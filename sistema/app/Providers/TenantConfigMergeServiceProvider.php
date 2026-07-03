<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class TenantConfigMergeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $slug = trim((string) config('tenant.slug', ''));
        if ($slug === '') {
            $slug = 'default';
        }

        $tenantFile = config_path("tenants/{$slug}.php");

        if (file_exists($tenantFile)) {
            /** @var array<string, mixed> $overrides */
            $overrides = require $tenantFile;

            config([
                'tenant' => array_replace_recursive(
                    config('tenant', []),
                    $overrides,
                    ['slug' => $slug],
                ),
            ]);
        } else {
            config(['tenant.slug' => $slug]);
        }
    }
}
