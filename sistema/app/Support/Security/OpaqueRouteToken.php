<?php

namespace App\Support\Security;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use JsonException;

/**
 * Referencias opacas para rutas GET (PDF, descargas) sin IDs numéricos en la URL.
 *
 * Cifrado con APP_KEY; no enumerable. Siempre revalidar auth, permiso y alcance
 * en el controlador.
 */
final class OpaqueRouteToken
{
    public const PURPOSE_INFORME_PACIENTE = 'protocolos.informe-paciente';

    /** Vigencia del token (segundos). */
    private const TTL_SEGUNDOS = 7200;

    public static function forInformePaciente(int $idPacientes, ?int $idUsuario = null): string
    {
        return self::encodePayload(self::PURPOSE_INFORME_PACIENTE, [
            'id' => $idPacientes,
            'u' => $idUsuario ?? (int) (auth()->id() ?? 0),
            't' => time(),
            // Nonce: URL distinta en cada render → evita caché agresiva (Safari/iOS).
            'n' => bin2hex(random_bytes(4)),
        ]);
    }

    /**
     * @return array{id: int, u: int}|null
     */
    public static function decodeInformePaciente(string $ref): ?array
    {
        $data = self::decodePayload($ref, self::PURPOSE_INFORME_PACIENTE);
        if ($data === null) {
            return null;
        }

        $id = (int) ($data['id'] ?? 0);
        $u = (int) ($data['u'] ?? 0);
        $t = (int) ($data['t'] ?? 0);

        if ($id <= 0 || $u <= 0 || $t <= 0) {
            return null;
        }

        if ((time() - $t) > self::TTL_SEGUNDOS) {
            return null;
        }

        return ['id' => $id, 'u' => $u];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function decodePayload(string $ref, string $purpose): ?array
    {
        $ref = trim($ref);
        if ($ref === '') {
            return null;
        }

        try {
            $json = Crypt::decryptString(self::fromUrlSafe($ref));
            /** @var array{p?: string, d?: array<string, mixed>} $payload */
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException) {
            return null;
        }

        if (($payload['p'] ?? '') !== $purpose) {
            return null;
        }

        $data = $payload['d'] ?? null;

        return is_array($data) ? $data : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function encodePayload(string $purpose, array $data): string
    {
        $payload = json_encode([
            'p' => $purpose,
            'd' => $data,
        ], JSON_THROW_ON_ERROR);

        return self::toUrlSafe(Crypt::encryptString($payload));
    }

    private static function toUrlSafe(string $encrypted): string
    {
        return rtrim(strtr($encrypted, '+/', '-_'), '=');
    }

    private static function fromUrlSafe(string $ref): string
    {
        $b64 = strtr($ref, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad > 0) {
            $b64 .= str_repeat('=', 4 - $pad);
        }

        return $b64;
    }
}
