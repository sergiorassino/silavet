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
        // Solo Menú de Clientes (no staff). Default true en config/tenant.php.
        // 'mostrar_lista_precios' => false,
        // 'mostrar_estimacion_costos' => false,
    ],
];
```

**Regla:** en `config/tenants/{slug}.php` declarar **solo** lo que difiere del default.

Usos típicos: URLs de LIS externos, flags de informe, textos legales, límites,
ítems del sidebar de autogestión (`portal_cliente.mostrar_*`).

### 3.2 Parametrización en BD (`entorno`, permisos)

- Logo, colores, pie de informe, SMTP → `entorno`.
- Permisos por usuario → `usuarios.permisos_ia`.
- Precios de lista → `tipodeterminaciones.precio` (lista 1); `precio2` / `precio3`;
  alcance por tenant (`cliente` / `paciente`) → [12-listas-de-precios-por-tenant.md](12-listas-de-precios-por-tenant.md).
  Descuento por cliente → `clientes.descuento`.
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
`tesoreria_pacientes`; flags `mostrar_modulo`, `pago_global` y `columna_pagado`).
Detalle flags: [11-tesoreria-por-tenant.md](11-tesoreria-por-tenant.md).
Especificación del módulo: [modulos/tesoreria.md](modulos/tesoreria.md).

**Ejemplo implementado:** listas de precios (`cliente` default / `paciente`).
Detalle: [12-listas-de-precios-por-tenant.md](12-listas-de-precios-por-tenant.md).
Módulo: [modulos/listas-de-precios.md](modulos/listas-de-precios.md).

**Ejemplo implementado:** automatización Serie Roja / Serie Blanca en carga de
resultados (`tenant.hemograma_auto`). Flag + mapa rol → `idItems` por laboratorio.
Doc: [modulos/carga-resultados.md](modulos/carga-resultados.md).

---

## 4. Identidad por tenant

- Logos en `public/entorno/logos/{TENANT_SLUG}/` y firmas en `public/entorno/firmas/{TENANT_SLUG}/`. En BD (`entorno.logo`, `firmaIzq`, `firmaCentro`, `firmaDer`) solo el nombre original del archivo.
- Fallback estático en `public/img/`.
- `TENANT_SLUG` debe coincidir entre `.env`, storage y config.

---

## 5. Checklist al agregar un tenant

1. Crear `config/tenants/{slug}.php` con solo diferencias.
2. Configurar `.env`: `TENANT_SLUG`, `DB_DATABASE`, `APP_URL`, y el bloque de
   emergencia (`LAB_ORDEN`, `EMERGENCIA_APP_URL`, `EMERGENCIA_DB_*`). Ver
   [13-backup-y-vps-emergencia.md](../13-backup-y-vps-emergencia.md).
3. Cargar esquema desde `estructura_bd.sql` o BD legacy existente.
4. Alinear esquema con NeoLab si el dump está atrasado: `php artisan lb:schema-sync {slug}` (genera SQL aditivo; ejecutar a mano). Luego migraciones SILAVET (`lb:migrate-legacy`) — manualmente, no desde agente. Completan columnas que el dump legacy no traiga (p. ej. `entorno.nombreListaPrecio`).
5. Sincronizar catálogo de permisos si aplica.
