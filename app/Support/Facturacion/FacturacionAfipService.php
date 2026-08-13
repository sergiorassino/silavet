<?php

namespace App\Support\Facturacion;

use App\Models\Cliente;
use App\Models\CompAfip;
use App\Models\Movimiento;
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
        $receptor = $this->armReceptor($paciente, $emisor, $importe);
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

        $idMovimientos = CompAfip::tieneColumnaMovimientos()
            ? (int) ($factura->idMovimientos ?? 0)
            : 0;

        if ($idMovimientos > 0 && FacturacionAfipConfig::esModoMovimientoCaja()) {
            $movimiento = $this->cargarMovimientoFacturable($idMovimientos);
            $paciente = $this->pacienteOpcionalDesdeMovimiento($movimiento);
        } else {
            $movimiento = null;
            $paciente = $this->cargarPacienteFacturable((int) $factura->idPacientes);
        }

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

        $comp = $this->persistir($paciente, $emisor, $receptor, $payload, $movimiento);

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
        $receptor = $this->armReceptor($paciente, $emisor, $importe);
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
        if (CompAfip::tieneColumnaAsoc()) {
            $vinculada = CompAfip::query()
                ->where('idCompAfipAsoc', $factura->id)
                ->exists();
            if ($vinculada) {
                return true;
            }
        }

        return $this->facturaAnuladaPorNcPosterior($factura);
    }

    /**
     * Tipos AFIP de nota de crédito. Nunca incluye el tipo de factura del emisor/config.
     *
     * @return list<int>
     */
    private function tiposNotaCreditoConocidos(): array
    {
        $cfg = FacturacionAfipConfig::config();
        $emisor = labCtx()->usuario();

        $tiposFactura = array_values(array_unique(array_filter([
            (int) ($cfg['cbte_tipo'] ?? 0),
            $emisor !== null ? (int) ($emisor->CbteTipo ?? 0) : 0,
            1,  // Factura A
            6,  // Factura B
            11, // Factura C
        ], static fn (int $t): bool => $t > 0)));

        $tipos = [
            (int) ($cfg['nota_credito_tipo'] ?? 0),
            2,  // NC A
            3,  // NC A (alt)
            7,  // NC B
            8,  // NC B (alt)
            12, // NC C
            13, // NC C (alt)
        ];

        if ($emisor !== null) {
            $tipos[] = (int) ($emisor->NtaCredTipo ?? 0);
        }

        return array_values(array_unique(array_filter(
            $tipos,
            static fn (int $t): bool => $t > 0 && ! in_array($t, $tiposFactura, true)
        )));
    }

    private function esTipoComanda(int $tipo): bool
    {
        return $tipo === FacturacionAfipConfig::CBTE_COMANDA
            || $tipo === (int) FacturacionAfipConfig::config()['comanda_tipo'];
    }

    private function esTipoNotaCredito(int $tipo): bool
    {
        return $tipo > 0 && in_array($tipo, $this->tiposNotaCreditoConocidos(), true);
    }

    private function esTipoFactura(int $tipo): bool
    {
        return $tipo > 0 && ! $this->esTipoComanda($tipo) && ! $this->esTipoNotaCredito($tipo);
    }

    /**
     * NC posterior del mismo origen: anula esta factura si aparece antes que otra factura.
     */
    private function facturaAnuladaPorNcPosterior(CompAfip $factura): bool
    {
        $columnas = ['id', 'CbteTipo', 'idPacientes'];
        if (CompAfip::tieneColumnaAsoc()) {
            $columnas[] = 'idCompAfipAsoc';
        }
        if (CompAfip::tieneColumnaMovimientos()) {
            $columnas[] = 'idMovimientos';
        }

        $q = CompAfip::query()
            ->where('id', '>', (int) $factura->id)
            ->orderBy('id')
            ->select($columnas);

        if (CompAfip::tieneColumnaMovimientos()) {
            $idMov = (int) ($factura->idMovimientos ?? 0);
            if ($idMov > 0) {
                $q->where('idMovimientos', $idMov);
            } else {
                $idPac = trim((string) ($factura->idPacientes ?? ''));
                if ($idPac === '' || $idPac === '0') {
                    return false;
                }
                $q->where('idPacientes', $idPac);
            }
        } else {
            $idPac = trim((string) ($factura->idPacientes ?? ''));
            if ($idPac === '' || $idPac === '0') {
                return false;
            }
            $q->where('idPacientes', $idPac);
        }

        foreach ($q->get() as $row) {
            $tipo = (int) $row->CbteTipo;

            if ($this->esTipoComanda($tipo)) {
                continue;
            }

            if ($this->esTipoNotaCredito($tipo)) {
                if (CompAfip::tieneColumnaAsoc()) {
                    $asoc = (int) ($row->idCompAfipAsoc ?? 0);
                    if ($asoc > 0) {
                        return $asoc === (int) $factura->id;
                    }
                }

                // Sin vínculo: la NC cierra la factura abierta anterior (esta).
                return true;
            }

            if ($this->esTipoFactura($tipo)) {
                // Otra factura después → esta no fue anulada por NC.
                return false;
            }
        }

        return false;
    }

    /**
     * @return list<CompAfip>
     */
    public function facturasAnulables(int $idPacientes): array
    {
        $comps = CompAfip::query()
            ->where('idPacientes', (string) $idPacientes)
            ->where('CbteTipo', '>', 0)
            ->orderByDesc('id')
            ->get();

        return $comps
            ->filter(fn (CompAfip $f) => $this->esTipoFactura((int) $f->CbteTipo)
                && ! $this->facturaTieneNotaCredito($f))
            ->values()
            ->all();
    }

    /**
     * @return array{comp: CompAfip, mensaje: string}
     */
    public function emitirFacturaCaja(int $idMovimientos, string $tipoReceptor, ?Usuario $emisor = null): array
    {
        $this->assertTenantHabilitado();
        $this->assertColumnaMovimientosCompAfip();
        $emisor = $this->resolverEmisor($emisor);
        $movimiento = $this->cargarMovimientoFacturable($idMovimientos);
        $this->assertPuedeEmitirFacturaCaja($idMovimientos);

        $importe = $this->importeMovimientoCaja($movimiento);
        $receptor = $this->armReceptorCaja($movimiento, $emisor, $importe, $tipoReceptor);
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

        $paciente = $this->pacienteOpcionalDesdeMovimiento($movimiento);

        $comp = $this->persistir($paciente, $emisor, $receptor, [
            'CbteTipo' => (int) $cfg['cbte_tipo'],
            'Concepto' => (int) $cfg['concepto'],
            'importe' => $importe,
            'fecha' => $fecha,
            'CbteHasta' => (int) $emision['cbte_hasta'],
            'conceptoFacturado' => 'Servicios de laboratorio',
            'CAE' => (string) $emision['cae'],
            'CAEFchVto' => $this->parseFechaAfip((string) $emision['cae_fch_vto']),
        ], $movimiento);

        return [
            'comp' => $comp,
            'mensaje' => 'Factura emitida correctamente'
                .(! empty($cfg['simular']) ? ' (simulación AFIP).' : '.'),
        ];
    }

    /**
     * @return array{comp: CompAfip, mensaje: string}
     */
    public function emitirComandaCaja(int $idMovimientos, string $tipoReceptor, ?Usuario $emisor = null): array
    {
        $this->assertTenantHabilitado();
        $this->assertColumnaMovimientosCompAfip();
        $emisor = $this->resolverEmisor($emisor);
        $movimiento = $this->cargarMovimientoFacturable($idMovimientos);
        $importe = $this->importeMovimientoCaja($movimiento);
        $receptor = $this->armReceptorCaja($movimiento, $emisor, $importe, $tipoReceptor);
        $receptor['condicion_iva_id'] = 0;
        $cfg = FacturacionAfipConfig::paraEmision($emisor);
        $comandaTipo = (int) $cfg['comanda_tipo'];
        $nro = $this->siguienteNumeroComanda($comandaTipo);
        $fecha = Carbon::now();
        $paciente = $this->pacienteOpcionalDesdeMovimiento($movimiento);

        $comp = $this->persistir($paciente, $emisor, $receptor, [
            'CbteTipo' => $comandaTipo,
            'Concepto' => (int) $cfg['concepto'],
            'importe' => $importe,
            'fecha' => $fecha,
            'CbteHasta' => $nro,
            'conceptoFacturado' => 'Servicios de laboratorio',
            'CAE' => '0',
            'CAEFchVto' => null,
        ], $movimiento);

        return [
            'comp' => $comp,
            'mensaje' => 'Comanda generada correctamente (N° '.$nro.').',
        ];
    }

    /**
     * @return list<CompAfip>
     */
    public function facturasAnulablesCaja(int $idMovimientos): array
    {
        if (! CompAfip::tieneColumnaMovimientos()) {
            return [];
        }

        $comps = CompAfip::query()
            ->where('idMovimientos', $idMovimientos)
            ->where('CbteTipo', '>', 0)
            ->orderByDesc('id')
            ->get();

        return $comps
            ->filter(fn (CompAfip $f) => $this->esTipoFactura((int) $f->CbteTipo)
                && ! $this->facturaTieneNotaCredito($f))
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
            ->with('cliente:idClientes,nombre,cuit,dni')
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

    private function cargarMovimientoFacturable(int $idMovimientos): Movimiento
    {
        $movimiento = Movimiento::query()
            ->with([
                'cliente:idClientes,nombre,cuit,dni',
                'paciente:idPacientes,nombre,propietario,dni',
            ])
            ->find($idMovimientos);

        if ($movimiento === null) {
            throw new RuntimeException('No se encontró el movimiento a facturar.');
        }

        if (! $movimiento->esIngreso()) {
            throw new RuntimeException('En este laboratorio solo se facturan ingresos.');
        }

        return $movimiento;
    }

    private function pacienteOpcionalDesdeMovimiento(Movimiento $movimiento): ?Paciente
    {
        $id = (int) ($movimiento->idPacientes ?? 0);
        if ($id <= 0) {
            return null;
        }

        return $movimiento->paciente instanceof Paciente ? $movimiento->paciente : null;
    }

    private function assertPuedeEmitirFacturaCaja(int $idMovimientos): void
    {
        if ($this->facturasAnulablesCaja($idMovimientos) !== []) {
            throw new RuntimeException(
                'Ya existe una factura vigente para este movimiento. Emita una nota de crédito si necesita anularla.'
            );
        }
    }

    private function importeMovimientoCaja(Movimiento $movimiento): float
    {
        $importe = round(abs((float) $movimiento->monto), 2);
        if ($importe <= 0) {
            throw new RuntimeException('El importe a facturar debe ser mayor a cero.');
        }

        return $importe;
    }

    private function assertColumnaMovimientosCompAfip(): void
    {
        if (! CompAfip::tieneColumnaMovimientos()) {
            throw new RuntimeException(
                'Falta la columna compafip.idMovimientos. Ejecute database/sql/compafip_id_movimientos.sql.'
            );
        }
    }

    /**
     * @return array{doc_tipo: int, doc_nro: string, razon_social: string, condicion_iva_id: int}
     */
    private function armReceptorCaja(
        Movimiento $movimiento,
        Usuario $emisor,
        float $importe,
        string $tipoReceptor
    ): array {
        if (! FacturacionAfipConfig::esTipoReceptorCajaValido($tipoReceptor)) {
            throw new RuntimeException('Seleccione a quién se factura: cliente, paciente o consumidor final.');
        }

        $cfg = FacturacionAfipConfig::config();
        $condicionDefault = (int) ($emisor->CondicionIVAReceptorId ?: $cfg['condicion_iva_receptor_id']);

        if ($tipoReceptor === FacturacionAfipConfig::RECEPTOR_CONSUMIDOR_FINAL) {
            $this->assertPuedeFacturarConsumidorFinal($importe, $cfg);

            return $this->receptorConsumidorFinal($cfg, $condicionDefault);
        }

        if ($tipoReceptor === FacturacionAfipConfig::RECEPTOR_PACIENTE) {
            $paciente = $this->pacienteOpcionalDesdeMovimiento($movimiento);
            if ($paciente === null) {
                throw new RuntimeException('Este movimiento no tiene protocolo/paciente asociado.');
            }

            if (! Schema::hasColumn('pacientes', 'dni')) {
                throw new RuntimeException(
                    'Falta la columna pacientes.dni en este laboratorio. Ejecute database/sql/pacientes_dni.sql.'
                );
            }

            $doc = $this->docNormalizado((string) ($paciente->dni ?? ''));
            if ($doc === '') {
                $this->assertPuedeFacturarConsumidorFinal($importe, $cfg);

                return $this->receptorConsumidorFinal($cfg, $condicionDefault);
            }

            $nombre = trim((string) ($paciente->propietario ?: $paciente->nombre));

            return $this->receptorConDocumento($cfg, $doc, $nombre, $condicionDefault);
        }

        $idClientes = (int) ($movimiento->idClientes ?? 0);
        if ($idClientes <= 0) {
            throw new RuntimeException('Este movimiento no tiene cliente asociado.');
        }

        $cliente = $movimiento->cliente;
        if (! $cliente instanceof Cliente) {
            throw new RuntimeException('No se encontró el cliente del movimiento.');
        }

        $doc = $this->docDesdeCampos(
            (string) ($cliente->cuit ?? ''),
            (string) ($cliente->dni ?? ''),
        );
        if ($doc === '') {
            $this->assertPuedeFacturarConsumidorFinal($importe, $cfg, 'cliente');

            return $this->receptorConsumidorFinal($cfg, $condicionDefault);
        }

        return $this->receptorConDocumento(
            $cfg,
            $doc,
            trim((string) $cliente->nombre) ?: 'Cliente',
            $condicionDefault
        );
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
    private function armReceptor(Paciente $paciente, Usuario $emisor, float $importe): array
    {
        $cfg = FacturacionAfipConfig::config();
        $tipo = (int) $paciente->tipoRegistro;
        $condicionDefault = (int) ($emisor->CondicionIVAReceptorId ?: $cfg['condicion_iva_receptor_id']);

        // Protocolo real → DNI/CUIT del paciente (columna pacientes.dni) o consumidor final (DocTipo 99).
        if ($tipo === Paciente::TIPO_PROTOCOLO) {
            if (! Schema::hasColumn('pacientes', 'dni')) {
                throw new RuntimeException(
                    'Falta la columna pacientes.dni en este laboratorio. Ejecute la migración o el SQL de database/sql/pacientes_dni.sql.'
                );
            }

            $doc = $this->docNormalizado((string) ($paciente->dni ?? ''));
            if ($doc === '') {
                $this->assertPuedeFacturarConsumidorFinal($importe, $cfg);

                return $this->receptorConsumidorFinal($cfg, $condicionDefault);
            }

            $nombre = trim((string) ($paciente->propietario ?: $paciente->nombre));

            return $this->receptorConDocumento($cfg, $doc, $nombre, $condicionDefault);
        }

        // Pago global / ingreso (modo movimiento o pago global) → cliente o consumidor final.
        $cliente = $paciente->cliente;
        if (! $cliente instanceof Cliente) {
            throw new RuntimeException('El registro no tiene cliente asociado.');
        }

        $doc = $this->docDesdeCampos(
            (string) ($cliente->cuit ?? ''),
            (string) ($cliente->dni ?? ''),
        );
        if ($doc === '') {
            $this->assertPuedeFacturarConsumidorFinal($importe, $cfg, 'cliente');

            return $this->receptorConsumidorFinal($cfg, $condicionDefault);
        }

        return $this->receptorConDocumento(
            $cfg,
            $doc,
            trim((string) $cliente->nombre) ?: 'Cliente',
            $condicionDefault
        );
    }

    private function docNormalizado(string $raw): string
    {
        $doc = preg_replace('/\D/', '', $raw) ?? '';

        return ($doc === '' || $doc === '0') ? '' : $doc;
    }

    private function docDesdeCampos(string ...$valores): string
    {
        foreach ($valores as $raw) {
            $doc = $this->docNormalizado($raw);
            if ($doc !== '') {
                return $doc;
            }
        }

        return '';
    }

    private function assertPuedeFacturarConsumidorFinal(float $importe, array $cfg, string $sujeto = 'paciente'): void
    {
        $minimo = (float) ($cfg['importe_minimo_identificacion_cf'] ?? 0);
        if ($minimo > 0 && round($importe, 2) >= $minimo) {
            $etiqueta = $sujeto === 'cliente' ? 'del cliente' : 'del paciente';
            throw new RuntimeException(
                'El importe supera $'.number_format($minimo, 0, ',', '.')
                .' y AFIP exige identificar al comprador. Cargue DNI o CUIT '.$etiqueta.'.'
            );
        }
    }

    /**
     * @param  array<string, mixed>  $cfg
     * @return array{doc_tipo: int, doc_nro: string, razon_social: string, condicion_iva_id: int}
     */
    private function receptorConsumidorFinal(array $cfg, int $condicionDefault): array
    {
        return [
            'doc_tipo' => (int) $cfg['doc_tipo_consumidor_final'],
            'doc_nro' => '0',
            'razon_social' => '',
            'condicion_iva_id' => $condicionDefault > 0 ? $condicionDefault : 5,
        ];
    }

    /**
     * @param  array<string, mixed>  $cfg
     * @return array{doc_tipo: int, doc_nro: string, razon_social: string, condicion_iva_id: int}
     */
    private function receptorConDocumento(array $cfg, string $doc, string $razonSocial, int $condicionDefault): array
    {
        return [
            'doc_tipo' => strlen($doc) === 11 ? (int) $cfg['doc_tipo_cuit'] : (int) $cfg['doc_tipo_dni'],
            'doc_nro' => $doc,
            'razon_social' => mb_substr($razonSocial !== '' ? $razonSocial : 'Consumidor final', 0, 100),
            'condicion_iva_id' => $condicionDefault > 0 ? $condicionDefault : 5,
        ];
    }

    /**
     * @param  array{doc_tipo: int, doc_nro: string, razon_social: string, condicion_iva_id: int}  $receptor
     * @param  array<string, mixed>  $extra
     */
    private function persistir(
        ?Paciente $paciente,
        Usuario $emisor,
        array $receptor,
        array $extra,
        ?Movimiento $movimiento = null
    ): CompAfip {
        $fecha = $extra['fecha'] instanceof Carbon ? $extra['fecha'] : Carbon::now();
        $idPacientes = $paciente !== null
            ? (string) $paciente->idPacientes
            : '0';

        $payload = [
            'idPacientes' => $idPacientes,
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

        if ($movimiento !== null && CompAfip::tieneColumnaMovimientos()) {
            $payload['idMovimientos'] = (int) $movimiento->id;
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
