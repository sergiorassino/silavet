{{-- Usuario de sesión en el hero. Etiqueta, no saludo (Tesorería / Pacientes / panel). --}}
@php
    $nombreUsuario = trim((string) (labCtx()->usuario()?->apenom ?? ''));
    if ($nombreUsuario === '') {
        $nombreUsuario = 'usuario';
    }
@endphp
<p {{ $attributes->class(['mt-1.5 text-sm text-white/80']) }}>
    Usuario: <span class="font-semibold text-white">{{ $nombreUsuario }}</span>
</p>
