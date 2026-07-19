<?php

namespace App\Support\Facturacion;

use App\Models\Cliente;
use App\Models\CompAfip;
use App\Models\Paciente;
use App\Models\Usuario;
use App\Support\Afip\AfipWsfeEmision;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Emisión individual: factura, nota de crédito (total) y comanda.
 */
final class FacturacionAfipService
{
    /**
     * @return array{comp: CompAfip, mensaje: string}
     */
    public function emitirFactura(int $idPacientes, ?Usuario $emisor = null): array
    {
        $this->assertTenantHabilitado();
        $emisor = $this->resolverEmisor($emisor);
        $paciente = $this->cargarPacienteFacturable($idPacientes);
        $this->assertPuedeEmitirFactura($paciente);

        $importe = $this->importeAFacturar($paciente);
        $receptor = $this->armReceptor($paciente, $emisor);
        $cfg = FacturacionAfipConfig::paraEmision($emisor);
        $fecha = Carbon::now();
        $fechaYmd = $fecha->format('Ymd');

        $cfg['doc_tipo'] = $receptor['doc_tipo'];

        $emision = AfipWsfeEmision::emitirRecibo($cfg, [
            'cuit' => preg_replace('/\D/', '', (string) $emisor->cuit) ?? '',
            'pto_vta' => (int) $emisor->PtoVta,
            'doc_nro' => (int) $receptor['doc_nro'],
            'importe' => $importe,
            'fecha_yyyymmdd' => $fechaYmd,
            'fch_serv_desde' => $fechaYmd,
            'fch_serv_hasta' => $fechaYmd,
            'condicion_iva_receptor_id' => $receptor['condicion_iva_id'],
            'tipo_cbte' => (int) $cfg['cbte_tipo'],
        ]);

        $comp = $this->persistir($paciente, $emisor, $receptor, [
            'CbteTipo' => (int) $cfg['cbte_tipo'],
            'Concepto' => (int) $cfg['concepto'],
            'importe' => $importe,
            'fecha' => $fecha,
            'CbteHasta' => (int) $emision['cbte_hasta'],
            'conceptoFacturado' => 'Servicios de laboratorio',
            'CAE' => (string) $emision['cae'],
            'CAEFchVto' => $this->parseFechaAfip((string) $emision['cae_fch_vto']),
        ]);

        return [
            'comp' => $comp,
            'mensaje' => 'Factura emitida correctamente'
                .(! empty($cfg['simular']) ? ' (simulación AFIP).' : '.'),
        ];
    }

