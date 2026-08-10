<?php

namespace App\Support;

/**
 * Uno o más emails en un solo campo (p. ej. clientes.email),
 * separados por `;` o `,`.
 */
final class EmailList
{
    /** Capacidad de clientes.email tras migración widen. */
    public const MAX_LENGTH = 500;

    public const SEPARATOR = ';';

    /**
     * @return list<string>
     */
    public static function parse(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/\s*[;,]\s*/', $raw) ?: [];
        $emails = [];

        foreach ($parts as $part) {
            $email = trim($part);
            if ($email === '') {
                continue;
            }
            if (! in_array($email, $emails, true)) {
                $emails[] = $email;
            }
        }

        return $emails;
    }

    public static function normalize(string $raw): string
    {
        return implode(self::SEPARATOR, self::parse($raw));
    }

    /**
     * Regla Laravel: vacío OK; si hay texto, cada dirección debe ser email válido.
     */
    public static function rule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $raw = trim((string) $value);
            if ($raw === '') {
                return;
            }

            $emails = self::parse($raw);
            if ($emails === []) {
                $fail('Ingrese uno o más emails válidos separados por ;');

                return;
            }

            foreach ($emails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    $fail("El email «{$email}» no es válido.");

                    return;
                }
            }
        };
    }
}
