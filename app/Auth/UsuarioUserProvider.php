<?php

namespace App\Auth;

use App\Models\Usuario;
use App\Support\UsuarioMenuPortal;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class UsuarioUserProvider implements UserProvider
{
    public function retrieveById($identifier): ?Authenticatable
    {
        return Usuario::find($identifier);
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        //
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials['dni'])) {
            return null;
        }

        $query = Usuario::where('dni', $credentials['dni']);

        if (array_key_exists('portal', $credentials)) {
            if ($credentials['portal'] === 'cliente') {
                $query->where('idRoles', UsuarioMenuPortal::ID_ROL_CLIENTE)
                    ->where('idClientes', '>', 0);
            } else {
                $query->where(function ($q) {
                    $q->whereNull('idRoles')
                        ->orWhere('idRoles', '!=', UsuarioMenuPortal::ID_ROL_CLIENTE);
                });
            }
        }

        return $query->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return self::verificarPassword($user, (string) ($credentials['password'] ?? ''));
    }

    public static function verificarPassword(Authenticatable $user, string $plain): bool
    {
        return hash_equals((string) $user->getAuthPassword(), $plain);
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        //
    }
}
