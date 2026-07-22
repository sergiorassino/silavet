<?php

namespace App\Livewire\Admin;

use App\Models\Entorno;
use App\Support\Entorno\EntornoArchivos;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithFileUploads;

class EntornoForm extends Component
{
    use WithFileUploads;

    public string $direLabo = '';

    public string $teleLabo = '';

    public string $emailLabo = '';

    public string $colorInforme = '#0EA5E9';

    public string $texto1footerIzq = '';

    public string $texto2footerIzq = '';

    public string $texto1footerCentro = '';

    public string $texto2footerCentro = '';

    public string $texto1footerDer = '';

    public string $texto2footerDer = '';

    public string $ctaEnvioMail = '';

    public string $passEnvioMail = '';

    public string $fromMail = '';

    public string $nombrePieMail = '';

    public string $direccionPieMail = '';

    public string $telefonoPieMail = '';

    public string $emailPieMail = '';

    public ?string $logoActual = null;

    public ?string $headerInformeActual = null;

    public ?string $footerInformeActual = null;

    public ?string $firmaIzqActual = null;

    public ?string $firmaCentroActual = null;

    public ?string $firmaDerActual = null;

    public bool $tienePassEnvioMail = false;

    public ?string $listaPreciosPdfActual = null;

    public $listaPreciosUpload = null;

    public $logoUpload = null;

    public $headerInformeUpload = null;

    public $footerInformeUpload = null;

    public $firmaIzqUpload = null;

    public $firmaCentroUpload = null;

    public $firmaDerUpload = null;

    public bool $tieneCamposHeaderFooter = false;

    /** Campos de impresión de etiquetas térmicas (entorno.e_*). */
    public string $e_AnchoPapel = '80';

    public string $e_AnchoEtiq = '35';

    public string $e_AltoEtiq = '20';

    public string $e_CantCol = '2';

    public string $e_GapX = '2';

    public string $e_GapY = '2';

    public string $e_MarginTop = '1';

    public string $e_MarginBottom = '0';

    public string $e_MarginLeft = '2';

    public string $e_MarginRight = '0';

    public string $e_FontLinea1 = '18';

    public string $e_FontLinea2 = '12';

    public string $e_FontLinea3 = '11';

    public string $e_FontLinea4 = '8';

    public string $e_MaxLargoLinea2 = '21';

    public string $e_MaxLargoLinea3 = '25';

    public bool $e_Borde = false;

    public bool $tieneCamposEtiquetas = false;

    /** A4 | termica80 */
    public string $afipFormatoImpresion = 'A4';

    public bool $tieneCampoAfipFormato = false;

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $entorno = Entorno::query()->orderBy('id')->first();
        abort_if($entorno === null, 404);

        $this->direLabo = (string) ($entorno->direLabo ?? '');
        $this->teleLabo = (string) ($entorno->teleLabo ?? '');
        $this->emailLabo = (string) ($entorno->emailLabo ?? '');
        $this->colorInforme = $this->normalizarColor((string) ($entorno->colorInforme ?? '#0EA5E9'));
        $this->texto1footerIzq = (string) ($entorno->texto1footerIzq ?? '');
        $this->texto2footerIzq = (string) ($entorno->texto2footerIzq ?? '');
        $this->texto1footerCentro = (string) ($entorno->texto1footerCentro ?? '');
        $this->texto2footerCentro = (string) ($entorno->texto2footerCentro ?? '');
        $this->texto1footerDer = (string) ($entorno->texto1footerDer ?? '');
        $this->texto2footerDer = (string) ($entorno->texto2footerDer ?? '');
        $this->ctaEnvioMail = (string) ($entorno->ctaEnvioMail ?? '');
        $this->fromMail = (string) ($entorno->fromMail ?? '');
        $this->nombrePieMail = (string) ($entorno->nombrePieMail ?? '');
        $this->direccionPieMail = (string) ($entorno->direccionPieMail ?? '');
        $this->telefonoPieMail = (string) ($entorno->telefonoPieMail ?? '');
        $this->emailPieMail = (string) ($entorno->emailPieMail ?? '');
        $this->listaPreciosPdfActual = $this->cargarRutaArchivo($entorno, 'listaPreciosPdf');
        $this->logoActual = $this->cargarRutaArchivo($entorno, 'logo');
        $this->firmaIzqActual = $this->cargarRutaArchivo($entorno, 'firmaIzq');
        $this->firmaCentroActual = $this->cargarRutaArchivo($entorno, 'firmaCentro');
        $this->firmaDerActual = $this->cargarRutaArchivo($entorno, 'firmaDer');
        $this->tienePassEnvioMail = trim((string) ($entorno->passEnvioMail ?? '')) !== '';

