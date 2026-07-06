<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Agrega tablas y columnas del sistema SILAVET a una BD legacy de NeoLab,
 * sin tocar ni destruir las tablas y datos que ya existen.
 *
 * Uso (después de lb:switch al laboratorio correcto):
 *   php artisan lb:migrate-legacy
 *   php artisan lb:migrate-legacy --dry-run
 *   php artisan lb:migrate-legacy --force
 *
 * Qué hace:
 *   1. Crea la tabla `migrations` si no existe.
 *   2. Marca como "ya ejecutadas" las migraciones boilerplate de Laravel
 *      (users/cache/jobs) que no aplican a la BD legacy con tabla `usuarios`.
 *   3. Corre el resto de migraciones core (idempotentes: hasTable / hasColumn).
 *   4. Corre migraciones tenant del laboratorio activo (si existen).
 */
class MigrateLegacyCommand extends Command
{
    protected $signature = 'lb:migrate-legacy
                            {--dry-run : Mostrar qué migraciones se ejecutarían sin correr nada}
                            {--force   : No pedir confirmación}';

    protected $description = 'Agrega tablas y columnas de SILAVET a una BD legacy de laboratorio veterinario.';

    /**
     * Migraciones de instalación Laravel que no deben correr sobre BD legacy.
     * SILAVET autentica contra `usuarios`, no contra `users`.
     *
     * @var list<string>
     */
    private const FAKE_ON_LEGACY = [
        '0001_01_01_000000_create_users_table',
        '0001_01_01_000001_create_cache_table',
        '0001_01_01_000002_create_jobs_table',
    ];

