<?php

namespace App\Livewire\Clientes;

use App\Models\Cliente;
use App\Support\CuentaCorriente\CuentaCorrienteConsulta;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Livewire\Component;

class CuentaCorrienteDetalle extends Component
{
    public int $idClientes;

    public string $fechaDesde = '';

    public string $fechaHasta = '';

    public function mount(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::FACTURACION), 403);

        $this->idClientes = $id;
    }

    public function getClienteProperty(): Cliente
    {
        return Cliente::query()->findOrFail($this->idClientes);
    }

    public function getPdfUrlProperty(): string
    {
        return route('clientes.cuenta-corriente.detalle.pdf', array_filter([
            'id' => $this->idClientes,
            'desde' => trim($this->fechaDesde) !== '' ? trim($this->fechaDesde) : null,
            'hasta' => trim($this->fechaHasta) !== '' ? trim($this->fechaHasta) : null,
        ]));
    }

    public function getExcelUrlProperty(): string
    {
        return route('clientes.cuenta-corriente.detalle.excel', array_filter([
            'id' => $this->idClientes,
            'desde' => trim($this->fechaDesde) !== '' ? trim($this->fechaDesde) : null,
            'hasta' => trim($this->fechaHasta) !== '' ? trim($this->fechaHasta) : null,
        ]));
    }

    public function render()
    {
        $cliente = $this->cliente;
        $filas = CuentaCorrienteConsulta::protocolosCliente(
            $this->idClientes,
            $this->fechaDesde,
            $this->fechaHasta,
        );
        $resumen = CuentaCorrienteConsulta::resumenProtocolos($filas);
        $saldoHoy = CuentaCorrienteConsulta::saldoClienteHoy($this->idClientes);
        $saldoAnterior = CuentaCorrienteConsulta::saldoAnteriorAFecha($this->idClientes, $this->fechaDesde);

        return view('livewire.clientes.cuenta-corriente-detalle', [
            'cliente' => $cliente,
            'filas' => $filas,
            'resumen' => $resumen,
            'saldoHoy' => $saldoHoy,
            'saldoAnterior' => $saldoAnterior,
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
