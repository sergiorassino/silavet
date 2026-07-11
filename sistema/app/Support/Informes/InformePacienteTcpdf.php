<?php

namespace App\Support\Informes;

use App\Support\Pdf\TcpdfFuenteArial;
use App\Support\Pdf\TcpdfLogoInstitucional;
use Illuminate\Http\Response;
use setasign\Fpdi\Tcpdf\Fpdi;
use Throwable;

/**
 * Informe de protocolo (paciente) — TCPDF + FPDI.
 *
 * Formato legacy NeoLab: hoja 210×290 mm (no A4), barra superior gruesa,
 * logo izq. + contacto der., datos de paciente solo en la 1.ª página.
 */
final class InformePacienteTcpdf extends Fpdi
{
    /** Ancho de página legacy (mm). */
    private const PAGE_W = 210.0;

    /** Alto de página legacy (mm). */
    private const PAGE_H = 290.0;

    private const MARGEN = 15.0;

    /** Barra superior (centro Y y grosor del trazo, mm). */
    private const BARRA_SUP_Y = 15.0;

    private const BARRA_SUP_GROSOR = 4.0;

    /** Línea bajo el membrete (mm) — debajo del logo para que no lo cruce. */
    private const LINEA_MEMBRETE_Y = 50.0;

    private const LINEA_MEMBRETE_GROSOR = 1.0;

    /** Y del logo: debajo de la barra superior (evita solapamiento). */
    private const LOGO_Y = 18.5;

    private const LOGO_TAM = 30.0;

    /** Margen inferior del contenido (mm). La reserva grande solo aplica al pie de firmas. */
    private const MARGEN_INFERIOR = 12.0;

    /** Reserva inferior para firmas (mm). */
    private const RESERVA_FOOTER = 38.0;

    private const ANCHO_IMAGEN = 100.0;

    /** Interlineado de filas (+10 % sobre el legacy ~5,6 mm). */
    private const ALTO_FILA = 6.16;

    /** @var array<string, mixed> */
    private array $datos;

    /** @var array{0: int, 1: int, 2: int} */
    private array $colorRgb = [103, 29, 143];

    private bool $esPrimeraPagina = true;

    /**
     * @param  array<string, mixed>  $datos
     */
    private function __construct(array $datos)
    {
        parent::__construct('P', 'mm', [self::PAGE_W, self::PAGE_H], true, 'UTF-8', false);
        $this->datos = $datos;
        $rgb = array_values((array) ($datos['color_rgb'] ?? [103, 29, 143]));
        if (count($rgb) >= 3) {
            $this->colorRgb = [(int) $rgb[0], (int) $rgb[1], (int) $rgb[2]];
        }

        $paciente = (array) ($datos['paciente'] ?? []);
        $titulo = trim(($paciente['protocolo'] ?? '').' '.($paciente['nombre'] ?? ''));

        $this->SetCreator(config('app.name', 'SILAVET'));
        $this->SetAuthor(config('app.name', 'SILAVET'));
        $this->SetTitle($titulo !== '' ? $titulo : 'Informe de protocolo');
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        $this->SetMargins(self::MARGEN, self::MARGEN, self::MARGEN);
        $this->SetAutoPageBreak(false);
        $this->setImageScale(1.25);
        $this->setJPEGQuality(75);
        $this->setFontSubsetting(true);
        $this->setPDFVersion('1.4');
    }

    /** Evita el pie "Powered by TCPDF". */
    public function Footer(): void {}

    public function Header(): void {}

    /**
     * @param  array<string, mixed>  $datos
     */
    public static function generar(array $datos): self
    {
        $pdf = new self($datos);
        $pdf->nuevaPaginaConEncabezado();
        $pdf->dibujarCuerpo();
        $pdf->dibujarObservaciones();
        $pdf->asegurarEspacio(self::RESERVA_FOOTER);
        $pdf->dibujarFooterFirmas();
        $pdf->incorporarAdjunto();

        return $pdf;
    }

