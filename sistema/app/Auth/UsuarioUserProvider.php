<?php

namespace App\Auth;

use App\Models\Usuario;
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
                $query->where('idClientes', '>', 0);
            } else {
                $query->where(function ($q) {
                    $q->whereNull('idClientes')->orWhere('idClientes', '<=', 0);
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
        $stored = $user->getAuthPassword();

        if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2a$')) {
            return password_verify($plain, $stored);
        }

        return hash_equals((string) $stored, (string) $plain);
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        //
    }
}
