<?php

namespace App\Support\Autoanalizadores;

use App\Support\Autoanalizadores\Contracts\AutoanalizadorDriver;
use App\Support\Autoanalizadores\Drivers\BiosystemA15Driver;
use App\Support\Autoanalizadores\Drivers\EdanH30Driver;
use App\Support\Autoanalizadores\Drivers\EdanH60Driver;
use App\Support\Autoanalizadores\Drivers\ExigoH400Driver;
use App\Support\Autoanalizadores\Drivers\GeoMcDriver;
use App\Support\Autoanalizadores\Drivers\IncaaDriver;
use App\Support\Autoanalizadores\Drivers\MetrolabCm250Driver;
use App\Support\Autoanalizadores\Drivers\MindrayBc20Driver;
use App\Support\Autoanalizadores\Drivers\MindrayBs120Driver;
use App\Support\Autoanalizadores\Drivers\WienerCm160Driver;
use InvalidArgumentException;

final class AutoanalizadorDriverRegistry
{
    /** @var array<string, class-string<AutoanalizadorDriver>> */
    private const DRIVERS = [
        'mindray_bc20' => MindrayBc20Driver::class,
        'mindray_bs120' => MindrayBs120Driver::class,
        'exigo_h400' => ExigoH400Driver::class,
        'geo_mc' => GeoMcDriver::class,
        'edan_h30' => EdanH30Driver::class,
        'edan_h60' => EdanH60Driver::class,
        'incaa' => IncaaDriver::class,
        'biosystem_a15' => BiosystemA15Driver::class,
        'metrolab_cm250' => MetrolabCm250Driver::class,
        'wiener_cm160' => WienerCm160Driver::class,
    ];

    public static function tiene(string $clave): bool
    {
        return isset(self::DRIVERS[$clave]);
    }

    public static function resolver(string $clave): AutoanalizadorDriver
    {
        if (! isset(self::DRIVERS[$clave])) {
            throw new InvalidArgumentException("No hay driver registrado para el aparato [{$clave}].");
        }

        $clase = self::DRIVERS[$clave];

        return new $clase;
    }

    /**
     * @return list<string>
     */
    public static function claves(): array
    {
        return array_keys(self::DRIVERS);
    }
}
