<?php

namespace App\Support\Tutor;

/**
 * Sesión del portal del tutor. No usa el guard `usuarios`.
 */
final class TutorContext
{
    public string $dni = '';

    public string $nombre = '';

    public static function fromSession(): static
    {
        $ctx = new static();
        $ctx->dni = TutorAcceso::dni((string) session('tutor.dni', ''));
        $ctx->nombre = trim((string) session('tutor.nombre', ''));

        return $ctx;
    }

    public static function set(string $dni, string $nombre): void
    {
        session([
            'tutor.dni' => TutorAcceso::dni($dni),
            'tutor.nombre' => trim($nombre),
        ]);
    }

    public static function clear(): void
    {
        session()->forget([
            'tutor.dni',
            'tutor.nombre',
        ]);
    }

    public function isValid(): bool
    {
        return $this->dni !== '';
    }

    public function inicial(): string
    {
        $base = $this->nombre !== '' ? $this->nombre : 'T';

        return mb_strtoupper(mb_substr($base, 0, 1));
    }

    /**
     * @return array{menuLabel: string, navPartial: string, homeRoute: string, collapsedSidebar: bool, sidebarFooter: string}
     */
    public static function layoutParams(): array
    {
        return [
            'menuLabel' => 'Autogestión del tutor',
            'navPartial' => 'layouts.partials.sidebar-nav-tutor',
            'homeRoute' => route('tutor.pacientes'),
            'collapsedSidebar' => true,
            'sidebarFooter' => 'layouts.partials.tutor-sidebar-footer',
        ];
    }
}
