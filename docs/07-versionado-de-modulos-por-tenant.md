# Personalización por laboratorio (tenant)

> Cómo diferenciar funcionalidades entre laboratorios sin afectar a los demás.
> Antes de tocar un módulo compartido, leer este documento.
> Basado en `docs/07-versionado-de-modulos-por-tenant.md` de Sistemas Escolares.

---

## 1. Modelo de despliegue

Cada laboratorio es un **tenant** identificado por `TENANT_SLUG` en `.env`:

- **Base de datos propia** (`DB_DATABASE`, habitualmente `lb_{slug}` o nombre legacy).
- **Mismo código** Laravel en la raíz del repo (una instalación / carpeta por laboratorio).
- **Overrides livianos** versionados en `config/tenants/{slug}.php`.

No usamos multi-tenant en una sola BD con `tenant_id` en cada fila: el aislamiento
fuerte es **instalación (o entorno) + BD separada**.

En desarrollo local: `php artisan lb:switch {slug}` cambia `TENANT_SLUG` y
`DB_DATABASE` en el `.env` activo (por implementar).

---

## 2. Qué **no** usamos

- Paquetes Composer opcionales por tenant (`packages/modulo-*`).
- Vistas duplicadas en `resources/views/custom/{slug}/`.
- Ramas de código `if (tenant === 'x')` en Blade.

La carpeta `packages/` queda vacía a propósito (`.gitkeep`).

---

## 3. Capas de personalización (de menor a mayor impacto)

### 3.1 Configuración en archivos (`config/tenant.php` + `config/tenants/{slug}.php`)

Merge recursivo del archivo del slug sobre defaults.

```php
// config/tenants/neolab.php — ejemplo
return [
    'informes' => [
        'mostrar_segunda_unidad' => true,
    ],
    'portal_cliente' => [
        'permite_descarga_excel' => true,
    ],
];
```

**Regla:** en `config/tenants/{slug}.php` declarar **solo** lo que difiere del default.

Usos típicos: URLs de LIS externos, flags de informe, textos legales, límites.

### 3.2 Parametrización en BD (`entorno`, permisos)

- Logo, colores, pie de informe, SMTP → `entorno`.
- Permisos por usuario → `usuarios.permisos_ia`.
- Precios de lista → `tipodeterminaciones.precio`; descuento por cliente → `clientes.descuento`.
- `estimacioncostos` → solo compatibilidad con el sistema viejo (no usar en módulos nuevos).

### 3.3 Variantes de implementación (registry pattern)

Cuando un módulo tenga implementaciones distintas (ej. formato de informe por
laboratorio), usar clave `implementacion` en config y registry PHP:

```php
// config/tenant.php
'informes' => [
    'implementacion' => 'estandar', // o 'neolab_legacy'
],
```

No bifurcar lógica en vistas.

**Ejemplo implementado:** número de protocolo al alta (`fecha_diaria`,
`dual_corto_largo`, …). Detalle completo de formatos, reglas y cómo agregar
variantes: [10-numero-de-protocolo.md](10-numero-de-protocolo.md).

**Ejemplo implementado:** tesorería (`tesoreria_movimientos` vs
`tesoreria_pacientes`). Detalle flags: [11-tesoreria-por-tenant.md](11-tesoreria-por-tenant.md).
Especificación del módulo: [modulos/tesoreria.md](modulos/tesoreria.md).

---

## 4. Identidad por tenant

- Logos en `storage/app/public/entorno/logos/{TENANT_SLUG}/` (previsto).
- Fallback estático en `public/img/`.
- `TENANT_SLUG` debe coincidir entre `.env`, storage y config.

---

## 5. Checklist al agregar un tenant

1. Crear `config/tenants/{slug}.php` con solo diferencias.
2. Configurar `.env`: `TENANT_SLUG`, `DB_DATABASE`, `APP_URL`.
3. Cargar esquema desde `estructura_bd.sql` o BD legacy existente.
4. Ejecutar migraciones aditivas (`lb:migrate-legacy`) — manualmente, no desde agente.
5. Sincronizar catálogo de permisos si aplica.
