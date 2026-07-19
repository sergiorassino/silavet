<?php

namespace App\Support\Autoanalizadores\Drivers;

/**
 * Wiener CM 160 — mismo layout CSV que Metrolab CM 250 (pares det/valor + protocolo Excel).
 *
 * El redondeo del legado NeoLab/LAM es distinto (enzimas a entero sin miles);
 * se configura en overrides del tenant, no en este parser.
 */
final class WienerCm160Driver extends MetrolabCm250Driver
{
}
