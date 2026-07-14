<?php

namespace App\Support;

use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;

class PermisosIaCatalog
{
    public const CLIENTES = 0;

    public const ESPECIES = 1;

    public const DETERMINACIONES = 2;

    public const PROTOCOLOS = 3;

    public const RESULTADOS = 4;

    public const INFORMES = 5;

    public const FACTURACION = 6;

    public const REACTIVOS = 7;

    public const PARAMETROS = 8;

    public const USUARIOS = 9;

    public const LISTADOS_ESTADISTICOS = 10;

    public static function usuarioTienePermiso(int $orden): bool
    {
        /** @var Usuario|null $usuario */
        $usuario = Auth::user();

        if (! $usuario) {
            return false;
        }

        if (config('tenant.acceso.temporal_todos_modulos', false)
            && ! UsuarioMenuPortal::esCliente($usuario->idRoles, $usuario->idClientes)) {
            return true;
        }

        $cadena = '';

        if (\Illuminate\Support\Facades\Schema::hasColumn('usuarios', 'permisos_ia')) {
            $cadena = (string) ($usuario->permisos_ia ?? '');
        }

        if ($cadena === '') {
            // Sin catálogo cargado aún: permitir acceso en desarrollo inicial.
            return true;
        }

        if ($orden < 0 || $orden >= strlen($cadena)) {
            return false;
        }

        return $cadena[$orden] === '1';
    }
}
