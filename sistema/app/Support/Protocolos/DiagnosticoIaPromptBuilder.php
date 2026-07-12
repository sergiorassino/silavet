<?php

namespace App\Support\Protocolos;

use App\Models\Paciente;
use App\Support\Informes\InformePacienteConsulta;

/**
 * Arma el prompt clínico para consultar ChatGPT (paridad con diagnosticoIA de NeoLab).
 */
final class DiagnosticoIaPromptBuilder
{
    public const CHATGPT_URL = 'https://chatgpt.com';

    /**
     * Límite conservador para window.open con prompt en la URL.
     * Si se supera, el cliente usa portapapeles + solapa vacía.
     */
    public const URL_MAX_CHARS = 16000;

    public const DISCLAIMER = 'Este módulo está pensado sólo como una ayuda para el profesional veterinario: reúne datos del protocolo (síntomas clínicos, resultados de laboratorio y contexto del paciente) para que la IA aporte elementos adicionales a la hora de interpretar el estado de salud y planificar el tratamiento. Es un recurso más para la toma de decisiones y de ninguna manera reemplaza el criterio, el diagnóstico ni la responsabilidad del profesional veterinario.';

    /**
     * URL de ChatGPT con el prompt precargado (?q= / #?q=).
     * Devuelve null si el texto es demasiado largo para la barra de direcciones.
     */
    public static function urlConPrompt(string $prompt): ?string
    {
        $prompt = trim($prompt);
        if ($prompt === '') {
            return null;
        }

        $encoded = rawurlencode($prompt);

        // Fragmento (#?q=): más tolerante con prompts clínicos largos que ?q= solo.
        $conFragmento = self::CHATGPT_URL.'/#?q='.$encoded;
        if (strlen($conFragmento) <= self::URL_MAX_CHARS) {
            return $conFragmento;
        }

        $conQuery = self::CHATGPT_URL.'/?q='.$encoded;
        if (strlen($conQuery) <= self::URL_MAX_CHARS) {
            return $conQuery;
        }

        return null;
    }

