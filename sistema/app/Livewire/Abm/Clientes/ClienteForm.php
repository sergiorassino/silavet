<?php

namespace App\Livewire\Abm\Clientes;

use App\Models\Cliente;
use App\Support\PermisosIaCatalog;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class ClienteForm extends Component
{
    public ?int $idClientes = null;

    public string $nombre = '';

    public string $direccion = '';

    public string $telefono1 = '';

    public string $telefono2 = '';

    public string $email = '';

    public string $whatsapp = '';

    public string $cuit = '';

    public function mount(?int $id = null): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::CLIENTES), 403);

        if ($id) {
            $cliente = Cliente::findOrFail($id);
            $this->idClientes = $cliente->idClientes;
            $this->nombre = (string) $cliente->nombre;
            $this->direccion = (string) $cliente->direccion;
            $this->telefono1 = (string) $cliente->telefono1;
            $this->telefono2 = (string) $cliente->telefono2;
            $this->email = (string) $cliente->email;
            $this->whatsapp = (string) $cliente->whatsapp;
            $this->cuit = (string) ($cliente->cuit ?? '');
        }
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:200'],
            'direccion' => ['nullable', 'string', 'max:200'],
            'telefono1' => ['nullable', 'string', 'max:50'],
            'telefono2' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'cuit' => ['nullable', 'digits:11'],
        ];
    }

    public function save(): void
    {
        $key = 'cliente-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $data = $this->validate();
        $data['nombre'] = trim($data['nombre']);
        $data['direccion'] = trim((string) ($data['direccion'] ?? ''));
        $data['telefono1'] = trim((string) ($data['telefono1'] ?? ''));
        $data['telefono2'] = trim((string) ($data['telefono2'] ?? ''));
        $data['email'] = trim((string) ($data['email'] ?? ''));
        $data['whatsapp'] = trim((string) ($data['whatsapp'] ?? ''));
        $data['cuit'] = trim((string) ($data['cuit'] ?? ''));

        if ($this->idClientes) {
            $cliente = Cliente::findOrFail($this->idClientes);
            $cliente->update($data);
            $mensaje = 'Cliente actualizado correctamente.';
        } else {
            Cliente::create($data);
            $mensaje = 'Cliente creado correctamente.';
        }

        RateLimiter::hit($key, 60);
        $this->dispatch('vl-swal-exito', mensaje: $mensaje);

        $this->redirectRoute('abm.clientes.index', navigate: false);
    }

    public function render()
    {
        $titulo = $this->idClientes ? 'Editar cliente' : 'Nuevo cliente';

        return view('livewire.abm.clientes.cliente-form', compact('titulo'))
            ->layout('layouts.laboratorio');
    }
}
