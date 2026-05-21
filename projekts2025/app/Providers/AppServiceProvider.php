<?php

namespace App\Providers;

use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->configureHttpsUrls();
        $this->configureRelativeViteAssets();
    }

    // Railway serves HTTPS in front of PHP; force https so CSS/JS are not blocked as mixed content
    private function configureHttpsUrls(): void
    {
        if (! $this->app->environment('production')) {
            return;
        }

        $url = config('app.url');

        if ($domain = env('RAILWAY_PUBLIC_DOMAIN')) {
            $url = 'https://'.$domain;
        } elseif (is_string($url) && str_starts_with($url, 'http://')) {
            $url = 'https://'.substr($url, 7);
        }

        if (is_string($url) && $url !== '') {
            config(['app.url' => rtrim($url, '/')]);
            URL::forceRootUrl(rtrim($url, '/'));
        }

        URL::forceScheme('https');
    }

    // Root-relative /build/... URLs always match the page protocol (fixes http assets on https pages)
    private function configureRelativeViteAssets(): void
    {
        if (! $this->app->environment('production')) {
            return;
        }

        $vite = $this->app->make(Vite::class);

        if (! method_exists($vite, 'createAssetPathsUsing')) {
            return;
        }

        $vite->createAssetPathsUsing(
            fn (string $path, ?bool $secure = null) => '/'.ltrim(str_replace('\\', '/', $path), '/')
        );
    }
}
