<?php

namespace App\Livewire\Listados;

use App\Models\Cliente;
use App\Models\Requerimiento;
use App\Models\Reqxtipodet;
use App\Models\Tipodeterminacion;
use App\Support\PermisosIaCatalog;
use App\Support\PrecioInput;
use App\Support\Precios\DescuentoDeterminacionResolver;
use App\Support\Precios\PrecioDeterminacionResolver;
use App\Support\Requerimientos\RequerimientoHtml;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class EstimacionCostos extends Component
{
    public ?int $idClientes = null;

    public string $idTipoSeleccionar = '';

    /** @var array<int, array{idTipodeterminaciones: int, nombre: string, neto: string, descuento: string, precio: string}> */
    public array $seleccionados = [];

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);
        abort_unless(Schema::hasTable('tipodeterminaciones'), 404, 'La tabla de tipodeterminaciones no está disponible.');

        $ctx = labCtx();
        if ($ctx->esCliente() && $ctx->idClientes) {
            $this->idClientes = $ctx->idClientes;
        }
    }

    public function updatedIdClientes(mixed $value): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);

        if ($value === '' || $value === null) {
            $this->idClientes = null;
            $this->recalcularPreciosSeleccionados();

            return;
        }

        $this->idClientes = (int) $value;
        $this->assertClienteEnAlcance($this->idClientes);
        $this->recalcularPreciosSeleccionados();
    }

    public function updatedIdTipoSeleccionar(mixed $value): void
    {
        if ($value === '' || $value === null) {
            return;
        }

        $this->agregarDeterminacion((int) $value);
        $this->idTipoSeleccionar = '';
    }

    public function agregarDeterminacion(int $idTipo): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);

        if ($this->idClientes === null) {
            $this->dispatch('vl-swal-error', mensaje: 'Seleccione un cliente antes de agregar determinaciones.');

            return;
        }

        $this->assertClienteEnAlcance($this->idClientes);

        if ($this->yaSeleccionada($idTipo)) {
            $this->dispatch('vl-swal-error', mensaje: 'Esa determinación ya está en la lista.');

            return;
        }

        $tipo = Tipodeterminacion::query()->find($idTipo);
        if ($tipo === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontró la determinación seleccionada.');

            return;
        }

        $this->seleccionados[] = $this->filaDesdeTipo($tipo);
    }

    public function quitarDeterminacion(int $indice): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);

        if (! array_key_exists($indice, $this->seleccionados)) {
            return;
        }

        unset($this->seleccionados[$indice]);
        $this->seleccionados = array_values($this->seleccionados);
    }

    public function borrarTodas(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::LISTADOS_ESTADISTICOS), 403);
        $this->seleccionados = [];
        $this->idTipoSeleccionar = '';
    }

    public function render()
    {
        $ctx = labCtx();

        $clientes = Cliente::query()
            ->when($ctx->esCliente() && $ctx->idClientes, fn ($q) => $q->where('idClientes', $ctx->idClientes))
            ->orderBy('nombre')
            ->get(['idClientes', 'nombre', 'descuento']);

        $idsYa = collect($this->seleccionados)->pluck('idTipodeterminaciones')->all();

        $tiposDisponibles = Tipodeterminacion::query()
            ->when($idsYa !== [], fn ($q) => $q->whereNotIn('idTipodeterminaciones', $idsYa))
            ->orderBy('nombre')
            ->get(['idTipodeterminaciones', 'nombre', 'precio']);

        $cliente = $this->idClientes !== null
            ? $clientes->firstWhere('idClientes', $this->idClientes)
            : null;

        $resumenDescuento = $this->idClientes !== null
            ? DescuentoDeterminacionResolver::resumenParaCliente($this->idClientes)
            : null;

        $sumaTotal = 0.0;
        foreach ($this->seleccionados as $fila) {
            $sumaTotal += PrecioInput::parse((string) ($fila['precio'] ?? '0'));
        }
        $sumaTotal = round($sumaTotal, 2);

        $requerimientos = $this->requerimientosParaSeleccion();

        return view('livewire.listados.estimacion-costos', [
            'clientes' => $clientes,
            'tiposDisponibles' => $tiposDisponibles,
            'clienteBloqueado' => $ctx->esCliente() && $ctx->idClientes,
            'resumenDescuento' => $resumenDescuento,
            'sumaTotal' => $sumaTotal,
            'sumaTotalFormateada' => PrecioInput::format($sumaTotal),
            'requerimientos' => $requerimientos,
        ])->layout('layouts.staff', UsuarioMenuPortal::layoutParamsDesdeContexto());
    }

    /** @return list<array{titulo: string, html: string}> */
    private function requerimientosParaSeleccion(): array
    {
        if ($this->seleccionados === [] || ! Schema::hasTable('reqxtipodet') || ! Schema::hasTable('requerimientos')) {
            return [];
        }

        $idsTipo = collect($this->seleccionados)->pluck('idTipodeterminaciones')->unique()->values()->all();
        if ($idsTipo === []) {
            return [];
        }

        $idsReq = Reqxtipodet::query()
            ->whereIn('idTipodeterminaciones', $idsTipo)
            ->pluck('idRequerimientos')
            ->unique()
            ->values()
            ->all();

        if ($idsReq === []) {
            return [];
        }

        return Requerimiento::query()
            ->whereIn('id', $idsReq)
            ->get(['id', 'titulo', 'requerimiento'])
            ->map(fn (Requerimiento $req) => [
                'titulo' => (string) $req->titulo,
                'html' => RequerimientoHtml::sanitizar((string) $req->requerimiento),
            ])
            ->all();
    }

    private function recalcularPreciosSeleccionados(): void
    {
        if ($this->seleccionados === []) {
            return;
        }

        $ids = collect($this->seleccionados)->pluck('idTipodeterminaciones')->all();
        $tipos = Tipodeterminacion::query()
            ->whereIn('idTipodeterminaciones', $ids)
            ->get()
            ->keyBy('idTipodeterminaciones');

        $nuevos = [];
        foreach ($this->seleccionados as $fila) {
            $idTipo = (int) ($fila['idTipodeterminaciones'] ?? 0);
            $tipo = $tipos->get($idTipo);
            if ($tipo === null) {
                continue;
            }
            $nuevos[] = $this->filaDesdeTipo($tipo);
        }

        $this->seleccionados = $nuevos;
    }

    /** @return array{idTipodeterminaciones: int, nombre: string, neto: string, descuento: string, precio: string} */
    private function filaDesdeTipo(Tipodeterminacion $tipo): array
    {
        $neto = PrecioDeterminacionResolver::resolverPrecioLista1($tipo);
        $descuento = DescuentoDeterminacionResolver::calcularDescuento(
            $neto,
            (int) $this->idClientes,
            $tipo
        );
        $precio = PrecioDeterminacionResolver::precioConDescuento($neto, $descuento);

        return [
            'idTipodeterminaciones' => (int) $tipo->idTipodeterminaciones,
            'nombre' => (string) $tipo->nombre,
            'neto' => PrecioInput::format($neto),
            'descuento' => PrecioInput::format($descuento),
            'precio' => PrecioInput::format($precio),
        ];
    }

    private function yaSeleccionada(int $idTipo): bool
    {
        foreach ($this->seleccionados as $fila) {
            if ((int) ($fila['idTipodeterminaciones'] ?? 0) === $idTipo) {
                return true;
            }
        }

        return false;
    }

    private function assertClienteEnAlcance(int $idClientes): void
    {
        $ctx = labCtx();

        Cliente::query()
            ->when($ctx->esCliente() && $ctx->idClientes, fn ($q) => $q->where('idClientes', $ctx->idClientes))
            ->where('idClientes', $idClientes)
            ->firstOrFail();
    }
}
