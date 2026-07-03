<?php

namespace App\Support;

class UsuarioMenuPortal
{
    /** IDs de roles que ingresan al Menú de Administración. */
    public const ROLES_ADMINISTRACION = [2, 3];

    public static function esAdministracion(?int $idRoles): bool
    {
        return in_array((int) $idRoles, self::ROLES_ADMINISTRACION, true);
    }

    public static function esCliente(?int $idClientes): bool
    {
        return (int) $idClientes > 0;
    }

    public static function rutaInicio(?int $idRoles, ?int $idClientes): string
    {
        if (self::esCliente($idClientes)) {
            return 'cliente.home';
        }

        if (self::esAdministracion($idRoles)) {
            return 'admin.dashboard';
        }

        return 'dashboard';
    }

    public static function layoutStaff(?int $idRoles): string
    {
        return self::esAdministracion($idRoles)
            ? 'layouts.administracion'
            : 'layouts.laboratorio';
    }
}
