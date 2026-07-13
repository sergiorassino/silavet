<?php

namespace App\Livewire\Protocolos;

use App\Models\Imagenxrenglon;
use App\Models\Renglon;
use App\Support\PermisosIaCatalog;
use App\Support\Resultados\RenglonImagenesStorage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class RenglonImagenes extends Component
{
    use WithFileUploads;

    public int $idRenglones;

    public int $idPacientes;

    public string $nombreItem = '';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public $archivos = [];

    /** @var array<int, array{id: int, nombreImagen: string, observacion: string, url: string|null}> */
    public array $imagenes = [];

    /** @var array<string, string> */
    public array $observaciones = [];

    public function mount(int $idRenglones, int $idPacientes, string $nombreItem = ''): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::RESULTADOS), 403);

        $this->idRenglones = $idRenglones;
        $this->idPacientes = $idPacientes;
        $this->nombreItem = $nombreItem;
        $this->asegurarRenglonEnAlcance();
        $this->sincronizarImagenes();
    }

    public function updatedArchivos(): void
    {
        if ($this->archivos === [] || $this->archivos === null) {
            return;
        }

        $this->subir();
    }

    public function subir(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::RESULTADOS), 403);
        abort_unless(Schema::hasTable('imagenesxrenglon'), 404, 'La tabla de imágenes no está disponible.');

        $key = 'renglon-img-up:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 20), 429);
        RateLimiter::hit($key, 60);

        $this->asegurarRenglonEnAlcance();

        $this->validate([
            'archivos' => ['required', 'array', 'min:1', 'max:10'],
            'archivos.*' => [
                'file',
                'max:'.RenglonImagenesStorage::MAX_KB,
                'mimes:'.implode(',', RenglonImagenesStorage::EXTENSIONES),
            ],
        ], [
            'archivos.required' => 'Seleccione al menos una imagen.',
            'archivos.*.mimes' => 'Solo se permiten JPG, PNG, GIF o WEBP.',
            'archivos.*.max' => 'Cada imagen no puede superar 5 MB.',
        ]);

        try {
            foreach ($this->archivos as $archivo) {
                $nombre = RenglonImagenesStorage::guardar($archivo);
                Imagenxrenglon::query()->create([
                    'idRenglones' => $this->idRenglones,
                    'nombreImagen' => $nombre,
                    'observacion' => '',
                ]);
            }
        } catch (ValidationException $e) {
            $mensaje = collect($e->errors())->flatten()->first() ?: 'No se pudo subir la imagen.';
            $this->dispatch('vl-swal-error', mensaje: $mensaje);
            $this->archivos = [];

            return;
        }

        $this->archivos = [];
        $this->sincronizarImagenes();
    }

    public function guardarObservacion(int $idImagen): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::RESULTADOS), 403);

        $key = 'renglon-img-obs:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 40), 429);
        RateLimiter::hit($key, 60);

        $this->asegurarRenglonEnAlcance();

        $obs = (string) ($this->observaciones[(string) $idImagen] ?? '');

        $registro = Imagenxrenglon::query()
            ->where('idRenglones', $this->idRenglones)
            ->whereKey($idImagen)
            ->firstOrFail();

        $registro->update(['observacion' => $obs]);
        $this->sincronizarImagenes();
    }

    public function eliminar(int $idImagen): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::RESULTADOS), 403);

        $key = 'renglon-img-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 20), 429);
        RateLimiter::hit($key, 60);

        $this->asegurarRenglonEnAlcance();

        $registro = Imagenxrenglon::query()
            ->where('idRenglones', $this->idRenglones)
            ->whereKey($idImagen)
            ->firstOrFail();

        $nombre = (string) $registro->nombreImagen;
        $registro->delete();
        RenglonImagenesStorage::eliminarArchivo($nombre);

        $this->sincronizarImagenes();
    }

    public function render()
    {
        return view('livewire.protocolos.renglon-imagenes');
    }

    private function sincronizarImagenes(): void
    {
        if (! Schema::hasTable('imagenesxrenglon')) {
            $this->imagenes = [];
            $this->observaciones = [];

            return;
        }

        $this->imagenes = Imagenxrenglon::query()
            ->where('idRenglones', $this->idRenglones)
            ->orderByDesc('id')
            ->get(['id', 'nombreImagen', 'observacion'])
            ->map(fn (Imagenxrenglon $img) => [
                'id' => (int) $img->id,
                'nombreImagen' => (string) $img->nombreImagen,
                'observacion' => (string) ($img->observacion ?? ''),
                'url' => RenglonImagenesStorage::urlPublica((string) $img->nombreImagen),
            ])
            ->all();

        $this->observaciones = collect($this->imagenes)
            ->mapWithKeys(fn (array $img) => [(string) $img['id'] => $img['observacion']])
            ->all();
    }

    private function asegurarRenglonEnAlcance(): void
    {
        $ctx = labCtx();

        $query = Renglon::query()
            ->where('idRenglones', $this->idRenglones)
            ->where('idPacientes', $this->idPacientes)
            ->where('tipoItem', 10);

        if ($ctx->esCliente() && $ctx->idClientes) {
            $query->where('idClientes', $ctx->idClientes);
        }

        abort_unless($query->exists(), 404);
    }
}
