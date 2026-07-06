<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --vl-primary: #0ea5e9;
        --vl-light-blue: #bae6fd;
        --vl-hover-bg: rgba(255, 255, 255, 0.12);
        --vl-sep: rgba(255, 255, 255, 0.14);
        --vl-sidebar-text: rgba(255, 255, 255, 0.9);
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