    /**
     * @return array{comp: CompAfip, mensaje: string}
     */
    public function emitirNotaCredito(int $idCompAfipFactura, ?Usuario $emisor = null): array
    {
        $this->assertTenantHabilitado();
        $emisor = $this->resolverEmisor($emisor);

        $factura = CompAfip::query()->find($idCompAfipFactura);
        if ($factura === null || ! $factura->esFactura()) {
            throw new RuntimeException('No se encontró la factura a anular.');
        }

        if ($this->facturaTieneNotaCredito($factura)) {
            throw new RuntimeException('Esa factura ya tiene una nota de crédito asociada.');
        }

        $paciente = $this->cargarPacienteFacturable((int) $factura->idPacientes);
        $cfg = FacturacionAfipConfig::paraEmision($emisor);
        $ncTipo = (int) $cfg['nota_credito_tipo'];
        if ($ncTipo <= 0) {
            throw new RuntimeException('El emisor no tiene configurado el tipo de nota de crédito.');
        }

        $importe = round((float) $factura->importe, 2);
        $fecha = Carbon::now();
        $fechaYmd = $fecha->format('Ymd');

        $cfg['doc_tipo'] = (int) $factura->DocTipo;

        $emision = AfipWsfeEmision::emitirRecibo($cfg, [
            'cuit' => preg_replace('/\D/', '', (string) $emisor->cuit) ?? '',
            'pto_vta' => (int) $emisor->PtoVta,
            'doc_nro' => (int) preg_replace('/\D/', '', (string) $factura->DocNro),
            'importe' => $importe,
            'fecha_yyyymmdd' => $fechaYmd,
            'fch_serv_desde' => $fechaYmd,
            'fch_serv_hasta' => $fechaYmd,
            'condicion_iva_receptor_id' => (int) $factura->CondicionIVAReceptorId,
            'tipo_cbte' => $ncTipo,
            'cbte_asoc_nro' => (int) $factura->CbteHasta,
            'cbte_asoc_tipo' => (int) $factura->CbteTipo,
            'cbte_asoc_pto_vta' => (int) $factura->PtoVta,
            'motivo_nc' => 'Anulación de comprobante',
        ]);

        $receptor = [
            'doc_tipo' => (int) $factura->DocTipo,
            'doc_nro' => (string) $factura->DocNro,
            'razon_social' => (string) $factura->razonSocialCliente,
            'condicion_iva_id' => (int) $factura->CondicionIVAReceptorId,
        ];

        $payload = [
            'CbteTipo' => $ncTipo,
            'Concepto' => (int) $cfg['concepto'],
            'importe' => $importe,
            'fecha' => $fecha,
            'CbteHasta' => (int) $emision['cbte_hasta'],
            'conceptoFacturado' => 'Nota de crédito s/ '.$factura->numeroFormateado(),
            'CAE' => (string) $emision['cae'],
            'CAEFchVto' => $this->parseFechaAfip((string) $emision['cae_fch_vto']),
        ];
        if (CompAfip::tieneColumnaAsoc()) {
            $payload['idCompAfipAsoc'] = (int) $factura->id;
        }

        $comp = $this->persistir($paciente, $emisor, $receptor, $payload);

        return [
            'comp' => $comp,
            'mensaje' => 'Nota de crédito emitida correctamente'
                .(! empty($cfg['simular']) ? ' (simulación AFIP).' : '.'),
        ];
    }

    /**
     * @return array{comp: CompAfip, mensaje: string}
     */
    public function emitirComanda(int $idPacientes, ?Usuario $emisor = null): array
    {
        $this->assertTenantHabilitado();
        $emisor = $this->resolverEmisor($emisor);
        $paciente = $this->cargarPacienteFacturable($idPacientes);
        $importe = $this->importeAFacturar($paciente);
        $receptor = $this->armReceptor($paciente, $emisor);
        // Comanda interna: no discrimina condición IVA del receptor.
        $receptor['condicion_iva_id'] = 0;
        $cfg = FacturacionAfipConfig::paraEmision($emisor);
        $comandaTipo = (int) $cfg['comanda_tipo'];
        $nro = $this->siguienteNumeroComanda($comandaTipo);
        $fecha = Carbon::now();

        $comp = $this->persistir($paciente, $emisor, $receptor, [
            'CbteTipo' => $comandaTipo,
            'Concepto' => (int) $cfg['concepto'],
            'importe' => $importe,
            'fecha' => $fecha,
            'CbteHasta' => $nro,
            'conceptoFacturado' => 'Servicios de laboratorio',
            'CAE' => '0',
            'CAEFchVto' => null,
        ]);

        return [
            'comp' => $comp,
            'mensaje' => 'Comanda generada correctamente (N° '.$nro.').',
        ];
    }

    public function facturaTieneNotaCredito(CompAfip $factura): bool
    {
        if (! CompAfip::tieneColumnaAsoc()) {
            return false;
        }

        return CompAfip::query()
            ->where('idCompAfipAsoc', $factura->id)
            ->exists();
    }

    /**
     * @return list<CompAfip>
     */
    public function facturasAnulables(int $idPacientes): array
    {
        $cfg = FacturacionAfipConfig::config();
        $ncTipo = (int) $cfg['nota_credito_tipo'];
        $comandaTipo = (int) $cfg['comanda_tipo'];

        $facturas = CompAfip::query()
            ->where('idPacientes', (string) $idPacientes)
            ->where('CbteTipo', '>', 0)
            ->where('CbteTipo', '!=', $comandaTipo)
            ->when($ncTipo > 0, fn ($q) => $q->where('CbteTipo', '!=', $ncTipo))
            ->orderByDesc('id')
            ->get();

        return $facturas
            ->filter(fn (CompAfip $f) => ! $this->facturaTieneNotaCredito($f))
            ->values()
            ->all();
    }

