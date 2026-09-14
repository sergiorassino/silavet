<?php

namespace Tests\Unit;

use App\Support\Autoanalizadores\AutoanalizadorValorFormatter;
use App\Support\Autoanalizadores\Drivers\MetrolabCm250Driver;
use PHPUnit\Framework\TestCase;

class MetrolabCm250DriverTest extends TestCase
{
    public function test_extrae_calcio_caiii_del_csv(): void
    {
        $ruta = $this->csvTemporal(
            '"=""2608631""","N","AKIRA",,,,"12/09/26","10","ALB","2,244","g/dL","ALPL","350,6","U/L","CAIII","6,651","mg/dl","CRELc","0,9522","mg/dL","FOS","4,049","mg/dL","GGTL","11,37","U/L","GOTL","55,4","U/L","GPTL","17,46","U/L","PROT","7,714","g/dL","UREL","30,06","g/L"'
        );

        try {
            $valores = (new MetrolabCm250Driver)->buscarPorProtocolo($ruta, '2608631');
            $this->assertIsArray($valores);
            $this->assertSame('6.651', $valores['CAIII']);
            $this->assertSame('4.049', $valores['FOS']);
            $this->assertArrayNotHasKey('CAIII ', $valores);
        } finally {
            @unlink($ruta);
        }
    }

    public function test_codigo_con_espacio_legacy_coincide_tras_trim(): void
    {
        $valores = (new AutoanalizadorValorFormatter)->formatear(
            ['CAIII' => '6.651', 'FOS' => '4.049'],
            [
                'CAIII' => ['decimales' => 1],
                'FOS' => ['decimales' => 1],
            ]
        );

        $this->assertSame('6.7', $valores['CAIII']);
        $this->assertSame('4.0', $valores['FOS']);

        $codigoRenglon = 'CAIII ';
        $this->assertArrayHasKey(trim($codigoRenglon), $valores);
        $this->assertArrayNotHasKey($codigoRenglon, $valores);
    }

    private function csvTemporal(string $linea): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'cm250_');
        file_put_contents($ruta, $linea."\n");

        return $ruta;
    }
}
