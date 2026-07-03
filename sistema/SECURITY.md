## Seguridad del sistema (PHP/MySQL/Laravel/Livewire)

### Alcance

Este documento define un baseline de seguridad para todos los módulos del sistema.

### Reglas obligatorias para módulos futuros (resumen)

- Autenticación y manejo correcto de sesión.
- Autorización / scoping por contexto en consultas y acciones por ID.
- Validación + normalización.
- Escape en Blade, no renderizar HTML sin sanitizar.
- Nada de SQL crudo con input.
- Rate limit en ABM.
- URLs de autogestión y PDFs sin identificadores enumerables.

Detalle completo: `docs/06-reglas-de-seguridad.md`.
