<?php

namespace App\Livewire\Protocolos;

use App\Models\Entorno;
use App\Models\Paciente;
use App\Support\Autoanalizadores\AutoanalizadorCarpeta;
use App\Support\Autoanalizadores\AutoanalizadorConfig;
use App\Support\Autoanalizadores\AutoanalizadorImportador;
use App\Support\PermisosIaCatalog;
use App\Support\Protocolos\PacienteListadoFiltros;
use App\Support\Resultados\RenglonesMaterializer;
use App\Support\Resultados\ResultadosCargaConsulta;
use App\Support\Resultados\ResultadosEstadosCatalog;
use App\Support\Resultados\ResultadosGuardarServicio;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;
use Throwable;

class PacienteResultados extends Component
{
    use WithFileUploads;

    public int $idPacientes;

    public string $estadoPaciente = '';

    public string $origen = 'pacientes';

    /** @var array{vista?: string, filtroEstado?: string, page?: int} */
    public array $listadoFiltros = [];

    public bool $modalAutoanalizadorAbierto = false;

    public string $aparatoSeleccionado = '';

    public string $archivoSeleccionado = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $archivoCsv = null;

    /** @var list<array{clave: string, etiqueta: string}> */
    public array $aparatosDisponibles = [];

    /** @var list<array{nombre: string, mtime: int, bytes: int}> */
    public array $archivosRecientes = [];

