<?php

namespace App\Livewire\Protocolos;

use App\Models\Entorno;
use App\Models\Paciente;
use App\Support\PermisosIaCatalog;
use App\Support\Resultados\RenglonesMaterializer;
use App\Support\Resultados\ResultadosCargaConsulta;
use App\Support\Resultados\ResultadosEstadosCatalog;
use App\Support\Resultados\ResultadosGuardarServicio;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PacienteResultados extends Component
{
    public int $idPacientes;

    public string $estadoPaciente = '';

    public string $origen = 'pacientes';

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
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
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
            default => route('protocolos.index'),
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
