<?php

namespace App\Support\Auth;

use App\Models\Usuario;
use Illuminate\Contracts\Auth\Authenticatable;

class ActualizarContraseñaUsuario
{
    public static function aplicar(Authenticatable $user, string $guard, string $nuevaPlain): bool
    {
        $nuevaPlain = trim($nuevaPlain);
        if ($nuevaPlain === '') {
            return false;
        }

        if (! $user instanceof Usuario) {
            return false;
        }

        $usuario = Usuario::find($user->getAuthIdentifier());
        if (! $usuario) {
            return false;
        }

        $usuario->password = $nuevaPlain;

        return $usuario->save();
    }
}
