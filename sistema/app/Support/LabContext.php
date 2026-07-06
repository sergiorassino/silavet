<?php

namespace App\Support;

use App\Models\Rol;
use App\Models\Usuario;

class LabContext
{
    public ?int $idUsuarios = null;

    public ?int $idRoles = null;

    public ?int $idClientes = null;

    private ?Usuario $_usuario = null;

    private ?Rol $_rol = null;

    public static function fromSession(): static
    {
        $ctx = new static();
        $ctx->idUsuarios = session('lab.idUsuarios');
        $ctx->idRoles = session('lab.idRoles');
        $ctx->idClientes = session('lab.idClientes');

        return $ctx;
    }

    public static function set(int $idUsuarios, ?int $idRoles, ?int $idClientes): void
    {
        session([
            'lab.idUsuarios' => $idUsuarios,
            'lab.idRoles' => $idRoles,
            'lab.idClientes' => $idClientes,
        ]);
    }

    public static function clear(): void
    {
        session()->forget([
            'lab.idUsuarios',
            'lab.idRoles',
            'lab.idClientes',
        ]);
    }

    public function isValid(): bool
    {
        return $this->idUsuarios !== null;
    }

    public function esCliente(): bool
    {
        return UsuarioMenuPortal::esCliente($this->idRoles, $this->idClientes);
    }

    public function esAdministracion(): bool
    {
        return UsuarioMenuPortal::esAdministracion($this->idRoles);
    }

    public function usuario(): ?Usuario
    {
        if ($this->_usuario === null && $this->idUsuarios) {
            $this->_usuario = Usuario::find($this->idUsuarios);
        }

        return $this->_usuario;
    }

    public function rol(): ?Rol
    {
        if ($this->_rol === null && $this->idRoles) {
            $this->_rol = Rol::find($this->idRoles);
        }

        return $this->_rol;
    }

    public function rolNombre(): string
    {
        return $this->rol()?->rol ?? '';
    }
}
