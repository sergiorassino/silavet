# Modelo de Datos — Núcleo

> Referencia completa del esquema: `estructura_bd.sql` en la raíz del proyecto
> (`D:\SILAVET\estructura_bd.sql`).

---

## 1. Tablas de Parametrización y Catálogo

| Tabla                  | Descripción                                              | PK                      |
|------------------------|----------------------------------------------------------|-------------------------|
| `entorno`              | Configuración institucional del laboratorio (logo, mail, pie de informe, fórmulas) | `id` |
| `especies`             | Especies animales (canino, felino, equino, etc.)         | `idEspecies`            |
| `razas`                | Razas por especie                                        | `idRazas`               |
| `tipodeterminaciones`  | Tipos de análisis / perfiles (hemograma, perfil hepático, etc.) | `idTipodeterminaciones` |
| `grupos`               | Agrupación de ítems en informes                          | `idGrupos`              |
| `itemsinforme`         | Ítems analíticos del informe (parámetros, textos, refs. por especie) | `idItems` |
| `renglonesxdeterminacion` | Plantilla de ítems por tipo de determinación        | `id`                    |
| `derivaciones`         | Destinos de derivación externa                           | `idDerivaciones`        |
| `equipos`              | Equipos / analizadores                                   | `id`                    |
| `requerimientos`       | Requisitos de muestra (ayuno, volumen, etc.)             | `id`                    |
| `reqxtipodet`          | Requisitos asociados a cada tipo de determinación        | `id`                    |
| `mediodepago`          | Medios de pago                                           | `id`                    |
| `cuentas`              | Plan de cuentas (nivel 1)                                | `id`                    |
| `cuentasdetalle`       | Subcuentas                                               | `id`                    |
| `roles`                | Roles de usuario                                         | `id`                    |
| `usermenu`             | Ítems de menú por rol (legacy)                           | `id`                    |

### Relaciones de parametrización

```
especies ─1:N─► razas
grupos ─1:N─► itemsinforme
tipodeterminaciones ─1:N─► renglonesxdeterminacion ──► itemsinforme
tipodeterminaciones ─1:N─► reqxtipodet ──► requerimientos
itemsinforme ─1:N─► rangovalores (refs. por especie/sexo)
reactivos ─N:M─► tipodeterminaciones (reactivoxdeterminacion)
```

---

## 2. Tablas Operativas (Protocolos y Resultados)

| Tabla             | Descripción                                                         | PK            |
|-------------------|---------------------------------------------------------------------|---------------|
| `clientes`        | Veterinarias, clínicas u otros clientes del laboratorio             | `idClientes`  |
| `pacientes`       | **Protocolo / caso analítico** (animal + propietario + estado de pago) | `idPacientes` |
| `determinaciones` | Determinaciones pedidas dentro de un protocolo                      | `idDeterminaciones` |
| `renglones`       | Valores cargados por ítem (resultados del informe)                  | `idRenglones` |
| `imagenesxrenglon`| Imágenes adjuntas a renglones (microscopía, etc.)                   | `id`          |
| `notificaciones`  | Avisos al cliente sobre protocolos                                  | `id`          |

> **Nota terminológica:** en el dominio legacy, `pacientes` no es un paciente
> humano sino el **protocolo de laboratorio** (caso analítico) asociado a un
> animal, propietario y cliente veterinario.

### Relaciones operativas

```
clientes ─1:N─► pacientes ─1:N─► determinaciones
pacientes ─1:N─► renglones
determinaciones ──► tipodeterminaciones
determinaciones ──► derivaciones
renglones ──► itemsinforme / grupos / tipodeterminacion
clientes ─1:N─► estimacioncostos (legacy; solo compatibilidad con sistema viejo)
```

---

## 3. Tabla `entorno` — Configuración del Laboratorio

La tabla `entorno` almacena la configuración institucional en **un registro**
(típicamente `id = 1`).

### Campos clave

