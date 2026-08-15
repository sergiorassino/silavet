<?php

namespace App\Console\Commands;

use App\Support\Schema\LegacySchemaDiffer;
use App\Support\Schema\SchemaSyncPlan;
use Illuminate\Console\Command;

/**
 * Compara un esquema modelo (NeoLab) con la BD de un laboratorio atrasado
 * y genera SQL aditivo (tablas/columnas/índices/FK faltantes).
 *
 *   php artisan lb:schema-sync civetfranca
 *   php artisan lb:schema-sync lb_civetfranca --from=lb_neolab
 *
 * No ejecuta el SQL salvo --apply (solo un humano; los agentes no deben usarlo).
 */
class SchemaSyncCommand extends Command
{
    protected $signature = 'lb:schema-sync
                            {destino : Slug o nombre de BD a alinear (ej. civetfranca o lb_civetfranca)}
                            {--from=lb_neolab : Esquema modelo}
                            {--output= : Ruta del archivo SQL}
                            {--no-copy-catalog : No copiar filas de permisos_ia/roles}
                            {--apply : Ejecutar el SQL en el destino (humano; irreversible en esquema)}';

    protected $description = 'Compara NeoLab con un laboratorio y genera SQL aditivo (tablas y columnas faltantes).';

    public function handle(LegacySchemaDiffer $differ): int
    {
        $from = (string) $this->option('from');
        $toArg = (string) $this->argument('destino');

        try {
            $differ->assertIdent($from);
            $to = $this->resolveSchema($differ, $toArg);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (! $differ->schemaExists($from)) {
            $this->error("No existe el esquema modelo `{$from}` en este MySQL.");

            return self::FAILURE;
        }

        $this->newLine();
        $this->line("  Modelo : <comment>{$from}</comment>");
        $this->line("  Destino: <comment>{$to}</comment>");
        $this->newLine();

        $plan = $differ->diff($from, $to, ! (bool) $this->option('no-copy-catalog'));
        $this->imprimirResumen($plan);

        $sql = $differ->renderSql($plan);
        $output = $this->option('output')
            ?: database_path('sql/schema_sync_'.$to.'.sql');

        $dir = dirname($output);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($output, $sql);

        $rel = str_replace('\\', '/', ltrim(str_replace(base_path(), '', $output), '\\/'));
        $this->newLine();
        $this->info("  SQL escrito en: {$rel}");
        $this->newLine();
        $this->line('  Siguiente paso (humano):');
        $this->line("    1. Revisar {$rel}");
        $this->line("    2. Ejecutarlo sobre `{$to}`");
        $this->line('    3. php artisan lb:switch <slug> && php artisan lb:migrate-legacy --force');
        $this->newLine();

        if ($this->option('apply')) {
            if (! $this->confirm("  ¿Aplicar el SQL ahora sobre `{$to}`?", false)) {
                $this->line('  Aplicación cancelada. El archivo SQL quedó generado.');

                return self::SUCCESS;
            }

            $differ->apply($plan);
            $this->info("  Cambios aplicados sobre `{$to}`.");
            $this->newLine();
        }

        return self::SUCCESS;
    }

    private function resolveSchema(LegacySchemaDiffer $differ, string $name): string
    {
        $name = trim($name);
        $differ->assertIdent($name);

        if ($differ->schemaExists($name)) {
            return $name;
        }

        $prefixed = str_starts_with($name, 'lb_') ? $name : 'lb_'.$name;
        if ($differ->schemaExists($prefixed)) {
            return $prefixed;
        }

        throw new \RuntimeException(
            "No existe el esquema `{$name}` ni `{$prefixed}` en este MySQL."
        );
    }

    private function imprimirResumen(SchemaSyncPlan $plan): void
    {
        $this->line('  Tablas a crear     : <comment>'.count($plan->createdTableNames).'</comment>');
        foreach ($plan->createdTableNames as $table) {
            $this->line("    + {$table}");
        }

        $porTabla = [];
        foreach ($plan->addColumns as $col) {
            $porTabla[$col['table']][] = $col['column'];
        }
        $this->line('  Columnas a agregar : <comment>'.count($plan->addColumns).'</comment>');
        foreach ($porTabla as $table => $cols) {
            $this->line('    '.$table.': '.implode(', ', $cols));
        }

        $this->line('  Índices a agregar  : <comment>'.count($plan->addIndexes).'</comment>');
        $this->line('  FK a agregar       : <comment>'.count($plan->addForeignKeys).'</comment>');
        $this->line('  Tipos distintos    : <comment>'.count($plan->typeMismatches).'</comment> (no se modifican)');

        if ($plan->isEmpty()) {
            $this->line('  <info>El destino ya tiene las tablas/columnas del modelo.</info>');
        }
    }
}
