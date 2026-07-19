<?php

namespace App\Support\Protocolos;

use App\Models\Entorno;
use App\Models\Paciente;
use App\Support\Pdf\TcpdfFuenteArial;
use Illuminate\Support\Facades\Schema;
use TCPDF;

/**
 * Etiquetas térmicas para tubos de ensayo.
 *
 * Formato de página personalizado (no A4): ancho/alto según entorno.e_* 
 * (impresora térmica de etiquetas). Una fila de etiquetas por página.
 */
final class EtiquetasTuboTcpdf extends TCPDF
{
    /** @var array<string, float|int|bool> */
    private array $cfg;

    /**
     * @param  array<string, float|int|bool>  $cfg
     * @param  array{0: float, 1: float}  $pageSize  [ancho mm, alto mm]
     */
    private function __construct(array $cfg, array $pageSize)
    {
        // '' = orientación automática según el formato. Si se fuerza 'P' con
        // ancho > alto, TCPDF invierte las medidas (p. ej. 80×20 → 20×80).
        parent::__construct('', 'mm', $pageSize, true, 'UTF-8', false);
        $this->cfg = $cfg;
        $this->SetCreator(config('app.name', 'SILAVET'));
        $this->SetAuthor(config('app.name', 'SILAVET'));
        $this->SetTitle('Etiquetas de tubos');
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        $this->SetAutoPageBreak(false);
        $this->SetMargins(0, 0, 0);
        $this->SetCellPadding(0);
    }

    /**
     * @return array<string, float|int|bool>
     */
    public static function configDesdeEntorno(): array
    {
        $defaults = [
            'anchoPapel' => 80.0,
            'anchoEtiq' => 35.0,
            'altoEtiq' => 20.0,
            'cantCol' => 2,
            'gapX' => 2.0,
            'gapY' => 2.0,
            'marginTop' => 1.0,
            'marginBottom' => 0.0,
            'marginLeft' => 2.0,
            'marginRight' => 0.0,
            'fontLinea1' => 18,
            'fontLinea2' => 12,
            'fontLinea3' => 11,
            'fontLinea4' => 8,
            'maxLinea2' => 21,
            'maxLinea3' => 25,
            'borde' => false,
        ];

        if (! Schema::hasTable('entorno')) {
            return $defaults;
        }

        $entorno = Entorno::query()->orderBy('id')->first();
        if ($entorno === null) {
            return $defaults;
        }

        $map = [
            'anchoPapel' => 'e_AnchoPapel',
            'anchoEtiq' => 'e_AnchoEtiq',
            'altoEtiq' => 'e_AltoEtiq',
            'cantCol' => 'e_CantCol',
            'gapX' => 'e_GapX',
            'gapY' => 'e_GapY',
            'marginTop' => 'e_MarginTop',
            'marginBottom' => 'e_MarginBottom',
            'marginLeft' => 'e_MarginLeft',
            'marginRight' => 'e_MarginRight',
            'fontLinea1' => 'e_FontLinea1',
            'fontLinea2' => 'e_FontLinea2',
            'fontLinea3' => 'e_FontLinea3',
            'fontLinea4' => 'e_FontLinea4',
            'maxLinea2' => 'e_MaxLargoLinea2',
            'maxLinea3' => 'e_MaxLargoLinea3',
            'borde' => 'e_Borde',
        ];

        foreach ($map as $clave => $columna) {
            if (! Schema::hasColumn('entorno', $columna)) {
                continue;
            }
            $valor = $entorno->{$columna};
            if ($valor === null || $valor === '') {
                continue;
            }
            if ($clave === 'borde') {
                $defaults[$clave] = (bool) (int) $valor;
            } elseif (in_array($clave, ['cantCol', 'fontLinea1', 'fontLinea2', 'fontLinea3', 'fontLinea4', 'maxLinea2', 'maxLinea3'], true)) {
                $defaults[$clave] = max(1, (int) $valor);
            } else {
                $defaults[$clave] = (float) $valor;
            }
        }

        $defaults['cantCol'] = max(1, (int) $defaults['cantCol']);

        return $defaults;
    }

    /**
     * @return list<array{linea1: string, linea2: string, linea3: string, linea4: string}>
     */
    public static function armarEtiquetas(Paciente $paciente, int $cantidad, array $cfg): array
    {
        $paciente->loadMissing('cliente');

        $nombrePaciente = self::titleCase((string) ($paciente->nombre ?? ''));
        $propietario = self::titleCase((string) ($paciente->propietario ?? ''));
        $nombreClientes = self::titleCase((string) ($paciente->cliente?->nombre ?? ''));
        $nombreProtocolo = trim((string) ($paciente->nombreProtocolo ?? ''));

        $idEspecies = (int) ($paciente->idEspecies ?? 0);
        $prefijoEspecie = match ($idEspecies) {
            1 => 'CA - ',
            5 => 'FE - ',
            default => '',
        };

        $fecha = $paciente->fechhoy?->format('d/m/Y') ?? '';

        $max2 = (int) $cfg['maxLinea2'];
        $max3 = (int) $cfg['maxLinea3'];

        $linea1 = $prefijoEspecie.$nombreProtocolo;
        $linea2 = self::truncarMb($nombrePaciente.' - '.$propietario, $max2);
        $linea3 = self::truncarMb($nombreClientes, $max3);
        $linea4 = $fecha;

        $etiquetas = [];
        $cantidad = max(1, min(99, $cantidad));
        for ($i = 0; $i < $cantidad; $i++) {
            $etiquetas[] = [
                'linea1' => $linea1,
                'linea2' => $linea2,
                'linea3' => $linea3,
                'linea4' => $linea4,
            ];
        }

        return $etiquetas;
    }