    public function handle(): int
    {
        $slug = env('TENANT_SLUG', '(sin definir)');
        $db = env('DB_DATABASE', '(sin definir)');

        $this->newLine();
        $this->line("  Tenant activo : <comment>{$slug}</comment>");
        $this->line("  Base de datos : <comment>{$db}</comment>");
        $this->newLine();

        if ($this->option('dry-run')) {
            return $this->dryRun();
        }

        if (! $this->option('force')) {
            $confirm = $this->confirm(
                "  ¿Continuar con la migración de <comment>{$db}</comment>?",
                false
            );
            if (! $confirm) {
                $this->line('  Cancelado.');

                return self::SUCCESS;
            }
        }

        $this->newLine();

        $this->line('<comment>  Paso 1/3</comment> — Preparando tabla migrations y migraciones boilerplate...');
        $migrationsTableCreada = $this->ensureMigrationsTable();
        $boilerplate = $this->fakeBoilerplateMigrations();

        $this->newLine();
        $this->line('<comment>  Paso 2/3</comment> — Corriendo migraciones core de SILAVET...');
        $migracionesAntesCore = $this->migrationNames();
        $this->call('migrate', ['--force' => true]);
        $migracionesCore = $this->nuevasMigraciones($migracionesAntesCore);

        $this->newLine();
        $this->line('<comment>  Paso 3/3</comment> — Corriendo migraciones tenant del laboratorio...');
        $migracionesAntesTenant = $this->migrationNames();
        if (is_dir(database_path('migrations/tenant'))) {
            $this->call('migrate', [
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);
        } else {
            $this->line('           (sin carpeta database/migrations/tenant)');
        }
        $migracionesTenant = $this->nuevasMigraciones($migracionesAntesTenant);

        $this->newLine();
        $this->info('  Migración completada.');
        $this->newLine();
        $this->imprimirResumenEjecucion($migrationsTableCreada, $boilerplate, $migracionesCore, $migracionesTenant);
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * @return array{registradas: list<string>, ya_existian: list<string>}
     */
    private function fakeBoilerplateMigrations(): array
    {
        $registradas = [];
        $yaExistian = [];

        foreach (self::FAKE_ON_LEGACY as $migration) {
            $alreadyRan = DB::table('migrations')
                ->where('migration', $migration)
                ->exists();

            if ($alreadyRan) {
                $yaExistian[] = $migration;

                continue;
            }

            $batch = (int) DB::table('migrations')->max('batch') + 1;

            DB::table('migrations')->insert([
                'migration' => $migration,
                'batch' => $batch,
            ]);

            $registradas[] = $migration;
        }

        if ($registradas === [] && $yaExistian === []) {
            $this->line('           Sin migraciones boilerplate que registrar.');
        } else {
            foreach ($registradas as $migration) {
                $this->line("           Registrada como ejecutada: {$migration}");
            }
            if ($yaExistian !== []) {
                $this->line('           Boilerplate ya registrada. OK.');
            }
        }

        return [
            'registradas' => $registradas,
            'ya_existian' => $yaExistian,
        ];
    }

    private function ensureMigrationsTable(): bool
    {
        if (DB::getSchemaBuilder()->hasTable('migrations')) {
            return false;
        }

        DB::getSchemaBuilder()->create('migrations', function ($table) {
            $table->increments('id');
            $table->string('migration');
            $table->integer('batch');
        });

        $this->line('           Tabla `migrations` creada. OK.');

        return true;
    }

    /** @return list<string> */
    private function migrationNames(): array
    {
        if (! DB::getSchemaBuilder()->hasTable('migrations')) {
            return [];
        }

        return DB::table('migrations')
            ->orderBy('id')
            ->pluck('migration')
            ->all();
    }

    /**
     * @param  list<string>  $antes
     * @return list<string>
     */
    private function nuevasMigraciones(array $antes): array
    {
        $setAntes = array_flip($antes);

        return array_values(array_filter(
            $this->migrationNames(),
            fn (string $migration) => ! isset($setAntes[$migration])
        ));
    }

    /**
     * @param  array{registradas: list<string>, ya_existian: list<string>}  $boilerplate
     * @param  list<string>  $migracionesCore
     * @param  list<string>  $migracionesTenant
     */
    private function imprimirResumenEjecucion(
        bool $migrationsTableCreada,
        array $boilerplate,
        array $migracionesCore,
        array $migracionesTenant
    ): void {
        $huboCambios = $migrationsTableCreada
            || $boilerplate['registradas'] !== []
            || $migracionesCore !== []
            || $migracionesTenant !== [];

        $this->line('  Resumen de esta ejecución:');

        if (! $huboCambios) {
            $this->line('    Sin cambios: la BD ya estaba al día.');

            return;
        }

        if ($migrationsTableCreada) {
            $this->line('    • Tabla <comment>migrations</comment> creada.');
        }

        if ($boilerplate['registradas'] !== []) {
            $cantidad = count($boilerplate['registradas']);
            $this->line("    • Boilerplate Laravel omitida: {$cantidad} migración".($cantidad === 1 ? '' : 'es').'.');
        }

        $this->imprimirMigracionesDelPaso('Core SILAVET', $migracionesCore);
        $this->imprimirMigracionesDelPaso('Tenant del laboratorio', $migracionesTenant);
    }

    /** @param  list<string>  $migraciones */
    private function imprimirMigracionesDelPaso(string $etiqueta, array $migraciones): void
    {
        if ($migraciones === []) {
            $this->line("    • {$etiqueta}: sin migraciones pendientes.");

            return;
        }

        $cantidad = count($migraciones);
        $this->line("    • {$etiqueta}: {$cantidad} migración".($cantidad === 1 ? '' : 'es').' aplicada'.($cantidad === 1 ? '' : 's').':');

        foreach ($migraciones as $migration) {
            $this->line("        - {$migration}");
        }
    }

    private function dryRun(): int
    {
        $this->line('  <comment>Modo --dry-run: no se ejecuta nada.</comment>');
        $this->newLine();

        $this->line('  Lo que haría lb:migrate-legacy:');
        $this->newLine();
        $this->line('  1. Crear tabla `migrations` si no existe');
        $this->newLine();
        $this->line('  2. Registrar como ejecutadas (sin correr) las migraciones boilerplate de Laravel:');
        foreach (self::FAKE_ON_LEGACY as $migration) {
            $this->line("     → {$migration}");
        }
        $this->newLine();
        $this->line('  3. php artisan migrate --force');
        $this->line('     → Columnas/tablas nuevas de SILAVET (idempotentes)');
        $this->newLine();
        $this->line('  4. php artisan migrate --path=database/migrations/tenant --force');
        $this->line('     → Migraciones exclusivas del laboratorio (si existen)');
        $this->newLine();
        $this->line('  <info>Para ejecutar:</info> php artisan lb:migrate-legacy --force');
        $this->newLine();

        return self::SUCCESS;
    }
}