    public function mount(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::RESULTADOS), 403);
        abort_unless(Schema::hasTable('renglones'), 404, 'La tabla de resultados no está disponible.');

        $paciente = $this->pacienteEnAlcance($id);
        abort_if($paciente->esPagoGlobal(), 404);
        $this->idPacientes = $paciente->idPacientes;

        $origen = request()->query('origen', 'pacientes');
        $this->origen = in_array($origen, ['pacientes', 'dia', 'derivaciones'], true)
            ? $origen
            : 'pacientes';

        $this->listadoFiltros = PacienteListadoFiltros::desdeRequest();

        (new RenglonesMaterializer)->asegurarParaPaciente($paciente);

        $this->estadoPaciente = ResultadosEstadosCatalog::normalizar($paciente->estado);
    }

    /**
     * @param  array<string, string|null>  $valores
     * @param  array<string, string|null>  $valores2
     */
    public function guardar(array $valores, array $valores2, string $estadoPaciente, bool $salir = false): mixed
    {
        abort_unless(tienePermiso(PermisosIaCatalog::RESULTADOS), 403);

        $key = 'prot-resultados-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);
        RateLimiter::hit($key, 60);

        $paciente = $this->pacienteEnAlcance($this->idPacientes);

        try {
            (new ResultadosGuardarServicio)->guardar(
                $paciente,
                $valores,
                $valores2,
                $estadoPaciente !== '' ? $estadoPaciente : null
            );
        } catch (ValidationException $e) {
            $mensaje = collect($e->errors())->flatten()->first() ?: 'No se pudo guardar.';
            $this->dispatch('vl-swal-error', mensaje: $mensaje);

            return null;
        }

        $this->estadoPaciente = $estadoPaciente;

        if ($salir) {
            return $this->redirect($this->urlVolver(), navigate: true);
        }

        return null;
    }

    public function abrirModalAutoanalizador(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::RESULTADOS), 403);

        if (! AutoanalizadorConfig::hayAparatosActivos()) {
            $this->dispatch('vl-swal-error', mensaje: 'No hay autoanalizadores configurados para este laboratorio.');

            return;
        }

        $this->refrescarListaAparatosYArchivos();
        $this->archivoCsv = null;
        $this->modalAutoanalizadorAbierto = true;
    }

    public function cerrarModalAutoanalizador(): void
    {
        $this->modalAutoanalizadorAbierto = false;
        $this->archivoCsv = null;
        $this->resetValidation();
    }

    public function updatedArchivoCsv(): void
    {
        if ($this->archivoCsv === null) {
            return;
        }

        abort_unless(tienePermiso(PermisosIaCatalog::RESULTADOS), 403);

        $key = 'autoanalizador-up:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 20), 429);
        RateLimiter::hit($key, 60);

        $this->validate([
            'archivoCsv' => [
                'required',
                'file',
                'max:'.AutoanalizadorCarpeta::MAX_KB,
            ],
        ], [
            'archivoCsv.required' => 'Seleccione un archivo CSV.',
            'archivoCsv.max' => 'El archivo no puede superar 10 MB.',
        ]);

        $ext = strtolower((string) $this->archivoCsv->getClientOriginalExtension());
        if (! in_array($ext, AutoanalizadorCarpeta::EXTENSIONES, true)) {
            $this->archivoCsv = null;
            $this->addError('archivoCsv', 'Solo se permiten archivos CSV, TXT o SHD.');

            return;
        }

        try {
            $nombre = (new AutoanalizadorCarpeta)->guardarUpload($this->archivoCsv);
            $this->archivoCsv = null;
            $this->refrescarListaAparatosYArchivos();
            $this->archivoSeleccionado = $nombre;
            $this->dispatch('vl-swal-exito', mensaje: 'Archivo guardado en AUTOANALIZADORES.');
        } catch (Throwable $e) {
            $this->archivoCsv = null;
            $this->dispatch('vl-swal-error', mensaje: $e->getMessage());
        }
    }

    public function importarDesdeAutoanalizador(): mixed
    {
        abort_unless(tienePermiso(PermisosIaCatalog::RESULTADOS), 403);

        $key = 'autoanalizador-imp:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);
        RateLimiter::hit($key, 60);

        $this->validate([
            'aparatoSeleccionado' => ['required', 'string', 'max:80'],
            'archivoSeleccionado' => ['required', 'string', 'max:255'],
        ], [
            'aparatoSeleccionado.required' => 'Seleccione el aparato.',
            'archivoSeleccionado.required' => 'Seleccione un archivo CSV.',
        ]);

        $paciente = $this->pacienteEnAlcance($this->idPacientes);

        try {
            $resultado = (new AutoanalizadorImportador)->importar(
                $paciente,
                $this->aparatoSeleccionado,
                $this->archivoSeleccionado
            );
        } catch (RuntimeException $e) {
            $this->dispatch('vl-swal-error', mensaje: $e->getMessage());

            return null;
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('vl-swal-error', mensaje: 'No se pudo importar el archivo.');

            return null;
        }

        $this->modalAutoanalizadorAbierto = false;
        $this->archivoCsv = null;

        $n = (int) $resultado['actualizados'];
        session()->flash(
            'vl_mensaje_exito',
            $n === 1
                ? 'Se importó 1 valor desde el autoanalizador.'
                : "Se importaron {$n} valores desde el autoanalizador."
        );

        return $this->redirect(
            route('protocolos.resultados', array_merge([
                'id' => $this->idPacientes,
                'origen' => $this->origen,
            ], $this->listadoFiltros)),
            navigate: false
        );
    }

    public function render()
    {
        $paciente = $this->pacienteEnAlcance($this->idPacientes)
            ->loadMissing(['cliente', 'especie', 'raza']);

        $entorno = Entorno::query()->orderBy('id')->first();

        return view('livewire.protocolos.paciente-resultados', [
            'pacienteResumen' => [
                'nombre' => (string) ($paciente->nombre ?: '—'),
                'protocolo' => (string) ($paciente->nombreProtocolo ?: '—'),
                'veterinaria' => (string) ($paciente->cliente?->nombre ?: '—'),
                'especie' => (string) ($paciente->especie?->nombre ?: '—'),
                'raza' => (string) ($paciente->raza?->nombre ?: '—'),
                'sexo' => (string) ($paciente->sexo ?: '—'),
                'edad' => (string) ($paciente->edad ?: '—'),
            ],
            'grupos' => (new ResultadosCargaConsulta)->gruposConRenglones($paciente),
            'estados' => ResultadosEstadosCatalog::valores(),
            'urlVolver' => $this->urlVolver(),
            'formulasJs' => $this->normalizarFormulas((string) ($entorno?->formulas ?? '')),
            'autoanalizadoresDisponibles' => AutoanalizadorConfig::hayAparatosActivos(),
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function refrescarListaAparatosYArchivos(): void
    {
        $this->aparatosDisponibles = array_map(
            fn (array $a): array => ['clave' => $a['clave'], 'etiqueta' => $a['etiqueta']],
            AutoanalizadorConfig::aparatosActivos()
        );

        if ($this->aparatoSeleccionado === '' && $this->aparatosDisponibles !== []) {
            $this->aparatoSeleccionado = $this->aparatosDisponibles[0]['clave'];
        }

        $this->archivosRecientes = (new AutoanalizadorCarpeta)->listarRecientes();

        if ($this->archivoSeleccionado !== '') {
            $nombres = array_column($this->archivosRecientes, 'nombre');
            if (! in_array($this->archivoSeleccionado, $nombres, true)) {
                $this->archivoSeleccionado = '';
            }
        }

        if ($this->archivoSeleccionado === '' && $this->archivosRecientes !== []) {
            $this->archivoSeleccionado = $this->archivosRecientes[0]['nombre'];
        }
    }

    private function normalizarFormulas(string $codigo): string
    {
        if (preg_match('~<script\b[^>]*>([\s\S]*?)</script>~i', $codigo, $m) === 1) {
            return trim((string) ($m[1] ?? ''));
        }

        return trim($codigo);
    }

    private function urlVolver(): string
    {
        return match ($this->origen) {
            'derivaciones' => route('derivaciones.index'),
            default => PacienteListadoFiltros::urlIndex($this->listadoFiltros, $this->idPacientes),
        };
    }

    private function pacienteEnAlcance(int $id): Paciente
    {
        $ctx = labCtx();

        return Paciente::query()
            ->when($ctx->esCliente() && $ctx->idClientes, fn ($q) => $q->where('idClientes', $ctx->idClientes))
            ->where('idPacientes', $id)
            ->firstOrFail();
    }
}