| Campo / grupo        | Descripción                                              |
|----------------------|----------------------------------------------------------|
| `formulas`           | Fórmulas de cálculo (texto legacy)                       |
| `nombreListaPrecio`  | Nombre de la lista de precios activa                     |
| `logo`, `fondo`      | Archivos de identidad visual para informes               |
| `direLabo`, `teleLabo`, `emailLabo` | Datos de contacto del laboratorio     |
| `colorInforme`       | Color de acento en informes PDF/HTML                     |
| `texto*footer*`, `firma*` | Pie de informe y firmas profesionales               |
| `*Mail`              | Configuración SMTP para envío de informes                |

Equivalente funcional de la tabla `ento` en Sistemas Escolares.

---

## 4. Tablas de Autenticación y Usuarios

| Tabla      | Descripción                                                  |
|------------|--------------------------------------------------------------|
| `usuarios` | Usuarios del sistema (personal de laboratorio y clientes)    |
| `roles`    | Catálogo de roles (`idRoles` en `usuarios`)                  |

### Campos relevantes de `usuarios`

| Campo          | Uso                                                         |
|----------------|-------------------------------------------------------------|
| `apenom`       | Nombre del usuario                                          |
| `dni`          | Identificador de login                                      |
| `password`     | Contraseña en texto plano (`varchar(10)`)                   |
| `idRoles`      | Rol → menú y permisos                                       |
| `idClientes`   | Si el usuario es cliente veterinario, FK a `clientes`       |
| `permisoAfip`  | Flag legacy para operaciones de facturación                 |
| `cuit`, `PtoVta`, `key`, `crt` | Datos AFIP del emisor                  |

Detalle de auth y permisos: [03-autenticacion-y-permisos.md](03-autenticacion-y-permisos.md).

---

## 5. Tablas de Facturación y Stock

| Tabla              | Descripción                                    |
|--------------------|------------------------------------------------|
| `compafip`         | Comprobantes electrónicos AFIP                 |
| `estimacioncostos` | Legacy: carrito temporal de estimaciones (no usar en versión nueva) |
| `reactivos`        | Stock de reactivos                             |
| `reactivoxdeterminacion` | Consumo de reactivos por tipo de análisis |

---

## 6. Convenciones de Eloquent para tablas legacy

Los modelos Eloquent deben respetar la estructura existente:

```php
// Ejemplo: modelo para tabla legacy
class Paciente extends Model
{
    protected $table = 'pacientes';
    public $timestamps = false;
    protected $primaryKey = 'idPacientes';

    protected $fillable = [
        // listar campos explícitamente — NO usar $guarded = []
    ];
}
```

- **No usar** convenciones de timestamps automáticos.
- **No usar** pluralización automática — definir `$table` explícitamente.
- **Definir `$fillable`** explícitamente en cada modelo (seguridad mass-assignment).
- Respetar nombres de PK legacy (`idPacientes`, `idClientes`, etc.).

---

## 7. Índice completo de tablas

| Tabla | Dominio |
|-------|---------|
| `clientes` | Clientes |
| `compafip` | Facturación AFIP |
| `cuentas` / `cuentasdetalle` | Contabilidad |
| `derivaciones` | Parametrización |
| `determinaciones` | Operativo |
| `entorno` | Configuración |
| `equipos` | Parametrización |
| `especies` / `razas` | Parametrización |
| `estimacioncostos` | Legacy (compatibilidad) |
| `grupos` | Informes |
| `imagenesxrenglon` | Resultados |
| `itemsinforme` | Informes |
| `mediodepago` | Parametrización |
| `notificaciones` | Portal clientes |
| `pacientes` | Protocolos |
| `rangovalores` | Referencias |
| `reactivos` / `reactivoxdeterminacion` | Stock |
| `renglones` / `renglonesxdeterminacion` | Resultados / plantillas |
| `requerimientos` / `reqxtipodet` | Parametrización |
| `roles` / `usermenu` | Auth |
| `tipodeterminaciones` | Parametrización |
| `usuarios` | Auth |
