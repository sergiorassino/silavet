<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        /* Marca — --vl-theme-base se sobrescribe desde entorno.colorFondoSistema */
        --vl-theme-base: #0ea5e9;
        --vl-primary: #0ea5e9;
        --vl-accent-sky: #7dd3fc;
        --vl-light-blue: #bae6fd;
        --vl-ice: #e0f7ff;

        /* Fondo app — degradé (un poco más marcado) a partir del color de sistema */
        --vl-app-bg: color-mix(in srgb, var(--vl-theme-base) 18%, white);
        --vl-app-bg-mid: color-mix(in srgb, var(--vl-theme-base) 11%, white);
        --vl-app-bg-end: color-mix(in srgb, var(--vl-theme-base) 5%, white);

        /* Hero — degradé */
        --vl-hero-from: color-mix(in srgb, var(--vl-theme-base) 86%, black);
        --vl-hero-via: color-mix(in srgb, var(--vl-theme-base) 72%, black);
        --vl-hero-to: color-mix(in srgb, var(--vl-theme-base) 52%, black);

        /* Fondo sidebar — degradé (más oscuro arriba, más claro abajo) */
        --vl-sidebar-bg-top: color-mix(in srgb, var(--vl-theme-base) 48%, black);
        --vl-sidebar-bg-mid: color-mix(in srgb, var(--vl-theme-base) 58%, black);
        --vl-sidebar-bg-bottom: color-mix(in srgb, var(--vl-theme-base) 68%, black);

        /* Superficies y texto */
        --vl-hover-bg: rgba(255, 255, 255, 0.10);
        --vl-hover-bg-strong: rgba(255, 255, 255, 0.16);
        --vl-sep: rgba(186, 230, 253, 0.20);
        --vl-sidebar-text: rgba(255, 255, 255, 0.86);
        --vl-sidebar-text-muted: rgba(255, 255, 255, 0.58);

        /* Grupos — efecto cristal sobre celeste */
        --vl-group-text: rgba(224, 247, 255, 0.92);
        --vl-group-bg: rgba(255, 255, 255, 0.06);
        --vl-group-border: rgba(186, 230, 253, 0.18);
        --vl-group-open-bg: rgba(255, 255, 255, 0.11);
        --vl-group-open-border: rgba(125, 211, 252, 0.38);

        /* Ítem activo */
        --vl-link-active-bg: rgba(255, 255, 255, 0.12);
        --vl-link-active-border: #bae6fd;

        --vl-sidebar-w: 24rem;
        --vl-sidebar-w-collapsed: 5rem;
    }
    .vl-main {
        width: 100%;
        min-width: 0;
        transition: transform 200ms ease-in-out, width 200ms ease-in-out;
        transform: translateX(0);
    }
    @media (min-width: 768px) {
        .vl-main {
            transform: translateX(var(--vl-sidebar-w));
            width: calc(100% - var(--vl-sidebar-w));
        }
        .vl-main.is-collapsed {
            transform: translateX(var(--vl-sidebar-w-collapsed));
            width: calc(100% - var(--vl-sidebar-w-collapsed));
        }
    }
    @media (max-width: 767px) {
        .vl-main.is-mobile-open {
            transform: translateX(var(--vl-sidebar-w));
            width: calc(100% - var(--vl-sidebar-w));
        }
    }
</style>
