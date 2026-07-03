<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SwitchTenantCommand extends Command
{
    protected $signature = 'vl:switch
                            {slug? : Slug del laboratorio destino}
                            {--db= : Nombre explícito de la BD}
                            {--list : Listar tenants conocidos}';

    protected $description = 'Cambia el tenant activo en el .env local y limpia el config cache.';

    /** @var array<string, string> */
    private array $dbMap = [
        'neolab' => 'lb_neolab',
    ];

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->showList();
        }

        $slug = $this->argument('slug');

        if (! $slug) {
            $current = env('TENANT_SLUG', '(no definido)');
            $db = env('DB_DATABASE', '(no definido)');
            $this->info("Tenant activo: {$current}  |  BD: {$db}");
            $this->line('Uso: php artisan vl:switch <slug>');

            return self::SUCCESS;
        }

        $db = $this->option('db') ?: ($this->dbMap[$slug] ?? "lb_{$slug}");

        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            $this->error('.env no encontrado en: '.$envPath);

            return self::FAILURE;
        }

        $content = file_get_contents($envPath);
        $content = $this->replaceLine($content, 'TENANT_SLUG', $slug);
        $content = $this->replaceLine($content, 'DB_DATABASE', $db);
        file_put_contents($envPath, $content);

        $this->call('config:clear');
        $this->call('view:clear');

        $this->info("TENANT_SLUG → {$slug}");
        $this->info("DB_DATABASE → {$db}");

        return self::SUCCESS;
    }

    private function replaceLine(string $content, string $key, string $value): string
    {
        $content = preg_replace(
            '/^#?'.preg_quote($key, '/').'=.*/m',
            "{$key}={$value}",
            $content
        );

        if (! preg_match('/^'.preg_quote($key, '/').'=/m', $content)) {
            $content .= "\n{$key}={$value}";
        }

        return $content;
    }

    private function showList(): int
    {
        $current = env('TENANT_SLUG', '—');
        $known = array_unique(array_merge(array_keys($this->dbMap), ['neolab', 'default']));
        sort($known);

        foreach ($known as $slug) {
            $db = $this->dbMap[$slug] ?? "lb_{$slug}";
            $marker = $slug === $current ? ' ← activo' : '';
            $this->line("  {$slug}  (BD: {$db}){$marker}");
        }

        return self::SUCCESS;
    }
}
