<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Manifest PWA con URLs absolutas (respeta subcarpeta de APP_URL).
 * El favicon de solapa usa asset(); sin esto Chrome no encuentra los PNG
 * al “Instalar / Añadir a pantalla de inicio” y dibuja una letra.
 */
class WebManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $name = (string) config('app.name', 'SILAVET');

        return response()->json([
            'name' => $name,
            'short_name' => $name,
            'description' => 'Sistema de laboratorio veterinario',
            'start_url' => url('/'),
            'scope' => url('/'),
            'display' => 'standalone',
            'background_color' => '#FFFFFF',
            'theme_color' => '#0EA5E9',
            'icons' => [
                [
                    'src' => asset('img/icon-192.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('img/icon-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('img/icon-192.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
                [
                    'src' => asset('img/icon-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
