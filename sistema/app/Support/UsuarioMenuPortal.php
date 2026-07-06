<?php

namespace App\Support;

class UsuarioMenuPortal
{
    /** @deprecated Usar config('tenant.roles.cliente') */
    public const ID_ROL_CLIENTE = 1;

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

    public static function esCliente(?int $idRoles, ?int $idClientes = null): bool
    {
        if (! in_array((int) $idRoles, self::rolesCliente(), true)) {
            return false;
        }

        return $idClientes === null || (int) $idClientes > 0;
    }

    public static function esStaff(?int $idRoles): bool
    {
        return ! self::esCliente($idRoles);
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

    public static function staffLayoutParams(?int $idRoles): array
    {
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

    public static function layoutStaff(?int $idRoles): string
    {
        return 'layouts.staff';
    }
}