        $this->cargarCamposHeaderFooter($entorno);
        $this->cargarCamposEtiquetas($entorno);
        $this->cargarCampoAfipFormato($entorno);
    }

    private function cargarCamposHeaderFooter(Entorno $entorno): void
    {
        $this->tieneCamposHeaderFooter = Schema::hasColumn('entorno', 'headerInforme')
            && Schema::hasColumn('entorno', 'footerInforme');

        if (! $this->tieneCamposHeaderFooter) {
            $this->headerInformeActual = null;
            $this->footerInformeActual = null;

            return;
        }

        $this->headerInformeActual = $this->cargarRutaArchivo($entorno, 'headerInforme');
        $this->footerInformeActual = $this->cargarRutaArchivo($entorno, 'footerInforme');
    }

    private function cargarCampoAfipFormato(Entorno $entorno): void
    {
        $this->tieneCampoAfipFormato = Schema::hasColumn('entorno', 'afipFormatoImpresion');
        if (! $this->tieneCampoAfipFormato) {
            return;
        }

        $valor = trim((string) ($entorno->afipFormatoImpresion ?? 'A4'));
        $this->afipFormatoImpresion = in_array($valor, ['A4', 'termica80'], true) ? $valor : 'A4';
    }

    private function cargarCamposEtiquetas(Entorno $entorno): void
    {
        $this->tieneCamposEtiquetas = Schema::hasColumn('entorno', 'e_AnchoPapel');
        if (! $this->tieneCamposEtiquetas) {
            return;
        }

        $this->e_AnchoPapel = $this->numStr($entorno->e_AnchoPapel ?? 80);
        $this->e_AnchoEtiq = $this->numStr($entorno->e_AnchoEtiq ?? 35);
        $this->e_AltoEtiq = $this->numStr($entorno->e_AltoEtiq ?? 20);
        $this->e_CantCol = (string) max(1, (int) ($entorno->e_CantCol ?? 2));
        $this->e_GapX = $this->numStr($entorno->e_GapX ?? 2);
        $this->e_GapY = $this->numStr($entorno->e_GapY ?? 2);
        $this->e_MarginTop = $this->numStr($entorno->e_MarginTop ?? 1);
        $this->e_MarginBottom = $this->numStr($entorno->e_MarginBottom ?? 0);
        $this->e_MarginLeft = $this->numStr($entorno->e_MarginLeft ?? 2);
        $this->e_MarginRight = $this->numStr($entorno->e_MarginRight ?? 0);
        $this->e_FontLinea1 = (string) max(1, (int) ($entorno->e_FontLinea1 ?? 18));
        $this->e_FontLinea2 = (string) max(1, (int) ($entorno->e_FontLinea2 ?? 12));
        $this->e_FontLinea3 = (string) max(1, (int) ($entorno->e_FontLinea3 ?? 11));
        $this->e_FontLinea4 = (string) max(1, (int) ($entorno->e_FontLinea4 ?? 8));
        $this->e_MaxLargoLinea2 = (string) max(1, (int) ($entorno->e_MaxLargoLinea2 ?? 21));
        $this->e_MaxLargoLinea3 = (string) max(1, (int) ($entorno->e_MaxLargoLinea3 ?? 25));
        $this->e_Borde = (bool) (int) ($entorno->e_Borde ?? 0);
    }

    private function numStr(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '0';
        }

        $n = (float) $valor;
        if (abs($n - (int) $n) < 0.00001) {
            return (string) (int) $n;
        }

        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }

    private function cargarRutaArchivo(Entorno $entorno, string $campo): ?string
    {
        $original = trim((string) ($entorno->{$campo} ?? ''));
        if ($original === '') {
            return null;
        }

        $normalizada = EntornoArchivos::normalizarRutaLegacy($original);
        if ($normalizada !== null && $normalizada !== $original) {
            $entorno->update([$campo => $normalizada]);
        }

        return $normalizada;
    }

    private function normalizarColor(string $color): string
    {
        $color = trim($color);
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1) {
            return strtoupper($color);
        }

        return '#0EA5E9';
    }

    public function rules(): array
    {
        $rules = [
            'direLabo' => ['nullable', 'string', 'max:255'],
            'teleLabo' => ['nullable', 'string', 'max:80'],
            'emailLabo' => ['nullable', 'email', 'max:120'],
            'colorInforme' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'texto1footerIzq' => ['nullable', 'string', 'max:255'],
            'texto2footerIzq' => ['nullable', 'string', 'max:255'],
            'texto1footerCentro' => ['nullable', 'string', 'max:255'],
            'texto2footerCentro' => ['nullable', 'string', 'max:255'],
            'texto1footerDer' => ['nullable', 'string', 'max:255'],
            'texto2footerDer' => ['nullable', 'string', 'max:255'],
            'ctaEnvioMail' => ['nullable', 'string', 'max:120'],
            'passEnvioMail' => ['nullable', 'string', 'max:255'],
            'fromMail' => ['nullable', 'string', 'max:120'],
            'nombrePieMail' => ['nullable', 'string', 'max:120'],
            'direccionPieMail' => ['nullable', 'string', 'max:255'],
            'telefonoPieMail' => ['nullable', 'string', 'max:80'],
            'emailPieMail' => ['nullable', 'string', 'max:120'],
            'listaPreciosUpload' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'logoUpload' => ['nullable', 'image', 'max:2048'],
            'firmaIzqUpload' => ['nullable', 'image', 'max:1024'],
            'firmaCentroUpload' => ['nullable', 'image', 'max:1024'],
            'firmaDerUpload' => ['nullable', 'image', 'max:1024'],
        ];

        if ($this->tieneCamposHeaderFooter) {
            $rules['headerInformeUpload'] = ['nullable', 'image', 'max:4096'];
            $rules['footerInformeUpload'] = ['nullable', 'image', 'max:4096'];
        }

        if ($this->tieneCamposEtiquetas) {
            $rules = array_merge($rules, [
                'e_AnchoPapel' => ['required', 'numeric', 'min:10', 'max:300'],
                'e_AnchoEtiq' => ['required', 'numeric', 'min:5', 'max:200'],
                'e_AltoEtiq' => ['required', 'numeric', 'min:5', 'max:200'],
                'e_CantCol' => ['required', 'integer', 'min:1', 'max:10'],
                'e_GapX' => ['required', 'numeric', 'min:0', 'max:50'],
                'e_GapY' => ['required', 'numeric', 'min:0', 'max:50'],
                'e_MarginTop' => ['required', 'numeric', 'min:0', 'max:50'],
                'e_MarginBottom' => ['required', 'numeric', 'min:0', 'max:50'],
                'e_MarginLeft' => ['required', 'numeric', 'min:0', 'max:50'],
                'e_MarginRight' => ['required', 'numeric', 'min:0', 'max:50'],
                'e_FontLinea1' => ['required', 'integer', 'min:4', 'max:48'],
                'e_FontLinea2' => ['required', 'integer', 'min:4', 'max:48'],
                'e_FontLinea3' => ['required', 'integer', 'min:4', 'max:48'],
                'e_FontLinea4' => ['required', 'integer', 'min:4', 'max:48'],
                'e_MaxLargoLinea2' => ['required', 'integer', 'min:1', 'max:80'],
                'e_MaxLargoLinea3' => ['required', 'integer', 'min:1', 'max:80'],
                'e_Borde' => ['boolean'],
            ]);
        }

        if ($this->tieneCampoAfipFormato) {
            $rules['afipFormatoImpresion'] = ['required', 'in:A4,termica80'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'colorInforme.regex' => 'El color del informe debe ser un código hexadecimal válido (#RRGGBB).',
            'emailLabo.email' => 'El email del laboratorio no es válido.',
            'listaPreciosUpload.mimes' => 'La lista de precios debe ser un archivo PDF.',
            'listaPreciosUpload.max' => 'La lista de precios no puede superar 10 MB.',
            'logoUpload.image' => 'El logo debe ser una imagen.',
            'logoUpload.max' => 'El logo no puede superar 2 MB.',
            'headerInformeUpload.image' => 'El encabezado del informe debe ser una imagen.',
            'headerInformeUpload.max' => 'El encabezado del informe no puede superar 4 MB.',
            'footerInformeUpload.image' => 'El pie del informe debe ser una imagen.',
            'footerInformeUpload.max' => 'El pie del informe no puede superar 4 MB.',
            'firmaIzqUpload.image' => 'La firma izquierda debe ser una imagen.',
            'firmaCentroUpload.image' => 'La firma central debe ser una imagen.',
            'firmaDerUpload.image' => 'La firma derecha debe ser una imagen.',
            'firmaIzqUpload.max' => 'Cada firma no puede superar 1 MB.',
            'firmaCentroUpload.max' => 'Cada firma no puede superar 1 MB.',
            'firmaDerUpload.max' => 'Cada firma no puede superar 1 MB.',
            'e_AnchoPapel.required' => 'Indique el ancho del papel.',
            'e_AnchoEtiq.required' => 'Indique el ancho de la etiqueta.',
            'e_AltoEtiq.required' => 'Indique el alto de la etiqueta.',
            'e_CantCol.required' => 'Indique la cantidad de columnas.',
        ];
    }

    public function save(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $key = 'entorno-parametros-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 20), 429);

        $this->tieneCamposEtiquetas = Schema::hasColumn('entorno', 'e_AnchoPapel');
        $this->tieneCampoAfipFormato = Schema::hasColumn('entorno', 'afipFormatoImpresion');
        $this->tieneCamposHeaderFooter = Schema::hasColumn('entorno', 'headerInforme')
            && Schema::hasColumn('entorno', 'footerInforme');

        if (
            (! $this->tieneCamposHeaderFooter)
            && ($this->headerInformeUpload !== null || $this->footerInformeUpload !== null)
        ) {
            $this->dispatch(
                'vl-swal-error',
                mensaje: 'Faltan las columnas headerInforme/footerInforme en entorno. Ejecutá el SQL de database/sql/entorno_header_footer_informe.sql o la migración correspondiente.'
            );

            return;
        }

        $data = $this->validate();
        $entorno = Entorno::query()->orderBy('id')->first();
        abort_if($entorno === null, 404);

        $payload = [
            'direLabo' => trim((string) ($data['direLabo'] ?? '')),
            'teleLabo' => trim((string) ($data['teleLabo'] ?? '')),
            'emailLabo' => trim((string) ($data['emailLabo'] ?? '')),
            'colorInforme' => strtoupper((string) $data['colorInforme']),
            'texto1footerIzq' => trim((string) ($data['texto1footerIzq'] ?? '')),
            'texto2footerIzq' => trim((string) ($data['texto2footerIzq'] ?? '')),
            'texto1footerCentro' => trim((string) ($data['texto1footerCentro'] ?? '')),
            'texto2footerCentro' => trim((string) ($data['texto2footerCentro'] ?? '')),
            'texto1footerDer' => trim((string) ($data['texto1footerDer'] ?? '')),
            'texto2footerDer' => trim((string) ($data['texto2footerDer'] ?? '')),
            'ctaEnvioMail' => trim((string) ($data['ctaEnvioMail'] ?? '')),
            'fromMail' => trim((string) ($data['fromMail'] ?? '')),
            'nombrePieMail' => trim((string) ($data['nombrePieMail'] ?? '')),
            'direccionPieMail' => trim((string) ($data['direccionPieMail'] ?? '')),
            'telefonoPieMail' => trim((string) ($data['telefonoPieMail'] ?? '')),
            'emailPieMail' => trim((string) ($data['emailPieMail'] ?? '')),
        ];

        if ($this->tieneCamposEtiquetas) {
            $payload = array_merge($payload, [
                'e_AnchoPapel' => round((float) $data['e_AnchoPapel'], 2),
                'e_AnchoEtiq' => round((float) $data['e_AnchoEtiq'], 2),
                'e_AltoEtiq' => round((float) $data['e_AltoEtiq'], 2),
                'e_CantCol' => (int) $data['e_CantCol'],
                'e_GapX' => round((float) $data['e_GapX'], 2),
                'e_GapY' => round((float) $data['e_GapY'], 2),
                'e_MarginTop' => round((float) $data['e_MarginTop'], 2),
                'e_MarginBottom' => round((float) $data['e_MarginBottom'], 2),
                'e_MarginLeft' => round((float) $data['e_MarginLeft'], 2),
                'e_MarginRight' => round((float) $data['e_MarginRight'], 2),
                'e_FontLinea1' => (int) $data['e_FontLinea1'],
                'e_FontLinea2' => (int) $data['e_FontLinea2'],
                'e_FontLinea3' => (int) $data['e_FontLinea3'],
                'e_FontLinea4' => (int) $data['e_FontLinea4'],
                'e_MaxLargoLinea2' => (int) $data['e_MaxLargoLinea2'],
                'e_MaxLargoLinea3' => (int) $data['e_MaxLargoLinea3'],
                'e_Borde' => ! empty($data['e_Borde']) ? 1 : 0,
            ]);
        }

        if ($this->tieneCampoAfipFormato) {
            $payload['afipFormatoImpresion'] = (string) $data['afipFormatoImpresion'];
        }

        $passNueva = trim((string) ($data['passEnvioMail'] ?? ''));
        if ($passNueva !== '') {
            $payload['passEnvioMail'] = $passNueva;
        }

        if ($this->listaPreciosUpload !== null) {
            $payload['listaPreciosPdf'] = EntornoArchivos::guardarPdf(
                $this->listaPreciosUpload,
                EntornoArchivos::directorioListaPrecios(),
                'lista-precios'
            );
            $this->listaPreciosPdfActual = $payload['listaPreciosPdf'];
            $this->listaPreciosUpload = null;
        }

        if ($this->logoUpload !== null) {
            $payload['logo'] = EntornoArchivos::guardarImagen(
                $this->logoUpload,
                EntornoArchivos::directorioLogo(),
                'logo'
            );
            $this->logoActual = $payload['logo'];
            $this->logoUpload = null;
        }

        if ($this->tieneCamposHeaderFooter && $this->headerInformeUpload !== null) {
            $payload['headerInforme'] = EntornoArchivos::guardarImagen(
                $this->headerInformeUpload,
                EntornoArchivos::directorioLogo(),
                'header-informe'
            );
            $this->headerInformeActual = $payload['headerInforme'];
            $this->headerInformeUpload = null;
        }

        if ($this->tieneCamposHeaderFooter && $this->footerInformeUpload !== null) {
            $payload['footerInforme'] = EntornoArchivos::guardarImagen(
                $this->footerInformeUpload,
                EntornoArchivos::directorioLogo(),
                'footer-informe'
            );
            $this->footerInformeActual = $payload['footerInforme'];
            $this->footerInformeUpload = null;
        }

        if ($this->firmaIzqUpload !== null) {
            $payload['firmaIzq'] = EntornoArchivos::guardarImagen(
                $this->firmaIzqUpload,
                EntornoArchivos::directorioFirmas(),
                'firma-izq'
            );
            $this->firmaIzqActual = $payload['firmaIzq'];
            $this->firmaIzqUpload = null;
        }

        if ($this->firmaCentroUpload !== null) {
            $payload['firmaCentro'] = EntornoArchivos::guardarImagen(
                $this->firmaCentroUpload,
                EntornoArchivos::directorioFirmas(),
                'firma-centro'
            );
            $this->firmaCentroActual = $payload['firmaCentro'];
            $this->firmaCentroUpload = null;
        }

        if ($this->firmaDerUpload !== null) {
            $payload['firmaDer'] = EntornoArchivos::guardarImagen(
                $this->firmaDerUpload,
                EntornoArchivos::directorioFirmas(),
                'firma-der'
            );
            $this->firmaDerActual = $payload['firmaDer'];
            $this->firmaDerUpload = null;
        }

        $entorno->update($payload);
        $entorno->refresh();

        $this->listaPreciosPdfActual = $this->cargarRutaArchivo($entorno, 'listaPreciosPdf');
        $this->logoActual = $this->cargarRutaArchivo($entorno, 'logo');
        $this->firmaIzqActual = $this->cargarRutaArchivo($entorno, 'firmaIzq');
        $this->firmaCentroActual = $this->cargarRutaArchivo($entorno, 'firmaCentro');
        $this->firmaDerActual = $this->cargarRutaArchivo($entorno, 'firmaDer');
        $this->cargarCamposHeaderFooter($entorno);
        $this->cargarCamposEtiquetas($entorno);
        $this->cargarCampoAfipFormato($entorno);

        if ($passNueva !== '') {
            $this->passEnvioMail = '';
            $this->tienePassEnvioMail = true;
        }

        RateLimiter::hit($key, 60);
        $this->dispatch('vl-swal-exito', mensaje: 'Parámetros del sistema actualizados correctamente.');
    }

    public function quitarHeaderInforme(): void
    {
        $this->quitarImagenInforme('headerInforme', 'headerInformeActual', 'Encabezado del informe quitado. Se usará el membrete con logo y datos.');
    }

    public function quitarFooterInforme(): void
    {
        $this->quitarImagenInforme('footerInforme', 'footerInformeActual', 'Pie del informe quitado. Se usarán firmas y textos del pie.');
    }

    private function quitarImagenInforme(string $campo, string $propActual, string $mensajeOk): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $key = 'entorno-parametros-quitar-img:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 20), 429);

        if (! Schema::hasColumn('entorno', $campo)) {
            $this->dispatch(
                'vl-swal-error',
                mensaje: 'Falta la columna '.$campo.' en entorno. Ejecutá el SQL de database/sql/entorno_header_footer_informe.sql.'
            );

            return;
        }

        $entorno = Entorno::query()->orderBy('id')->first();
        abort_if($entorno === null, 404);

        $ruta = $this->cargarRutaArchivo($entorno, $campo);
        $entorno->update([$campo => null]);
        $this->{$propActual} = null;

        if ($ruta !== null) {
            $absoluta = EntornoArchivos::rutaAbsoluta($ruta);
            if ($absoluta !== null && is_file($absoluta)) {
                @unlink($absoluta);
            }
        }

        RateLimiter::hit($key, 60);
        $this->dispatch('vl-swal-exito', mensaje: $mensajeOk);
    }

    public function render()
    {
        $logoPreviewUrl = $this->previewTemporal($this->logoUpload);
        $headerInformePreviewUrl = $this->previewTemporal($this->headerInformeUpload);
        $footerInformePreviewUrl = $this->previewTemporal($this->footerInformeUpload);

        return view('livewire.admin.entorno-form', [
            'listaPreciosUrl' => EntornoArchivos::urlPublica($this->listaPreciosPdfActual),
            'logoPreviewUrl' => $logoPreviewUrl,
            'logoUrl' => EntornoArchivos::urlPublica($this->logoActual, cacheBust: true),
            'headerInformePreviewUrl' => $headerInformePreviewUrl,
            'headerInformeUrl' => EntornoArchivos::urlPublica($this->headerInformeActual, cacheBust: true),
            'footerInformePreviewUrl' => $footerInformePreviewUrl,
            'footerInformeUrl' => EntornoArchivos::urlPublica($this->footerInformeActual, cacheBust: true),
            'firmaIzqUrl' => EntornoArchivos::urlPublica($this->firmaIzqActual),
            'firmaCentroUrl' => EntornoArchivos::urlPublica($this->firmaCentroActual),
            'firmaDerUrl' => EntornoArchivos::urlPublica($this->firmaDerActual),
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function previewTemporal(mixed $upload): ?string
    {
        if ($upload === null) {
            return null;
        }

        try {
            return $upload->temporaryUrl();
        } catch (\Throwable) {
            return null;
        }
    }
}
