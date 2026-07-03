# SILAVET — Sistema de Gestión de Laboratorios Veterinarios

Sistema de información para la gestión operativa, analítica y administrativa de
laboratorios veterinarios. Se construye sobre la base de datos MySQL existente
(legacy NeoLab / ScriptCase), con la misma arquitectura y convenciones que **Sistemas Escolares**
(`D:\SCRIPTCASE_DEPLOY\ia\sistema`).

## Estructura del repositorio

```
SILAVET/
├── estructura_bd.sql      # Esquema completo de la BD legacy (referencia canónica)
└── sistema/               # Proyecto Laravel 11 (en construcción)
    ├── app/
    ├── docs/              # Documentación numerada del proyecto
    ├── database/
    ├── resources/
    ├── routes/
    ├── AGENTS.md
    ├── README.md
    └── SECURITY.md
```

## Stack previsto

- **Backend:** PHP 8.2+ · Laravel 11 · Livewire 4
- **Frontend:** Blade · Tailwind CSS 4 · Vite 5
- **Base de datos:** MySQL legacy (`lb_neolab` u homóloga por tenant)
- **Servidor local:** WAMP 64-bit

## Documentación

Ver índice completo en [`sistema/README.md`](sistema/README.md) y carpeta
[`sistema/docs/`](sistema/docs/).

## Estado actual

**Etapa 1 — Núcleo (iniciada):** Laravel 11 + Livewire 4 + Tailwind 4 instalados.
Login de gestión, `LabContext`, dashboard y ABM Clientes operativos sobre BD legacy.

