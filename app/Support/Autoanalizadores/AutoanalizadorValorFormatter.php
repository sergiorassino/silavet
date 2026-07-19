<?php

namespace App\Support\Autoanalizadores;

/**
 * Aplica overrides del perfil del lab sobre valores crudos del driver.
 */
final class AutoanalizadorValorFormatter
{
    /**
     * @param  array<string, string>  $crudos  idAnalizador => número crudo
     * @param  array<string, array<string, mixed>>  $overrides
     * @return array<string, string>  idAnalizador => valor listo para renglones.valor
     */
    public function formatear(array $crudos, array $overrides): array
    {
        $salida = [];

        foreach ($crudos as $codigo => $crudo) {
            $cfg = is_array($overrides[$codigo] ?? null) ? $overrides[$codigo] : [];
            $salida[$codigo] = $this->formatearUno($crudo, $cfg);
        }

        return $salida;
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    private function formatearUno(string $crudo, array $cfg): string
    {
        $mult = isset($cfg['multiplicador']) ? (float) $cfg['multiplicador'] : 1.0;
        $formato = (string) ($cfg['formato'] ?? '');
        if ($formato === '' && isset($cfg['redondear']) && $cfg['redondear'] === 'entero') {
            $formato = 'entero';
        }

        $tieneDecimales = array_key_exists('decimales', $cfg) && $cfg['decimales'] !== null;
        $sinTransformacion = $mult === 1.0 && $formato === '' && ! $tieneDecimales;

        if ($sinTransformacion) {
            return $crudo;
        }

        $num = (float) str_replace(',', '.', $crudo);
        $num *= $mult;

        return match ($formato) {
            'entero' => (string) (int) round($num),
            'entero_miles' => number_format((int) round($num), 0, ',', '.'),
            default => $this->formatoDefault($num, $cfg),
        };
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    private function formatoDefault(float $num, array $cfg): string
    {
        if (array_key_exists('decimales', $cfg) && $cfg['decimales'] !== null) {
            $dec = (int) $cfg['decimales'];
            $separadorMiles = (bool) ($cfg['separador_miles'] ?? false);
            if ($separadorMiles) {
                return number_format($num, $dec, ',', '.');
            }

            return number_format($num, $dec, '.', '');
        }

        // Sin override: conservar representación razonable del float.
        $texto = rtrim(rtrim(sprintf('%.6F', $num), '0'), '.');

        return $texto === '' ? '0' : $texto;
    }
}