    private function assertTenantHabilitado(): void
    {
        if (! FacturacionAfipConfig::habilitada()) {
            throw new RuntimeException('La facturación AFIP no está habilitada en este laboratorio.');
        }
    }

    private function resolverEmisor(?Usuario $emisor): Usuario
    {
        $emisor ??= labCtx()->usuario();
        if (! FacturacionAfipConfig::emisorPuedeFacturar($emisor)) {
            throw new RuntimeException(
                'El usuario no tiene permiso AFIP o faltan datos/certificados del emisor.'
            );
        }

        /** @var Usuario $emisor */
        return $emisor;
    }

    private function cargarPacienteFacturable(int $idPacientes): Paciente
    {
        $paciente = Paciente::query()
            ->with('cliente:idClientes,nombre,cuit')
            ->find($idPacientes);

        if ($paciente === null) {
            throw new RuntimeException('No se encontró el registro a facturar.');
        }

        $tipo = (int) $paciente->tipoRegistro;
        if (FacturacionAfipConfig::esModoMovimiento()) {
            if ($tipo !== Paciente::TIPO_INGRESO) {
                throw new RuntimeException('En este laboratorio solo se facturan ingresos.');
            }
        } elseif (! in_array($tipo, [Paciente::TIPO_PROTOCOLO, Paciente::TIPO_INGRESO], true)) {
            throw new RuntimeException('El registro no es facturable.');
        }

        return $paciente;
    }

    private function assertPuedeEmitirFactura(Paciente $paciente): void
    {
        $anulables = $this->facturasAnulables((int) $paciente->idPacientes);
        // Permitir nueva factura solo si no hay factura vigente (sin NC).
        // Varias comandas sí; una sola factura “abierta” a la vez.
        if ($anulables !== []) {
            throw new RuntimeException(
                'Ya existe una factura vigente para este registro. Emita una nota de crédito si necesita anularla.'
            );
        }
    }

    private function importeAFacturar(Paciente $paciente): float
    {
        $tipo = (int) $paciente->tipoRegistro;
        $importe = $tipo === Paciente::TIPO_INGRESO
            ? (float) $paciente->pagado
            : (float) $paciente->precio;

        $importe = round(abs($importe), 2);
        if ($importe <= 0) {
            throw new RuntimeException('El importe a facturar debe ser mayor a cero.');
        }

        return $importe;
    }

    /**
     * @return array{doc_tipo: int, doc_nro: string, razon_social: string, condicion_iva_id: int}
     */
    private function armReceptor(Paciente $paciente, Usuario $emisor): array
    {
        $cfg = FacturacionAfipConfig::config();
        $tipo = (int) $paciente->tipoRegistro;
        $condicionDefault = (int) ($emisor->CondicionIVAReceptorId ?: $cfg['condicion_iva_receptor_id']);

        // Protocolo real → DNI/CUIT del paciente (columna pacientes.dni).
        if ($tipo === Paciente::TIPO_PROTOCOLO) {
            if (! Schema::hasColumn('pacientes', 'dni')) {
                throw new RuntimeException(
                    'Falta la columna pacientes.dni en este laboratorio. Ejecute la migración o el SQL de database/sql/pacientes_dni.sql.'
                );
            }

            $doc = preg_replace('/\D/', '', (string) ($paciente->dni ?? '')) ?? '';
            if ($doc === '' || $doc === '0') {
                throw new RuntimeException('El paciente no tiene DNI/CUIT cargado para facturar.');
            }
            $nombre = trim((string) ($paciente->propietario ?: $paciente->nombre));
            if ($nombre === '') {
                $nombre = 'Consumidor final';
            }

            return [
                'doc_tipo' => strlen($doc) === 11 ? (int) $cfg['doc_tipo_cuit'] : (int) $cfg['doc_tipo_dni'],
                'doc_nro' => $doc,
                'razon_social' => mb_substr($nombre, 0, 100),
                'condicion_iva_id' => $condicionDefault > 0 ? $condicionDefault : 5,
            ];
        }

        // Pago global / ingreso → cliente (veterinaria).
        $cliente = $paciente->cliente;
        if (! $cliente instanceof Cliente) {
            throw new RuntimeException('El registro no tiene cliente asociado.');
        }

        $doc = preg_replace('/\D/', '', (string) ($cliente->cuit ?? '')) ?? '';
        if ($doc === '' || $doc === '0') {
            throw new RuntimeException('El cliente no tiene CUIT/DNI cargado para facturar.');
        }

        return [
            'doc_tipo' => strlen($doc) === 11 ? (int) $cfg['doc_tipo_cuit'] : (int) $cfg['doc_tipo_dni'],
            'doc_nro' => $doc,
            'razon_social' => mb_substr(trim((string) $cliente->nombre) ?: 'Cliente', 0, 100),
            'condicion_iva_id' => $condicionDefault > 0 ? $condicionDefault : 5,
        ];
    }

