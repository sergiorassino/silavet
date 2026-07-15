<?php

namespace App\Support;

use App\Models\Paciente;

class UsuarioMenuPortal
{
    /** @deprecated Usar config('tenant.roles.cliente') */
    public const ID_ROL_CLIENTE = 1;

    /**
     * Cliente interno del laboratorio (`clientes.idClientes = 1`).
     * Usuarios asociados a este cliente usan el menú de laboratorio/administración.
     */
    public const ID_CLIENTES_LABORATORIO = Paciente::ID_CLIENTES_EGRESO;

    /** @return list<int> */
    public static function rolesCliente(): array
    {
        return array_map('intval', config('tenant.roles.cliente', [self::ID_ROL_CLIENTE]));
    }

    /** @return list<int> */
    public static function rolesAdministracion(): array
    {
        return array_map('intval', config('tenant.roles.administracion', [3]));
    }

    public static function esAdministracion(?int $idRoles): bool
    {
        return in_array((int) $idRoles, self::rolesAdministracion(), true);
    }

    /**
     * Autogestión: usuario asociado a un cliente veterinario distinto del laboratorio (id = 1).
     * En tenants como ALQU los clientes suelen tener idRoles null; el discriminante es idClientes.
     */
    public static function esCliente(?int $idRoles, ?int $idClientes = null): bool
    {
        $id = (int) ($idClientes ?? 0);

        return $id > 0 && $id !== self::ID_CLIENTES_LABORATORIO;
    }

    public static function esStaff(?int $idRoles, ?int $idClientes = null): bool
    {
        return ! self::esCliente($idRoles, $idClientes);
    }

    public static function rutaInicio(?int $idRoles, ?int $idClientes): string
    {
        if (self::esCliente($idRoles, $idClientes)) {
            return 'cliente.home';
        }

        if (self::esAdministracion($idRoles)) {
            return 'admin.dashboard';
        }

        return 'dashboard';
    }

    /**
     * Parámetros del layout staff (laboratorio, administración o autogestión).
     *
     * @return array{menuLabel: string, navPartial: string, homeRoute: string, collapsedSidebar: bool}
     */
    public static function staffLayoutParams(?int $idRoles, ?int $idClientes = null): array
    {
        if (self::esCliente($idRoles, $idClientes)) {
            return self::clienteLayoutParams();
        }

        if (config('tenant.acceso.temporal_todos_modulos', false)) {
            return [
                'menuLabel' => 'Menú del personal',
                'navPartial' => 'layouts.partials.sidebar-nav-staff',
                'homeRoute' => self::esAdministracion($idRoles)
                    ? route('admin.dashboard')
                    : route('dashboard'),
                'collapsedSidebar' => true,
            ];
        }

        if (self::esAdministracion($idRoles)) {
            return [
                'menuLabel' => 'Menú de Administración',
                'navPartial' => 'layouts.partials.sidebar-nav-administracion',
                'homeRoute' => route('admin.dashboard'),
                'collapsedSidebar' => true,
            ];
        }

        return [
            'menuLabel' => 'Menú de Laboratorio',
            'navPartial' => 'layouts.partials.sidebar-nav-laboratorio',
            'homeRoute' => route('dashboard'),
            'collapsedSidebar' => true,
        ];
    }

    /**
     * @return array{menuLabel: string, navPartial: string, homeRoute: string, collapsedSidebar: bool}
     */
    public static function clienteLayoutParams(): array
    {
        return [
            'menuLabel' => 'Menú de Clientes',
            'navPartial' => 'layouts.partials.sidebar-nav-cliente',
            'homeRoute' => route('cliente.home'),
            'collapsedSidebar' => true,
        ];
    }

    public static function layoutParamsDesdeContexto(): array
    {
        $ctx = labCtx();

        return self::staffLayoutParams($ctx->idRoles, $ctx->idClientes);
    }

    public static function layoutStaff(?int $idRoles): string
    {
        return 'layouts.staff';
    }
}