    public static function nombreArchivo(array $datos): string
    {
        $paciente = (array) ($datos['paciente'] ?? []);
        $base = trim(($paciente['protocolo'] ?? '').'_'.($paciente['nombre'] ?? ''));
        $base = preg_replace('/[^\p{L}\p{N}_\- ]+/u', '', $base) ?: 'informe';
        $base = preg_replace('/\s+/', '_', trim($base)) ?: 'informe';
        $base = mb_substr($base, 0, 60);

        return $base.'_'.date('dmyHis').'_'.bin2hex(random_bytes(2)).'.pdf';
    }

    public static function respuestaHttp(self $pdf, string $nombreArchivo): Response
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $binario = $pdf->Output($nombreArchivo, 'S');
        $ascii = preg_replace('/[^A-Za-z0-9._-]+/', '_', $nombreArchivo) ?: 'informe.pdf';
        $disposition = 'inline; filename="'.$ascii.'"; filename*=UTF-8\'\''.rawurlencode($nombreArchivo);

        return response($binario, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition,
            'Content-Length' => (string) strlen($binario),
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
            'Vary' => 'Cookie, Authorization',
        ]);
    }

    private function nuevaPaginaConEncabezado(): void
    {
        $this->AddPage();
        $this->dibujarMembrete();
        if ($this->esPrimeraPagina) {
            $this->dibujarDatosPaciente();
            $this->esPrimeraPagina = false;
        }
    }

    /**
     * Membrete legacy: barra gruesa, logo izq., contacto der., línea bajo membrete.
     * Sin repetir el nombre del laboratorio (ya va en el logo).
     */
    private function dibujarMembrete(): void
    {
        $header = (array) ($this->datos['header'] ?? []);
        $direccion = trim((string) ($header['direccion'] ?? ''));
        $telefono = trim((string) ($header['telefono'] ?? ''));
        $email = trim((string) ($header['email'] ?? ''));
        $logoFile = TcpdfLogoInstitucional::resolverArchivo(
            is_string($header['logo_file'] ?? null) ? $header['logo_file'] : null
        );

        // Barra superior gruesa con extremos redondeados.
        $this->setLineStyle([
            'width' => self::BARRA_SUP_GROSOR,
            'cap' => 'round',
            'join' => 'round',
            'dash' => 0,
            'color' => $this->colorRgb,
        ]);
        $this->Line(self::MARGEN, self::BARRA_SUP_Y, self::PAGE_W - self::MARGEN, self::BARRA_SUP_Y);
        $this->setLineStyle([
            'width' => 0.2,
            'cap' => 'butt',
            'join' => 'miter',
            'dash' => 0,
            'color' => [0, 0, 0],
        ]);

        if ($logoFile !== null) {
            TcpdfLogoInstitucional::dibujar(
                $this,
                self::MARGEN,
                self::LOGO_Y,
                self::LOGO_TAM,
                self::LOGO_TAM,
                $logoFile,
            );
        }

        // Contacto a la derecha, itálica, tres líneas (como el sistema viejo).
        TcpdfFuenteArial::aplicar($this, 'I', 8);
        $this->SetTextColor(0, 0, 0);
        $yContacto = self::LOGO_Y + 8.0;
        $lineas = array_values(array_filter([$direccion, $telefono, $email], static fn (string $l): bool => $l !== ''));
        foreach ($lineas as $i => $linea) {
            $this->SetXY(self::MARGEN, $yContacto + ($i * 4.0));
            $this->Cell($this->anchoUtil(), 4, $linea, 0, 1, 'R');
        }

        // Línea bajo membrete.
        $this->SetDrawColor($this->colorRgb[0], $this->colorRgb[1], $this->colorRgb[2]);
        $this->SetLineWidth(self::LINEA_MEMBRETE_GROSOR);
        $this->Line(self::MARGEN, self::LINEA_MEMBRETE_Y, self::PAGE_W - self::MARGEN, self::LINEA_MEMBRETE_Y);
        $this->SetLineWidth(0.2);
        $this->SetDrawColor(0, 0, 0);

        $this->SetY(self::LINEA_MEMBRETE_Y + 5.0);
    }

    private function dibujarDatosPaciente(): void
    {
        $p = (array) ($this->datos['paciente'] ?? []);
        $colIzq = 80.0;
        $colDer = 100.0;

        TcpdfFuenteArial::aplicar($this, 'I', 9);
        $this->SetTextColor(0, 0, 0);

        $filas = [
            ['Protocolo: '.($p['protocolo'] ?? ''), 'Especie: '.($p['especie'] ?? '')],
            ['Fecha: '.($p['fecha'] ?? ''), 'Raza: '.($p['raza'] ?? '')],
            ['Paciente: '.($p['nombre'] ?? ''), 'Sexo: '.($p['sexo'] ?? '')],
            ['Tutor Responsable: '.($p['propietario'] ?? ''), 'Edad: '.($p['edad'] ?? '')],
            ['Veterinaria: '.($p['cliente'] ?? ''), ''],
        ];

        foreach ($filas as $fila) {
            $this->Cell($colIzq, 5, (string) $fila[0], 0, 0, 'L');
            $this->Cell($colDer, 5, (string) $fila[1], 0, 1, 'L');
        }

        $this->Ln(6);
    }

    private function dibujarCuerpo(): void
    {
        /** @var list<array<string, mixed>> $grupos */
        $grupos = (array) ($this->datos['grupos'] ?? []);
        $rotuloRef = (string) (($this->datos['paciente']['rotulo_ref'] ?? '') ?: '');

        foreach ($grupos as $grupo) {
            $nombreGrupo = (string) ($grupo['nombreGrupo'] ?? '');
            $this->asegurarEspacio(16);
            $this->dibujarTituloGrupo($nombreGrupo);

            $mostrarRefs = ! in_array(mb_strtoupper($nombreGrupo), ['OBSERVACIONES', 'INFORME DE ECOGRAFÍA'], true);
            if ($mostrarRefs) {
                TcpdfFuenteArial::aplicar($this, '', 7);
                $etiqueta = 'VALORES DE REFERENCIA'.($rotuloRef !== '' ? ' '.$rotuloRef : '');
                $this->Cell(0, 3, $etiqueta, 0, 1, 'R');
                $this->Ln(1);
            }

            /** @var list<array<string, mixed>> $renglones */
            $renglones = (array) ($grupo['renglones'] ?? []);
            foreach ($renglones as $renglon) {
                $this->dibujarRenglon($renglon);
            }

            $this->Ln(4);
        }
    }

    private function dibujarTituloGrupo(string $nombreGrupo): void
    {
        TcpdfFuenteArial::aplicar($this, '', 11);
        $this->SetTextColor($this->colorRgb[0], $this->colorRgb[1], $this->colorRgb[2]);
        $this->Cell(0, 5, $nombreGrupo, 0, 1, 'C');
        $y = $this->GetY() + 1.5;
        $this->SetDrawColor($this->colorRgb[0], $this->colorRgb[1], $this->colorRgb[2]);
        $this->SetLineWidth(0.2);
        $this->Line(self::MARGEN, $y, self::PAGE_W - self::MARGEN, $y);
        $this->SetDrawColor(0, 0, 0);
        $this->SetTextColor(0, 0, 0);
        $this->SetY($y + 2.5);
    }

    /**
     * @param  array<string, mixed>  $r
     */
    private function dibujarRenglon(array $r): void
    {
        $tipo = (int) ($r['tipoItem'] ?? 0);

        match ($tipo) {
            5 => $this->dibujarLineaSeparadora(),
            3 => $this->dibujarTituloItem((string) ($r['nombreItem'] ?? '')),
            2 => $this->dibujarTextoFijo($r),
            8 => $this->dibujarTextoLargo($r),
            9 => $this->dibujarDosValores($r),
            10 => $this->dibujarImagenes($r),
            default => $this->dibujarValorReferencia($r),
        };
    }

    /**
     * @param  array<string, mixed>  $r
     */
    private function dibujarValorReferencia(array $r): void
    {
        $wNombre = 60.0;
        $wValor = 55.0;
        $wRef = $this->anchoUtil() - $wNombre - $wValor;

        $nombre = (string) ($r['nombreItem'] ?? '');
        $valor = trim((string) ($r['valor'] ?? ''));
        $unidad = trim((string) ($r['unidadMedida'] ?? ''));
        $textoValor = trim($valor.($unidad !== '' ? '  '.$unidad : ''));
        $ref = (string) ($r['referencia'] ?? '');

        $h = max(
            self::ALTO_FILA,
            $this->alturaMultiCell($wNombre, $nombre, 9),
            $this->alturaMultiCell($wValor, $textoValor, 9),
            $this->alturaMultiCell($wRef, $ref, 6.5)
        );
        $this->asegurarEspacio($h + 0.5);

        $x = self::MARGEN;
        $y = $this->GetY();

        TcpdfFuenteArial::aplicar($this, '', 9);
        $this->MultiCell($wNombre, 4.5, $nombre, 0, 'L', false, 0, $x, $y, true, 0, false, true, $h, 'M');
        $this->MultiCell($wValor, 4.5, $textoValor, 0, 'L', false, 0, $x + $wNombre, $y, true, 0, false, true, $h, 'M');
        TcpdfFuenteArial::aplicar($this, '', 6.5);
        $this->MultiCell($wRef, 3.2, $ref, 0, 'R', false, 1, $x + $wNombre + $wValor, $y, true, 0, false, true, $h, 'M');
    }

    /**
     * @param  array<string, mixed>  $r
     */
    private function dibujarDosValores(array $r): void
    {
        $wNombre = 55.0;
        $wV1 = 28.0;
        $wV2 = 32.0;
        $wRef = $this->anchoUtil() - $wNombre - $wV1 - $wV2;

        $nombre = (string) ($r['nombreItem'] ?? '');
        $v1 = trim((string) ($r['valor'] ?? '').' '.(string) ($r['unidadMedida'] ?? ''));
        $v2 = trim((string) ($r['valor2'] ?? '').' '.(string) ($r['unidadMedida2'] ?? ''));
        $ref = (string) ($r['referencia'] ?? '');

        $h = max(
            self::ALTO_FILA,
            $this->alturaMultiCell($wNombre, $nombre, 9),
            $this->alturaMultiCell($wRef, $ref, 6.5)
        );
        $this->asegurarEspacio($h + 0.5);
        $x = self::MARGEN;
        $y = $this->GetY();

        TcpdfFuenteArial::aplicar($this, '', 9);
        $this->MultiCell($wNombre, 4.5, $nombre, 0, 'L', false, 0, $x, $y, true, 0, false, true, $h, 'M');
        $this->MultiCell($wV1, 4.5, $v1, 0, 'L', false, 0, $x + $wNombre, $y, true, 0, false, true, $h, 'M');
        $this->MultiCell($wV2, 4.5, $v2, 0, 'L', false, 0, $x + $wNombre + $wV1, $y, true, 0, false, true, $h, 'M');
        TcpdfFuenteArial::aplicar($this, '', 6.5);
        $this->MultiCell($wRef, 3.2, $ref, 0, 'R', false, 1, $x + $wNombre + $wV1 + $wV2, $y, true, 0, false, true, $h, 'M');
    }

    /**
     * @param  array<string, mixed>  $r
     */
    private function dibujarTextoFijo(array $r): void
    {
        $this->asegurarEspacio(7);
        TcpdfFuenteArial::aplicar($this, '', 8);
        $texto = trim((string) ($r['nombreItem'] ?? '').' '.(string) ($r['valor'] ?? ''));
        $this->MultiCell($this->anchoUtil(), 4, $texto, 0, 'L', false, 1);
        $this->Ln(0.5);
    }

    private function dibujarTituloItem(string $nombre): void
    {
        $this->asegurarEspacio(9);
        $this->Ln(1);
        TcpdfFuenteArial::aplicar($this, '', 9);
        $this->MultiCell($this->anchoUtil(), 5, $nombre, 0, 'L', false, 1);
        $this->Ln(1);
    }

    /**
     * @param  array<string, mixed>  $r
     */
    private function dibujarTextoLargo(array $r): void
    {
        $wNombre = 60.0;
        $wValor = $this->anchoUtil() - $wNombre;
        $nombre = (string) ($r['nombreItem'] ?? '');
        $valor = (string) ($r['valor'] ?? '');

        $h = max(
            self::ALTO_FILA,
            $this->alturaMultiCell($wNombre, $nombre, 9),
            $this->alturaMultiCell($wValor, $valor, 9)
        );
        $this->asegurarEspacio($h + 2);
        $x = self::MARGEN;
        $y = $this->GetY();

        TcpdfFuenteArial::aplicar($this, '', 9);
        $this->MultiCell($wNombre, 4.5, $nombre, 0, 'L', false, 0, $x, $y, true, 0, false, true, $h, 'T');
        $this->MultiCell($wValor, 4.5, $valor, 0, 'L', false, 1, $x + $wNombre, $y, true, 0, false, true, $h, 'T');
        $this->Ln(1);
    }

    private function dibujarLineaSeparadora(): void
    {
        $this->asegurarEspacio(5);
        $y = $this->GetY() + 1.5;
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.2);
        $this->Line(self::MARGEN, $y, self::PAGE_W - self::MARGEN, $y);
        $this->SetY($y + 2);
    }

    /**
     * @param  array<string, mixed>  $r
     */
    private function dibujarImagenes(array $r): void
    {
        /** @var list<array{ruta: string, observacion: string}> $imagenes */
        $imagenes = (array) ($r['imagenes'] ?? []);
        if ($imagenes === []) {
            return;
        }

        $primera = true;
        foreach ($imagenes as $img) {
            $ruta = (string) ($img['ruta'] ?? '');
            if ($ruta === '' || ! is_file($ruta)) {
                continue;
            }

            $this->asegurarEspacio(55);

            if ($primera) {
                TcpdfFuenteArial::aplicar($this, 'B', 10);
                $this->SetFillColor(240, 240, 240);
                $this->Cell($this->anchoUtil(), 7, (string) ($r['nombreItem'] ?? ''), 1, 1, 'C', true);
                $this->SetFillColor(255, 255, 255);
                $this->Ln(2);
                $primera = false;
            }

            $x = self::MARGEN + (($this->anchoUtil() - self::ANCHO_IMAGEN) / 2);
            $y = $this->GetY();

            try {
                $this->Image($ruta, $x, $y, self::ANCHO_IMAGEN, 0, '', '', '', false, 150, '', false, false, 1);
                $nuevoY = method_exists($this, 'getImageRBY') ? (float) $this->getImageRBY() : $this->GetY();
                if ($nuevoY <= $y + 1) {
                    $info = @getimagesize($ruta);
                    $h = self::ANCHO_IMAGEN * 0.75;
                    if (is_array($info) && ($info[0] ?? 0) > 0) {
                        $h = self::ANCHO_IMAGEN * ((float) $info[1] / (float) $info[0]);
                    }
                    $this->SetY($y + $h + 2);
                } else {
                    $this->SetY($nuevoY + 2);
                }
            } catch (Throwable) {
                TcpdfFuenteArial::aplicar($this, '', 8);
                $this->Cell(0, 5, '[Imagen no disponible]', 0, 1, 'C');
            }

            $obs = trim((string) ($img['observacion'] ?? ''));
            if ($obs !== '') {
                $this->asegurarEspacio(10);
                TcpdfFuenteArial::aplicar($this, '', 8);
                $this->MultiCell($this->anchoUtil(), 4, $obs, 0, 'J', false, 1);
                $this->Ln(2);
            }
        }
    }

    private function dibujarObservaciones(): void
    {
        $obs = trim((string) (($this->datos['paciente']['observaciones'] ?? '') ?: ''));
        if ($obs === '') {
            return;
        }

        $this->asegurarEspacio(14);
        TcpdfFuenteArial::aplicar($this, '', 8);
        $this->MultiCell($this->anchoUtil(), 4, 'Observaciones:  '.$obs, 0, 'L', false, 1);
        $this->Ln(2);
    }

    private function dibujarFooterFirmas(): void
    {
        $f = (array) ($this->datos['footer'] ?? []);
        $anchoCol = $this->anchoUtil() / 3;
        $yInicio = $this->GetY() + 4;
        $altoFirma = 16.0;

        $firmas = [
            ['file' => $f['firmaIzq'] ?? null, 't1' => $f['texto1footerIzq'] ?? '', 't2' => $f['texto2footerIzq'] ?? ''],
            ['file' => $f['firmaCentro'] ?? null, 't1' => $f['texto1footerCentro'] ?? '', 't2' => $f['texto2footerCentro'] ?? ''],
            ['file' => $f['firmaDer'] ?? null, 't1' => $f['texto1footerDer'] ?? '', 't2' => $f['texto2footerDer'] ?? ''],
        ];

        foreach ($firmas as $i => $col) {
            $x = self::MARGEN + ($i * $anchoCol);
            $file = is_string($col['file'] ?? null) ? $col['file'] : null;
            if ($file !== null && is_file($file)) {
                try {
                    $this->Image($file, $x + (($anchoCol - 28) / 2), $yInicio, 28, 0, '', '', '', false, 150);
                } catch (Throwable) {
                    // Firma ilegible: continuar con textos.
                }
            }

            TcpdfFuenteArial::aplicar($this, '', 6);
            $this->SetXY($x, $yInicio + $altoFirma);
            $this->MultiCell($anchoCol, 3.2, trim((string) $col['t1']), 0, 'C', false, 1);
            $this->SetX($x);
            $this->MultiCell($anchoCol, 3.2, trim((string) $col['t2']), 0, 'C', false, 1);
        }

        // Barra inferior gruesa debajo del bloque de firmas (legacy).
        $yBarra = max($this->GetY() + 3, $yInicio + $altoFirma + 10);
        $this->setLineStyle([
            'width' => 3.0,
            'cap' => 'round',
            'join' => 'round',
            'dash' => 0,
            'color' => $this->colorRgb,
        ]);
        $this->Line(self::MARGEN, $yBarra, self::PAGE_W - self::MARGEN, $yBarra);
        $this->setLineStyle([
            'width' => 0.2,
            'cap' => 'butt',
            'join' => 'miter',
            'dash' => 0,
            'color' => [0, 0, 0],
        ]);
    }

    private function incorporarAdjunto(): void
    {
        $ruta = $this->datos['adjunto_ruta'] ?? null;
        if (! is_string($ruta) || $ruta === '' || ! is_file($ruta)) {
            return;
        }

        try {
            $pageCount = $this->setSourceFile($ruta);
        } catch (Throwable) {
            return;
        }

        for ($n = 1; $n <= $pageCount; $n++) {
            $this->AddPage();
            $this->SetDrawColor(0, 64, 128);
            $this->RoundedRect(15, 20, 180, 240, 4, '1111', 'D');
            try {
                $pageId = $this->importPage($n);
                $this->useImportedPage($pageId, 20, 25, 170);
            } catch (Throwable) {
                TcpdfFuenteArial::aplicar($this, '', 10);
                $this->SetXY(20, 40);
                $this->Cell(0, 6, 'No se pudo incorporar la página del adjunto.', 0, 1, 'C');
            }
        }
    }

    private function asegurarEspacio(float $necesarioMm): void
    {
        // Contenido: solo margen inferior chico (usa casi toda la hoja).
        // Antes del pie se llama con RESERVA_FOOTER.
        $limite = self::PAGE_H - self::MARGEN_INFERIOR;
        if ($this->GetY() + $necesarioMm > $limite) {
            $this->nuevaPaginaConEncabezado();
        }
    }

    private function anchoUtil(): float
    {
        return self::PAGE_W - (2 * self::MARGEN);
    }

    private function alturaMultiCell(float $ancho, string $texto, float $fontSize = 9): float
    {
        if ($texto === '') {
            return self::ALTO_FILA;
        }

        TcpdfFuenteArial::aplicar($this, '', $fontSize);
        $lineas = max(1, $this->getNumLines($texto, $ancho));

        return max(self::ALTO_FILA, $lineas * 4.2);
    }
}
