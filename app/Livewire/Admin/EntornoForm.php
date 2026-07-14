<?php

namespace App\Livewire\Admin;

use App\Models\Entorno;
use App\Support\Entorno\EntornoArchivos;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
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

    public ?string $firmaIzqActual = null;

    public ?string $firmaCentroActual = null;

    public ?string $firmaDerActual = null;

    public bool $tienePassEnvioMail = false;

    public ?string $listaPreciosPdfActual = null;

    public $listaPreciosUpload = null;

    public $logoUpload = null;

    public $firmaIzqUpload = null;

    public $firmaCentroUpload = null;

    public $firmaDerUpload = null;

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
        return [
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
            'firmaIzqUpload.image' => 'La firma izquierda debe ser una imagen.',
            'firmaCentroUpload.image' => 'La firma central debe ser una imagen.',
            'firmaDerUpload.image' => 'La firma derecha debe ser una imagen.',
            'firmaIzqUpload.max' => 'Cada firma no puede superar 1 MB.',
            'firmaCentroUpload.max' => 'Cada firma no puede superar 1 MB.',
            'firmaDerUpload.max' => 'Cada firma no puede superar 1 MB.',
        ];
    }

    public function save(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PARAMETROS), 403);

        $key = 'entorno-parametros-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 20), 429);

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

        if ($passNueva !== '') {
            $this->passEnvioMail = '';
            $this->tienePassEnvioMail = true;
        }

        RateLimiter::hit($key, 60);
        $this->dispatch('vl-swal-exito', mensaje: 'Parámetros del sistema actualizados correctamente.');
    }

    public function render()
    {
        $logoPreviewUrl = null;
        if ($this->logoUpload !== null) {
            try {
                $logoPreviewUrl = $this->logoUpload->temporaryUrl();
            } catch (\Throwable) {
                $logoPreviewUrl = null;
            }
        }

        return view('livewire.admin.entorno-form', [
            'listaPreciosUrl' => EntornoArchivos::urlPublica($this->listaPreciosPdfActual),
            'logoPreviewUrl' => $logoPreviewUrl,
            'logoUrl' => EntornoArchivos::urlPublica($this->logoActual, cacheBust: true),
            'firmaIzqUrl' => EntornoArchivos::urlPublica($this->firmaIzqActual),
            'firmaCentroUrl' => EntornoArchivos::urlPublica($this->firmaCentroActual),
            'firmaDerUrl' => EntornoArchivos::urlPublica($this->firmaDerActual),
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