    /**
     * Prompt completo listo para pegar en ChatGPT.
     */
    public static function armar(Paciente $paciente, string $clinica): string
    {
        $paciente->loadMissing(['cliente', 'especie', 'raza']);

        $datos = InformePacienteConsulta::armar($paciente);
        $p = $datos['paciente'] ?? [];
        $especie = trim((string) ($p['especie'] ?? $paciente->especie?->nombre ?? ''));
        if ($especie === '') {
            $especie = 'la especie indicada';
        }

        $lineas = [];

        $lineas[] = 'Actuá como veterinario clínico experto en '.$especie.', con sólida formación en medicina interna, interpretación de laboratorio clínico y planificación terapéutica.';
        $lineas[] = '';
        $lineas[] = 'Voy a darte el contexto de un caso de laboratorio veterinario (datos del paciente, resultados de estudios y síntomas clínicos). Tu respuesta debe ayudar al veterinario a reunir más elementos para evaluar el estado de salud y planificar el tratamiento; no reemplaza su criterio profesional.';
        $lineas[] = '';
        $lineas[] = 'Restricciones importantes:';
        $lineas[] = '- Respondé en español, con lenguaje técnico claro orientado a un profesional veterinario.';
        $lineas[] = '- Basate únicamente en los datos suministrados; si falta información, indicalo explícitamente.';
        $lineas[] = '- No inventes valores de laboratorio ni hallazgos no mencionados.';
        $lineas[] = '- Diferenciá con claridad lo que es interpretación tentativa de lo que sería una indicación clínica definitiva.';
        $lineas[] = '- Incluí advertencias de urgencia o red flags si corresponde.';
        $lineas[] = '- Las sugerencias terapéuticas deben ser orientativas (líneas de tratamiento, monitoreo, reevaluación), no una receta lista para dispensar sin criterio clínico.';
        $lineas[] = '';
        $lineas[] = 'Estructura tu respuesta en estas secciones:';
        $lineas[] = '1) Síntesis del caso';
        $lineas[] = '2) Interpretación de los hallazgos de laboratorio (destacar alterados vs. referencia)';
        $lineas[] = '3) Correlación con los síntomas clínicos y evaluación del estado de salud';
        $lineas[] = '4) Diagnósticos diferenciales tentativos (priorizados, con breve justificación)';
        $lineas[] = '5) Sugerencias para planificar el tratamiento (medidas de soporte, líneas terapéuticas posibles, monitoreo)';
        $lineas[] = '6) Estudios o controles adicionales que podrían aportar valor';
        $lineas[] = '7) Señales de alarma / criterios de urgencia';
        $lineas[] = '8) Limitaciones de esta orientación (qué no se puede concluir con los datos actuales)';
        $lineas[] = '';
        $lineas[] = '=== DATOS DEL PACIENTE ===';
        $lineas[] = 'Protocolo: '.self::dato($p['protocolo'] ?? $paciente->nombreProtocolo);
        $lineas[] = 'Fecha: '.self::dato($p['fecha'] ?? $paciente->fechhoyFormateada());
        $lineas[] = 'Especie: '.self::dato($especie);
        $lineas[] = 'Veterinaria: '.self::dato($p['cliente'] ?? $paciente->cliente?->nombre);
        $lineas[] = 'Raza: '.self::dato($p['raza'] ?? $paciente->raza?->nombre);
        $lineas[] = 'Paciente: '.self::dato($p['nombre'] ?? $paciente->nombre);
        $lineas[] = 'Sexo: '.self::dato($p['sexo'] ?? $paciente->sexo);
        $lineas[] = 'Propietario: '.self::dato($p['propietario'] ?? $paciente->propietario);
        $lineas[] = 'Edad: '.self::dato($p['edad'] ?? $paciente->edad);

        $rotuloRef = trim((string) ($p['rotulo_ref'] ?? ''));
        if ($rotuloRef !== '') {
            $lineas[] = 'Referencias de laboratorio: '.$rotuloRef;
        }

        $lineas[] = '';
        $lineas[] = '=== RESULTADOS DE LABORATORIO ===';

        $grupos = $datos['grupos'] ?? [];
        if ($grupos === []) {
            $lineas[] = '(Sin resultados visibles en el informe para este protocolo.)';
        } else {
            foreach ($grupos as $grupo) {
                $nombreGrupo = trim((string) ($grupo['nombreGrupo'] ?? ''));
                if ($nombreGrupo !== '') {
                    $lineas[] = '';
                    $lineas[] = $nombreGrupo;
                }

                foreach ($grupo['renglones'] ?? [] as $r) {
                    $lineaResultado = self::lineaResultado($r);
                    if ($lineaResultado !== null) {
                        $lineas[] = $lineaResultado;
                    }
                }
            }
        }

        $observaciones = trim((string) ($p['observaciones'] ?? $paciente->observaciones ?? ''));
        if ($observaciones !== '') {
            $lineas[] = '';
            $lineas[] = 'Observaciones del protocolo: '.$observaciones;
        }

        $clinica = trim($clinica);
        $lineas[] = '';
        $lineas[] = '=== SÍNTOMAS CLÍNICOS ===';
        $lineas[] = $clinica !== ''
            ? $clinica
            : '(No se cargaron síntomas clínicos.)';

        $lineas[] = '';
        $lineas[] = 'Con estos datos, elaborá la orientación clínica solicitada.';

        return implode("\n", $lineas);
    }

    /**
     * @param  array<string, mixed>  $r
     */
    private static function lineaResultado(array $r): ?string
    {
        $tipo = (int) ($r['tipoItem'] ?? 0);
        $nombre = trim((string) ($r['nombreItem'] ?? ''));
        if ($nombre === '' || in_array($tipo, [5, 6], true)) {
            return null;
        }

        $valor = trim((string) ($r['valor'] ?? ''));
        $valor2 = trim((string) ($r['valor2'] ?? ''));
        $unidad = trim((string) ($r['unidadMedida'] ?? ''));
        $unidad2 = trim((string) ($r['unidadMedida2'] ?? ''));
        $referencia = trim((string) ($r['referencia'] ?? ''));

        // tipoItem 9 usa valor2 / unidadMedida2 (paridad NeoLab).
        if ($tipo === 9) {
            $partes = array_filter([$nombre, $valor2, $unidad2], static fn ($v) => $v !== '');
            $linea = implode(' ', $partes);
        } elseif (in_array($tipo, [3, 8], true)) {
            $partes = array_filter([$nombre, $valor], static fn ($v) => $v !== '');
            $linea = implode(' ', $partes);
        } else {
            $partes = array_filter([$nombre, $valor, $unidad], static fn ($v) => $v !== '');
            $linea = implode(' ', $partes);
        }

        if ($referencia !== '' && ! in_array($tipo, [3, 8], true)) {
            $linea .= ' Referencias: '.$referencia;
        }

        return $linea;
    }

    private static function dato(mixed $valor): string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto !== '' ? $texto : '—';
    }
}