    /**
     * @param  array{doc_tipo: int, doc_nro: string, razon_social: string, condicion_iva_id: int}  $receptor
     * @param  array<string, mixed>  $extra
     */
    private function persistir(Paciente $paciente, Usuario $emisor, array $receptor, array $extra): CompAfip
    {
        $fecha = $extra['fecha'] instanceof Carbon ? $extra['fecha'] : Carbon::now();
        $payload = [
            'idPacientes' => (string) $paciente->idPacientes,
            'cuit' => preg_replace('/\D/', '', (string) $emisor->cuit) ?? '',
            'PtoVta' => (int) $emisor->PtoVta,
            'CbteTipo' => (int) $extra['CbteTipo'],
            'Concepto' => (int) $extra['Concepto'],
            'DocTipo' => (int) $receptor['doc_tipo'],
            'DocNro' => (string) $receptor['doc_nro'],
            'razonSocial' => mb_substr(trim((string) $emisor->razonSocial), 0, 100) ?: '0',
            'domicComerc' => mb_substr(trim((string) $emisor->domicComerc), 0, 50) ?: '0',
            'razonSocialCliente' => $receptor['razon_social'],
            'importe' => round((float) $extra['importe'], 2),
            'FechServDesde' => $fecha->toDateString(),
            'FechServHasta' => $fecha->toDateString(),
            'fechaComprobante' => $fecha->toDateString(),
            'CbteHasta' => (int) $extra['CbteHasta'],
            'CondicionIVAReceptorId' => (int) $receptor['condicion_iva_id'],
            'conceptoFacturado' => mb_substr((string) $extra['conceptoFacturado'], 0, 200),
            'CAE' => (string) ($extra['CAE'] ?? '0'),
            'CAEFchVto' => $extra['CAEFchVto'] ?? null,
        ];

        if (CompAfip::tieneColumnaAsoc() && isset($extra['idCompAfipAsoc'])) {
            $payload['idCompAfipAsoc'] = (int) $extra['idCompAfipAsoc'];
        }

        return CompAfip::query()->create($payload);
    }

    /** Numeración local global: MAX(CbteHasta) de comandas (CbteTipo 888) + 1. */
    private function siguienteNumeroComanda(int $comandaTipo): int
    {
        $ultimo = (int) CompAfip::query()
            ->where('CbteTipo', $comandaTipo)
            ->max('CbteHasta');

        return max(1, $ultimo + 1);
    }

    private function parseFechaAfip(string $ymd): ?string
    {
        $ymd = preg_replace('/\D/', '', $ymd) ?? '';
        if (strlen($ymd) !== 8) {
            return null;
        }

        return substr($ymd, 0, 4).'-'.substr($ymd, 4, 2).'-'.substr($ymd, 6, 2);
    }
}
