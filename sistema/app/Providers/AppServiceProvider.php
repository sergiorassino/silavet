<?php

namespace App\Providers;

use App\Auth\UsuarioUserProvider;
use App\Support\LabContext;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LabContext::class, function () {
            return LabContext::fromSession();
        });
    }

    public function boot(): void
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $appPath = rtrim((string) (parse_url($appUrl, PHP_URL_PATH) ?: ''), '/');

        if ($appPath !== '') {
            Config::set('session.path', $appPath);

            if (! config('app.asset_url')) {
                Config::set('app.asset_url', $appUrl);
            }

            Config::set('filesystems.disks.public.url', $appUrl.'/storage');

            if (! config('livewire.asset_url')) {
                $livewireJs = config('app.debug') ? 'livewire.js' : 'livewire.min.js';
                Config::set('livewire.asset_url', $appUrl.EndpointResolver::prefix().'/'.$livewireJs);
            }

            URL::forceRootUrl($appUrl);

            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }

        Auth::provider('usuario', function ($app, array $config) {
            return new UsuarioUserProvider();
        });

        Paginator::defaultView('vendor.pagination.vl');
    }
}
