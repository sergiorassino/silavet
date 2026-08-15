<?php

/**
 * Alinea `entorno` con las columnas que SILAVET espera (legacy NeoLab + aditivas).
 * Pensada para laboratorios nuevos cuyo dump no trae el mismo esquema que lb_neolab.
 *
 * Idempotente: solo ADD COLUMN si falta. No modifica tipos ni valores existentes.
 * AFTER es opcional: si el ancla no existe, agrega al final (no falla).
 *
 * Se aplica con: php artisan lb:migrate-legacy --force
 * SQL manual: database/sql/entorno_ensure_columnas_silavet.sql
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<array{name: string, ddl: string, after: string|null}>
     */
    private const COLUMNAS = [
        ['name' => 'formulas', 'ddl' => '`formulas` text NULL', 'after' => 'id'],
        ['name' => 'nombreListaPrecio', 'ddl' => '`nombreListaPrecio` varchar(200) DEFAULT NULL', 'after' => 'formulas'],
        ['name' => 'listaPreciosPdf', 'ddl' => '`listaPreciosPdf` varchar(255) DEFAULT NULL', 'after' => 'nombreListaPrecio'],
        ['name' => 'carpeta', 'ddl' => '`carpeta` varchar(30) DEFAULT NULL', 'after' => 'listaPreciosPdf'],
        ['name' => 'logo', 'ddl' => '`logo` varchar(255) DEFAULT NULL', 'after' => 'carpeta'],
        ['name' => 'headerInforme', 'ddl' => '`headerInforme` varchar(255) DEFAULT NULL', 'after' => 'logo'],
        ['name' => 'footerInforme', 'ddl' => '`footerInforme` varchar(255) DEFAULT NULL', 'after' => 'headerInforme'],
        ['name' => 'fondo', 'ddl' => '`fondo` varchar(255) DEFAULT NULL', 'after' => 'footerInforme'],
        ['name' => 'direLabo', 'ddl' => '`direLabo` varchar(255) DEFAULT NULL', 'after' => 'fondo'],
        ['name' => 'teleLabo', 'ddl' => '`teleLabo` varchar(80) DEFAULT NULL', 'after' => 'direLabo'],
        ['name' => 'emailLabo', 'ddl' => '`emailLabo` varchar(120) DEFAULT NULL', 'after' => 'teleLabo'],
        ['name' => 'colorInforme', 'ddl' => '`colorInforme` varchar(20) DEFAULT NULL', 'after' => 'emailLabo'],
        ['name' => 'colorFondoSistema', 'ddl' => '`colorFondoSistema` varchar(20) DEFAULT NULL', 'after' => 'colorInforme'],
        ['name' => 'texto1footerIzq', 'ddl' => '`texto1footerIzq` varchar(255) DEFAULT NULL', 'after' => 'colorFondoSistema'],
        ['name' => 'texto2footerIzq', 'ddl' => '`texto2footerIzq` varchar(255) DEFAULT NULL', 'after' => 'texto1footerIzq'],
        ['name' => 'texto1footerCentro', 'ddl' => '`texto1footerCentro` varchar(255) DEFAULT NULL', 'after' => 'texto2footerIzq'],
        ['name' => 'texto2footerCentro', 'ddl' => '`texto2footerCentro` varchar(255) DEFAULT NULL', 'after' => 'texto1footerCentro'],
        ['name' => 'texto1footerDer', 'ddl' => '`texto1footerDer` varchar(255) DEFAULT NULL', 'after' => 'texto2footerCentro'],
        ['name' => 'texto2footerDer', 'ddl' => '`texto2footerDer` varchar(255) DEFAULT NULL', 'after' => 'texto1footerDer'],
        ['name' => 'firmaIzq', 'ddl' => '`firmaIzq` varchar(255) DEFAULT NULL', 'after' => 'texto2footerDer'],
        ['name' => 'firmaCentro', 'ddl' => '`firmaCentro` varchar(255) DEFAULT NULL', 'after' => 'firmaIzq'],
        ['name' => 'firmaDer', 'ddl' => '`firmaDer` varchar(255) DEFAULT NULL', 'after' => 'firmaCentro'],
        ['name' => 'ctaEnvioMail', 'ddl' => '`ctaEnvioMail` varchar(120) DEFAULT NULL', 'after' => 'firmaDer'],
        ['name' => 'passEnvioMail', 'ddl' => '`passEnvioMail` varchar(255) DEFAULT NULL', 'after' => 'ctaEnvioMail'],
        ['name' => 'fromMail', 'ddl' => '`fromMail` varchar(120) DEFAULT NULL', 'after' => 'passEnvioMail'],
        ['name' => 'nombrePieMail', 'ddl' => '`nombrePieMail` varchar(120) DEFAULT NULL', 'after' => 'fromMail'],
        ['name' => 'direccionPieMail', 'ddl' => '`direccionPieMail` varchar(255) DEFAULT NULL', 'after' => 'nombrePieMail'],
        ['name' => 'telefonoPieMail', 'ddl' => '`telefonoPieMail` varchar(80) DEFAULT NULL', 'after' => 'direccionPieMail'],
        ['name' => 'emailPieMail', 'ddl' => '`emailPieMail` varchar(120) DEFAULT NULL', 'after' => 'telefonoPieMail'],
        ['name' => 'e_AnchoPapel', 'ddl' => '`e_AnchoPapel` decimal(8,2) DEFAULT 80', 'after' => 'emailPieMail'],
        ['name' => 'e_AnchoEtiq', 'ddl' => '`e_AnchoEtiq` decimal(8,2) DEFAULT 35', 'after' => 'e_AnchoPapel'],
        ['name' => 'e_AltoEtiq', 'ddl' => '`e_AltoEtiq` decimal(8,2) DEFAULT 20', 'after' => 'e_AnchoEtiq'],
        ['name' => 'e_CantCol', 'ddl' => '`e_CantCol` tinyint unsigned DEFAULT 2', 'after' => 'e_AltoEtiq'],
        ['name' => 'e_GapX', 'ddl' => '`e_GapX` decimal(8,2) DEFAULT 2', 'after' => 'e_CantCol'],
        ['name' => 'e_GapY', 'ddl' => '`e_GapY` decimal(8,2) DEFAULT 2', 'after' => 'e_GapX'],
        ['name' => 'e_MarginTop', 'ddl' => '`e_MarginTop` decimal(8,2) DEFAULT 1', 'after' => 'e_GapY'],
        ['name' => 'e_MarginBottom', 'ddl' => '`e_MarginBottom` decimal(8,2) DEFAULT 0', 'after' => 'e_MarginTop'],
        ['name' => 'e_MarginLeft', 'ddl' => '`e_MarginLeft` decimal(8,2) DEFAULT 2', 'after' => 'e_MarginBottom'],
        ['name' => 'e_MarginRight', 'ddl' => '`e_MarginRight` decimal(8,2) DEFAULT 0', 'after' => 'e_MarginLeft'],
        ['name' => 'e_FontLinea1', 'ddl' => '`e_FontLinea1` tinyint unsigned DEFAULT 18', 'after' => 'e_MarginRight'],
        ['name' => 'e_FontLinea2', 'ddl' => '`e_FontLinea2` tinyint unsigned DEFAULT 12', 'after' => 'e_FontLinea1'],
        ['name' => 'e_FontLinea3', 'ddl' => '`e_FontLinea3` tinyint unsigned DEFAULT 11', 'after' => 'e_FontLinea2'],
        ['name' => 'e_FontLinea4', 'ddl' => '`e_FontLinea4` tinyint unsigned DEFAULT 8', 'after' => 'e_FontLinea3'],
        ['name' => 'e_MaxLargoLinea2', 'ddl' => '`e_MaxLargoLinea2` tinyint unsigned DEFAULT 21', 'after' => 'e_FontLinea4'],
        ['name' => 'e_MaxLargoLinea3', 'ddl' => '`e_MaxLargoLinea3` tinyint unsigned DEFAULT 25', 'after' => 'e_MaxLargoLinea2'],
        ['name' => 'e_Borde', 'ddl' => '`e_Borde` tinyint(1) DEFAULT 0', 'after' => 'e_MaxLargoLinea3'],
        ['name' => 'afipFormatoImpresion', 'ddl' => "`afipFormatoImpresion` varchar(20) NOT NULL DEFAULT 'A4'", 'after' => 'e_Borde'],
    ];

    /** @var array<string, true> */
    private array $columnasConocidas = [];

    public function up(): void
    {
        if (! Schema::hasTable('entorno')) {
            return;
        }

        $this->columnasConocidas = $this->listarColumnas();

        foreach (self::COLUMNAS as $col) {
            $this->ensureColumn($col['name'], $col['ddl'], $col['after']);
        }
    }

    public function down(): void
    {
        // Intencionalmente vacío: columnas pueden ser legacy o creadas por
        // migraciones anteriores; no eliminar datos de un laboratorio en producción.
    }

    private function ensureColumn(string $name, string $ddl, ?string $preferredAfter): void
    {
        if (isset($this->columnasConocidas[$name])) {
            return;
        }

        $afterSql = '';
        if ($preferredAfter !== null && isset($this->columnasConocidas[$preferredAfter])) {
            $afterSql = " AFTER `{$preferredAfter}`";
        }

        DB::statement("ALTER TABLE `entorno` ADD COLUMN {$ddl}{$afterSql}");
        $this->columnasConocidas[$name] = true;
    }

    /** @return array<string, true> */
    private function listarColumnas(): array
    {
        $rows = DB::select(
            'SELECT COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?',
            ['entorno']
        );

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row->COLUMN_NAME] = true;
        }

        return $map;
    }
};
