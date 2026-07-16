<?php

namespace App\Livewire\Protocolos;

use App\Models\Determinacion;
use App\Support\PermisosIaCatalog;
use App\Support\Resultados\InformeVisibilidadConsulta;
use App\Support\Resultados\ResultadosEstadosCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

class DerivacionIndex extends PacienteIndex
{
    public const AGRUPACION_NINGUNA = 'ninguna';

    public const AGRUPACION_CENTRO = 'centro';

    public const AGRUPACION_CLIENTE = 'cliente';

    public const AGRUPACION_FECHA = 'fecha';

    public string $agrupacion = self::AGRUPACION_CENTRO;

    public bool $incluirFinalizados = false;

    public function updatingAgrupacion(): void
    {
        $this->resetPage();
    }

    public function toggleIncluirFinalizados(): void
    {
        $this->incluirFinalizados = ! $this->incluirFinalizados;
        $this->resetPage();
    }

    public function actualizarFechaEnvioDeriv(int $idDeterminaciones, ?string $fecha = null): void
    {
        $this->guardarFechaDerivacion($idDeterminaciones, 'fechaEnvioDeriv', $fecha);
    }

    public function actualizarFechaDevolucDeterm(int $idDeterminaciones, ?string $fecha = null): void
    {
        $this->guardarFechaDerivacion($idDeterminaciones, 'fechaDevolucDeterm', $fecha);
    }

    public function render()
    {
        abort_unless(Schema::hasTable('determinaciones'), 404, 'La tabla de determinaciones no está disponible.');

        $term = trim($this->busqueda);
        $ctx = labCtx();
        $tieneFechas = $this->tieneColumnasFechasDerivacion();

        $pacienteWith = ['cliente', 'especie', 'raza'];
        if (Schema::hasTable('notificaciones')) {
            $pacienteWith[] = 'notificacion';
        }

        $query = Determinacion::query()
            ->select('determinaciones.*')
            ->where('determinaciones.idDerivaciones', '>', 0)
            ->when(! $this->incluirFinalizados, function ($q) {
                $q->whereNotIn('pacientes.estado', ResultadosEstadosCatalog::estadosFinalizados());
            })
            ->with([
                'paciente' => fn ($q) => $q->with($pacienteWith),
                'cliente',
                'tipodeterminacion',
                'derivacion',
            ])
            ->join('pacientes', 'pacientes.idPacientes', '=', 'determinaciones.idPacientes')
            ->when($ctx->esCliente() && $ctx->idClientes, function ($q) use ($ctx) {
                $q->where('determinaciones.idClientes', $ctx->idClientes);
            })
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('pacientes.nombreProtocolo', 'like', "%{$term}%")
                        ->orWhere('pacientes.nombre', 'like', "%{$term}%")
                        ->orWhere('pacientes.propietario', 'like', "%{$term}%")
                        ->orWhere('pacientes.email', 'like', "%{$term}%")
                        ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$term}%"))
                        ->orWhereHas('tipodeterminacion', fn ($t) => $t->where('nombre', 'like', "%{$term}%"))
                        ->orWhereHas('derivacion', fn ($d) => $d->where('derivacion', 'like', "%{$term}%"));
                });
            });

        if ($this->agrupacion === self::AGRUPACION_CENTRO) {
            $query->leftJoin('derivaciones', 'derivaciones.idDerivaciones', '=', 'determinaciones.idDerivaciones')
                ->orderBy('derivaciones.derivacion')
                ->orderByDesc('pacientes.fechhoy')
                ->orderBy('pacientes.nombreProtocolo');
        } elseif ($this->agrupacion === self::AGRUPACION_CLIENTE) {
            $query->leftJoin('clientes', 'clientes.idClientes', '=', 'determinaciones.idClientes')
                ->orderBy('clientes.nombre')
                ->orderByDesc('pacientes.fechhoy')
                ->orderBy('pacientes.nombreProtocolo');
        } elseif ($this->agrupacion === self::AGRUPACION_FECHA) {
            $query->orderByDesc('pacientes.fechhoy')
                ->orderBy('pacientes.nombreProtocolo');
        } else {
            $query->orderByDesc('pacientes.fechhoy')
                ->orderBy('pacientes.nombreProtocolo');
        }

        $registros = $query->paginate(self::POR_PAGINA);

        $edInfRenglones = [];
        if ($this->modalEdInfAbierto && $this->edInfIdPaciente !== null) {
            $pacienteEdInf = $this->pacienteEnAlcance($this->edInfIdPaciente);
            if ($pacienteEdInf !== null) {
                $edInfRenglones = (new InformeVisibilidadConsulta)->listar($pacienteEdInf);
            }
        }

        return view('livewire.protocolos.derivacion-index', [
            'registros' => $registros,
            'edInfRenglones' => $edInfRenglones,
            'tieneFechasDerivacion' => $tieneFechas,
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function guardarFechaDerivacion(int $idDeterminaciones, string $campo, ?string $fecha): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);
        abort_unless(in_array($campo, ['fechaEnvioDeriv', 'fechaDevolucDeterm'], true), 400);

        if (! Schema::hasColumn('determinaciones', $campo)) {
            $this->dispatch('vl-swal-error', mensaje: 'La columna de fecha aún no está disponible en la base de datos.');

            return;
        }

        $uid = labCtx()->idUsuarios ?? 0;
        $key = 'derivaciones-fecha:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($key, 60), 429);

        $fecha = $fecha !== null ? trim($fecha) : '';
        if ($fecha === '') {
            $valor = null;
        } else {
            validator(
                ['fecha' => $fecha],
                ['fecha' => ['required', 'date']],
                ['fecha.date' => 'La fecha no es válida.']
            )->validate();
            $valor = $fecha;
        }

        $registro = $this->determinacionEnAlcance($idDeterminaciones);
        if ($registro === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontró la determinación derivada.');

            return;
        }

        RateLimiter::hit($key, 60);
        $registro->update([$campo => $valor]);
    }

    private function determinacionEnAlcance(int $idDeterminaciones): ?Determinacion
    {
        $ctx = labCtx();

        return Determinacion::query()
            ->where('determinaciones.idDeterminaciones', $idDeterminaciones)
            ->where('determinaciones.idDerivaciones', '>', 0)
            ->when($ctx->esCliente() && $ctx->idClientes, function ($q) use ($ctx) {
                $q->where('determinaciones.idClientes', $ctx->idClientes);
            })
            ->first();
    }

    private function tieneColumnasFechasDerivacion(): bool
    {
        return Schema::hasColumn('determinaciones', 'fechaEnvioDeriv')
            && Schema::hasColumn('determinaciones', 'fechaDevolucDeterm');
    }
}