    /**
     * @param  list<array{linea1: string, linea2: string, linea3: string, linea4: string}>  $etiquetas
     */
    public static function generar(array $etiquetas, ?array $cfg = null): self
    {
        $cfg = $cfg ?? self::configDesdeEntorno();
        $ancho = max(10.0, (float) $cfg['anchoPapel']);
        $alto = max(5.0, (float) $cfg['altoEtiq']);

        $pdf = new self($cfg, [$ancho, $alto]);
        $columnas = (int) $cfg['cantCol'];
        $filas = array_chunk($etiquetas, $columnas);

        foreach ($filas as $fila) {
            $pdf->AddPage();
            $pdf->dibujarFila($fila);
        }

        return $pdf;
    }

    public static function nombreArchivo(Paciente $paciente): string
    {
        $proto = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) ($paciente->nombreProtocolo ?? 'etiquetas')) ?: 'etiquetas';

        return 'etiquetas_'.$proto.'.pdf';
    }

    public static function respuestaHttp(self $pdf, string $nombreArchivo): \Illuminate\Http\Response
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $binario = $pdf->Output($nombreArchivo, 'S');

        return response($binario, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$nombreArchivo.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * @param  list<array{linea1: string, linea2: string, linea3: string, linea4: string}>  $fila
     */
    private function dibujarFila(array $fila): void
    {
        $columnas = (int) $this->cfg['cantCol'];
        $anchoEtiq = (float) $this->cfg['anchoEtiq'];
        $altoEtiq = (float) $this->cfg['altoEtiq'];
        $gapX = (float) $this->cfg['gapX'];
        $marginLeft = (float) $this->cfg['marginLeft'];
        $marginTop = (float) $this->cfg['marginTop'];

        $x = $marginLeft;
        $y = $marginTop;

        for ($i = 0; $i < $columnas; $i++) {
            if (isset($fila[$i])) {
                $this->dibujarEtiqueta($x, $y, $anchoEtiq, $altoEtiq, $fila[$i]);
            }
            $x += $anchoEtiq + $gapX;
        }
    }

    /**
     * @param  array{linea1: string, linea2: string, linea3: string, linea4: string}  $e
     */
    private function dibujarEtiqueta(float $x, float $y, float $w, float $h, array $e): void
    {
        $pad = 1.0;
        $innerX = $x + $pad;
        $innerY = $y + $pad;
        $innerW = max(1.0, $w - (2 * $pad));
        $innerH = max(1.0, $h - (2 * $pad));

        if (! empty($this->cfg['borde'])) {
            $this->SetDrawColor(0, 0, 0);
            $this->SetLineWidth(0.2);
            $this->Rect($innerX, $innerY, $innerW, $innerH);
        }

        $lineas = [
            ['texto' => $e['linea1'], 'size' => (float) $this->cfg['fontLinea1']],
            ['texto' => $e['linea2'], 'size' => (float) $this->cfg['fontLinea2']],
            ['texto' => $e['linea3'], 'size' => (float) $this->cfg['fontLinea3']],
            ['texto' => $e['linea4'], 'size' => (float) $this->cfg['fontLinea4']],
        ];

        $alturas = [];
        $altoTotal = 0.0;
        foreach ($lineas as $linea) {
            // ~0.35 mm por punto tipográfico
            $ah = max(2.0, $linea['size'] * 0.35);
            $alturas[] = $ah;
            $altoTotal += $ah;
        }

        $cursorY = $innerY + max(0.0, ($innerH - $altoTotal) / 2);

        foreach ($lineas as $idx => $linea) {
            TcpdfFuenteArial::aplicar($this, 'B', $linea['size']);
            $this->SetXY($innerX, $cursorY);
            $this->Cell($innerW, $alturas[$idx], $linea['texto'], 0, 0, 'C', false, '', 0, false, 'T', 'M');
            $cursorY += $alturas[$idx];
        }
    }

    private static function titleCase(string $texto): string
    {
        $texto = trim($texto);
        if ($texto === '') {
            return '';
        }

        if (function_exists('mb_convert_case')) {
            return mb_convert_case($texto, MB_CASE_TITLE, 'UTF-8');
        }

        return ucwords(strtolower($texto));
    }

    private static function truncarMb(string $texto, int $max): string
    {
        $max = max(1, $max);
        if (function_exists('mb_substr')) {
            return mb_substr($texto, 0, $max, 'UTF-8');
        }

        return substr($texto, 0, $max);
    }
}
