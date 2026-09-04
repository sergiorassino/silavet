# Módulo: Carga de resultados (informe)

En UI: **Carga de resultados** de un protocolo (`pacientes`).  
Código: valores en `renglones.valor` / `valor2`, script legacy `entorno.formulas`,
y (opcionales por tenant) automatización Serie Roja / Serie Blanca + importación
desde autoanalizadores.

Variantes por laboratorio: [../07-versionado-de-modulos-por-tenant.md](../07-versionado-de-modulos-por-tenant.md).  
Rangos usados por hemograma auto: [valores-referencia.md](valores-referencia.md).  
Qué determinaciones están pedidas (materializa `renglones`): [carga-determinaciones-paciente.md](carga-determinaciones-paciente.md).

## Propósito

Permitir cargar y editar los resultados analíticos de un protocolo: un input por
ítem de `itemsinforme` (materializado en `renglones`), con:

1. **Cálculos legacy** vía `function formulas()` guardada en `entorno.formulas`
   (VCM, CHCM, absolutos del diferencial, plaquetas desde conteo manual, coloreo, etc.).
2. **Serie Roja / Serie Blanca** (opcional): texto interpretativo según
   `rangovalores` + mapa rol → `idItems` del tenant.
3. **Autoanalizadores** (opcional): importar CSV/TXT/SHD a `renglones`.

No gestiona el pedido de determinaciones. No emite el PDF del informe (otro módulo).

Entrada: listado de protocolos / día / derivaciones → icono resultados →
`route('protocolos.resultados', $idPacientes)`.

## Modalidades / variantes

La pantalla base es **igual para todos** los labs. Lo que cambia por tenant son
flags de config (merge `config/tenant.php` ← `config/tenants/{slug}.php`).

| Variante | Config | Efecto |
|----------|--------|--------|
| Base (todos) | — | Formulario + `entorno.formulas` + guardado a `renglones` |
| Sin hemograma auto (default) | `tenant.hemograma_auto.activo` = `false` | No se completa Serie Roja/Blanca por rangos |
| Con hemograma auto | `activo` = `true` + mapa `items` completo | Clasifica orígenes y escribe texto en destinos |
| Sin autoanalizadores (default) | `tenant.autoanalizadores.aparatos` vacío / inactivos | Sin botón “Autoanalizadores” |
| Con autoanalizadores | uno o más aparatos con `activo` = `true` | Botón + modal de importación |

### Matriz de tenants (estado actual)

| Tenant | `hemograma_auto` | Autoanalizadores |
|--------|:----------------:|:----------------:|
| **labvetciudad** | sí (mapa idItems abajo) | sí (Mindray BC-20, Incaa, Metrolab CM 250) |
| **civetfranca** | no (default) | sí (Edan H 30, Geo MC, Incca, Incca v2) |
| alqu, neolab, lvm, lam, laboratoriosiv, … | no (default) | según su `config/tenants/{slug}.php` |
| default (`config/tenant.php`) | `activo` = `false`, roles → `null` | `aparatos` vacío |

**Activar hemograma en otro lab:** en `config/tenants/{slug}.php` poner
`hemograma_auto.activo = true` y el mapa de **sus** `idItems` (no copiar a ciegas
los de labvetciudad: el catálogo `itemsinforme` es por BD).

### Config hemograma (labvetciudad)

```php
// config/tenants/labvetciudad.php
'hemograma_auto' => [
    'activo' => true,
    'items' => [
        'hto' => 3,
        'eritrocitos' => 1,
        'hb' => 29,
        'vcm' => 2,
        'chcm' => 5,
        'plaquetas' => 18,
        'plaquetas_conteo_manual' => 239,
        'leucocitos' => 6,
        'neutrofilos' => 10,
        'bandas' => 9,
        'linfocitos' => 7,
        'eosinofilos' => 11,
        'basofilos' => 12,
        'monocitos' => 8,
        'serie_roja' => 209,
        'serie_blanca' => 210,
    ],
],
```

Helpers: `HemogramaAutoConfig`, `HemogramaAutoPayload`.

**Identificación:** nunca por nombre de determinación ni por `tipodeterminaciones`.
Solo **rol semántico → `idItems`** (`itemsinforme`) declarado en el tenant.
Si faltan `serie_roja` o `serie_blanca` en el mapa (o `activo` es false), el
payload sale con `activo: false` y el JS no escribe leyendas.

## Actores y permisos

| Actor | Permiso | Alcance |
|-------|---------|---------|
| Staff (Menú de Laboratorio) | `PermisosIaCatalog::RESULTADOS` + `menu.portal:staff` | Ruta `protocolos.resultados`; `abort_unless` en Livewire |
| Usuario cliente / portal | — | **No** usa esta pantalla |

`labCtx()`: si el usuario es cliente, el protocolo debe estar en su `idClientes`.  
Pagos globales (`Paciente::esPagoGlobal()`): **404**.

Rate limit guardar: 30/min por usuario (`prot-resultados-save:*`).  
Diálogos: `vl-swal-*` / `vlSwal*` (éxito al guardar sin salir).  
Atajos: **F9** guardar, **F10** guardar y salir; navegación Enter/↑↓ entre campos.
Modal autoanalizadores: teclado completo (Tab/Enter/flechas/Esc; ver §E).  
UI: cabecera del protocolo + barra de título/acciones (`vl-carga-sticky`) quedan
fijas al scroll vertical y horizontal de la página.

## Tablas y campos críticos

| Tabla | Rol |
|-------|-----|
| `renglones` | Fuente de verdad de valores cargados (`valor`, `valor2`, `idItems`, `tipoItem`, `mostrar`). `valor` y `valor2` son `text`. El tope de validación al guardar es la longitud **real** de la columna (hasta aplicar la migración, un lab viejo puede seguir en `varchar(100)`). |
| `itemsinforme` | Catálogo de campos (`idItems`, `actualiza`, `estiloNum`, `nombreItem`, `textos`, `mostrar`) |
| `entorno.formulas` | Script JS legacy inyectado en el navegador como `window.formulas` |
| `pacientes` | Especie (`idEspecies`), sexo (texto), estado del protocolo |
| `rangovalores` | Min/max por `idItems` + `idEspecies` + `idSexos` (**solo** hemograma auto) |
| `especies` / `sexos` | Catálogos; sexo → `idSexos` vía `SexoCatalog::idSexos()` |

### Tipos de ítem en el formulario (`tipoItem`)

| `tipoItem` | UI | Dispara `formulas()` al cambiar |
|------------|----|----------------------------------|
| 1 | Input texto (`id` = `idItems`) | Si `actualiza = 1` **o** el `idItems` es `plaquetas_conteo_manual` del mapa |
| 4 | Select (`id_2`) + textarea (`id`) | **No** llama a `formulas()` (ScriptCase: evita pisar plaquetas). Sí refresca Serie Roja/Blanca si hemograma activo |
| 7 | Readonly (resultado de fórmula) | — |
| 8 | Textarea (Serie Roja/Blanca, textos largos) | No |
| 9 | % editable (`id`) + absoluto oculto (`id_2`) + display (`id_T`) | Sí (`formatearYCalcular`) |
| 6 | % readonly (`id`) + absoluto oculto (`id_2`) + display (`id_T`); ambos los escribe `formulas()` | No (no dispara al editar; se recalcula al cambiar orígenes) |
| 3 / 5 / 10 | Título / línea / imágenes | Sin valor de resultado numérico |

`renglones.mostrar` / `itemsinforme.mostrar` **no** ocultan campos en esta pantalla
(Sí/No = solo visibilidad del informe PDF). Si `renglones.mostrar = 0`, el ítem
sigue en el form con la leyenda **No se muestra en el informe** al lado del
nombre. Tipo 2 (valor fijo) sigue sin listarse en carga, igual que el sistema
anterior.

IDs DOM: el operador edita `#idItems`; el diferencial absoluto vive en `#idItems_2`
(y se refleja en `#idItems_T` disabled). `formulas()` escribe `_2` y `_T`.

## Flujo principal

### A. Arranque de la pantalla

1. `RenglonesMaterializer::asegurarParaPaciente` (por si faltan filas).
2. Alpine `vlCargaResultados` recibe:
   - `formulas` ← `entorno.formulas` (sin tags `<script>`).
   - `contextoFormulas`: `especieNombre` (nombre real, **no** `"—"`), `idEspecies`,
     `idSexos`, `idConteoPlaquetas`.
   - `hemogramaAuto` ← `HemogramaAutoPayload::paraPaciente`.
3. Setea globals ScriptCase: `JSespecie`, `JSidEspecies`, `JSidSexos`
   (**siempre**, aunque hemograma esté off — las fórmulas de plaquetas usan
   `JSespecie === 'Canino'|'Felino'`).
4. Inyecta el script de entorno → `window.formulas` (función **pura**, sin envolver).
5. `instalarHemogramaAuto` registra el runner `__vlCorrerFormulasYHemograma`
   (y `__vlAplicarHemogramaAuto` si está activo).
6. Focus al primer campo + una corrida del runner **solo con `formulas()`**
   (`aplicarHemograma: false`). Serie Roja/Blanca **no** se escribe al entrar.

### B. Runner de cálculo (orden fijo)

`correrFormulas(opciones)` → `__vlCorrerFormulasYHemograma(opciones)`:

1. Guarda el valor actual de plaquetas (si el mapa lo declara).
2. Ejecuta `window.formulas()` en `try/catch` (un error del entorno **no** corta el resto).
3. Si el cálculo dejó plaquetas vacío/`PENDIENTE`/`NaN` **y** no hay conteo manual
   usable → **restaura** el valor previo (el script de entorno pisa plaquetas cuando
   el conteo es inválido).
4. Si hemograma activo → marca en rojo/negrita (`vl-fuera-rango`) los campos cuyo
   valor está fuera de `rangovalores` (un valor: `#idItems`; diferencial: `#id_T`
   / `#id_2`). También al entrar al form.
5. Si `opciones.aplicarHemograma !== false` **y** hemograma activo → aplica
   Serie Roja / Serie Blanca.

**Cuándo corre cada parte**

| Momento | `formulas()` | Estilo fuera de rango | Serie Roja/Blanca |
|---------|:------------:|:---------------------:|:-----------------:|
| Arranque del form | sí | sí | **no** |
| `@change` de campo que dispara cálculo (`formatearYCalcular`) | sí | sí | sí (si tenant activo) |
| Tipo 4 (select/input plaquetas) | **no** | sí | sí (`__vlAplicarHemogramaAuto`) |
| Guardar (F9 / F10) | sí | sí | **no** (leyendas ya al editar) |

**No** hay listener `change` delegado en todo el form: eso duplicaba `formulas()`
y disparaba en campos con `actualiza = 0` o al editar los destinos de texto.

### C. Hemograma auto — clasificación

Condiciones: `activo`, mapa con `serie_roja` + `serie_blanca`, `idEspecies > 0`.

1. Para cada origen del mapa, busca rango en el payload:
   - Match preferido: `idItems` + `idEspecies` + `idSexos`.
   - Si el paciente **no tiene sexo** (`idSexos = 0`): fallback al primer rango de
     esa especie (en labs actuales los 4 sexos suelen compartir min/max).
   - Si hay sexo pero no hay fila para ese sexo: **no** clasifica ese ítem.
2. Lectura del valor a comparar:
   - Un valor (hto, leucocitos, …): campo `#idItems`.
   - Diferencial (`neutrofilos`, `bandas`, `linfocitos`, `eosinofilos`, `basofilos`,
     `monocitos`): **absoluto** en `#idItems_2` o `#idItems_T` (los
     `rangovalores` de labvetciudad están en absolutos; `formulas()` calcula esa
     columna desde el %). Comparar el % contra rangos absolutos da falsos
     “penia/filia”.
   - Plaquetas: número entre paréntesis si existe; si no, el texto del campo
     (soporta miles `es-AR` tipo `54.000`).
3. `PENDIENTE` / vacío / `NaN` → no clasifica.
4. Bajo / normal / alto según `valorMin` / `valorMax`.

#### Frases Serie Roja (destino `serie_roja`)

| Condición | Texto base |
|-----------|------------|
| Hto bajo + eritrocitos bajo + Hb bajo | `Anemia {VCM} {CHCM}.` |
| Hto bajo (sin los tres bajos) | `Anemia.` |
| Hto alto | `Policitemia` |
| Hto normal | `Normal.` |
| Hto sin clasificar | *(no escribe nada en el destino)* |

VCM → `microcítica` / `normocítica` / `macrócitica`.  
CHCM → `hipocrómica` / `normocrómica` / `hipercrómica`.  
Plaquetas bajo/alto se **anexan** solo si ya hay base: `Trombocitopenia.` /
`Trombocitosis.` (si no hay Hto clasificado, no hay leyenda de plaquetas sola).

#### Frases Serie Blanca (destino `serie_blanca`)

Solo se concatenan desviaciones (bajo/alto). “Normal” en un ítem **no** agrega texto.

| Rol | Bajo | Alto |
|-----|------|------|
| leucocitos | Leucopenia. | Leucocitosis. |
| neutrofilos | Neutropenia. | Neutrofilia. |
| bandas | — | Desvío a la izquierda regenerativo. |
| linfocitos | Linfopenia. | Linfocitosis. |
| eosinofilos | Eosinopenia. | Eosinofilia. |
| basofilos | — | Basofília. |
| monocitos | Monocitopenia. | Monocitosis. |

- Si **ningún** origen pudo clasificarse → no toca el destino (preserva `PENDIENTE`).
- Si hubo clasificaciones pero ninguna frase (todo en rango) → `Normal.`

#### Texto manual del operador

Al escribir el destino se quitan frases automáticas conocidas (incluyendo el
punto opcional que las sigue, para no dejar `.` huérfanos al inicio) y
marcadores `{{AUTO:id}}…{{/AUTO:id}}` si existieran; luego se recombina
manual + auto. Si el manual queda solo puntos/espacios, se ignora. Si el auto
es solo `Normal.`, se conserva el manual.

### D. Guardar

1. Runner de cálculo **solo `formulas()`** (`aplicarHemograma: false`; el texto
   Serie Roja/Blanca ya se actualizó al editar orígenes).
2. Recolecta `[data-renglon][data-campo=valor|valor2]` del DOM (`wire:ignore` en filas).
3. `ResultadosGuardarServicio` → UPDATE `renglones` + estado en `pacientes`.
4. Sin salir: `vl-swal-exito`. Con salir: redirect al listado de origen
   (desde derivaciones: misma agrupación/página + `foco` = `idPacientes` para
   dejar el cursor sobre la fila, igual que en Pacientes). Desde Pacientes:
   se conservan **Por día** (fecha) e **Historial** con rango Desde/Hasta
   (sesión + query) hasta que el usuario cambie el filtro o salga del módulo
   (otro ítem del menú).

### E. Autoanalizadores (si hay aparatos activos)

Modal: elegir aparato + archivo reciente o upload → `AutoanalizadorImportador`
escribe valores en `renglones` → redirect a la misma pantalla. Detalle de drivers
y overrides: `config/tenants/{slug}.php` → `autoanalizadores` +
`App\Support\Autoanalizadores\*`.

Operación por teclado en el modal: foco al abrir en **Elegir archivo…**; **Tab** cicla
controles; **Enter** / **↑↓** entre upload → aparato → archivo → Importar;
**←→** cambian opción en los selects; **Enter**/**Espacio** en “Elegir archivo…”
abre el file picker; **Esc** cierra. Tras subir, mensaje inline (sin Swal) y foco
en el select de archivo.
## Roles del mapa `items`

| Rol | Uso |
|-----|-----|
| `hto`, `eritrocitos`, `hb`, `vcm`, `chcm`, `plaquetas` | Orígenes Serie Roja |
| `plaquetas_conteo_manual` | Auxiliar: dispara `formulas()` aunque `actualiza = 0` |
| `leucocitos`, `neutrofilos`, `bandas`, `linfocitos`, `eosinofilos`, `basofilos`, `monocitos` | Orígenes Serie Blanca |
| `serie_roja`, `serie_blanca` | Destinos del texto automático (tipo 8) |

`HemogramaAutoConfig::idItemsDisparo()`: todos los ids del mapa **excepto** los
dos destinos (útil para listados / tests; el Blade dispara por `actualiza` +
conteo manual).

## Fuente de verdad

| Dato | Quién escribe | Quién solo lee |
|------|---------------|----------------|
| Valores numéricos / texto de resultado | Operador + `formulas` + hemograma auto + autoanalizador → `renglones` | Informes PDF/HTML |
| Script de cálculos | Admin → `entorno.formulas` (pantalla Script de automatización) | Esta pantalla (inyección JS) |
| Rangos min/max | ABM Valores de referencia → `rangovalores` | Solo JS hemograma auto |
| Mapa rol → idItems | `config/tenants/{slug}.php` | Runtime (`HemogramaAutoConfig`) |
| Estado del protocolo | Selector en el footer → `pacientes.estado` | Listados |

## Archivos clave

| Pieza | Ruta |
|-------|------|
| Livewire | `app/Livewire/Protocolos/PacienteResultados.php` |
| Vista | `resources/views/livewire/protocolos/paciente-resultados.blade.php` |
| Consulta de filas | `app/Support/Resultados/ResultadosCargaConsulta.php` |
| Guardado | `app/Support/Resultados/ResultadosGuardarServicio.php` |
| Materializar renglones | `app/Support/Resultados/RenglonesMaterializer.php` |
| Config hemograma | `app/Support/Resultados/HemogramaAutoConfig.php` |
| Payload JS | `app/Support/Resultados/HemogramaAutoPayload.php` |
| Sexo → idSexos | `app/Support/SexoCatalog.php` |
| JS Alpine + helpers | `resources/js/app.js` (`vlCargaResultados`) |
| JS hemograma | `resources/js/hemograma-auto.js` |
| Defaults | `config/tenant.php` → `hemograma_auto` / `autoanalizadores` |
| Override labvetciudad | `config/tenants/labvetciudad.php` |
| Override civetfranca | `config/tenants/civetfranca.php` (Edan H 30, Geo MC, Incca, Incca v2) |
| Filtros + foco al volver (derivaciones) | `app/Support/Protocolos/DerivacionListadoFiltros.php` |
| Ruta | `routes/web.php` → `protocolos.resultados` |
| Tests config | `tests/Unit/HemogramaAutoConfigTest.php` |

## Cómo activar hemograma en un laboratorio nuevo

1. Confirmar en su BD los `idItems` de Hto, eritrocitos, Hb, VCM, CHCM, plaquetas,
   conteo manual, leucocitos, diferencial, Serie Roja y Serie Blanca.
2. Cargar `rangovalores` para esas especies/sexos (ABM Valores de referencia).
   Diferencial: rangos en **absolutos** (como labvetciudad), no en %.
3. En `config/tenants/{slug}.php`:

```php
'hemograma_auto' => [
    'activo' => true,
    'items' => [ /* rol => idItems de ESE lab */ ],
],
```

4. Verificar que `entorno.formulas` calcule absolutos en `#id_2` / `#id_T` y que
   use `JSespecie` con los **mismos** nombres de `especies.nombre` (p. ej. `Canino`).
5. Probar un protocolo con especie (y sexo si es posible): salir de un campo
   origen y confirmar textos en 209/210 (o los ids destino del mapa).

## Qué no hacer / trampas

- **No** aplicar Serie Roja/Blanca al arrancar el form ni al guardar: solo al
  editar orígenes (`formatearYCalcular` / tipo 4). Al entrar sí corre `formulas()`
  y el coloreo fuera de rango (si hemograma activo).
- **No** hardcodear `idItems` en PHP/JS fuera del mapa del tenant.
- **No** `if (tenant === 'labvetciudad')` en Blade: usar payload /
  `config('tenant.hemograma_auto…')`.
- **No** envolver `window.formulas` con la lógica de hemograma: el script de
  entorno debe seguir siendo la función global pura; el runner encadena después.
- **No** volver a poner un `change` delegado en `#vl-form-carga` que llame
  siempre a `formulas()` (duplica corridas y rompe `actualiza = 0` / destinos).
- **No** llamar a `formulas()` desde el change del tipo 4 (select plaquetas):
  sobrescribe la opción del operador.
- **No** clasificar el diferencial leyendo el % (`#idItems`): los rangos están
  en absolutos (`#_2`).
- **No** inventar destinos si faltan `serie_roja` / `serie_blanca` en el mapa.
- **No** recalcular min/max fuera de `rangovalores`.
- Pasar a `JSespecie` el nombre real de especie (`Canino`), nunca el placeholder
  de UI `"—"`.
- Tras tocar `resources/js/**`: `npm run build` e incluir `public/build/` en el
  despliegue.
- **No** filtrar la carga por `renglones.mostrar`. Ese flag solo oculta el
  renglón en el informe PDF (`InformePacienteConsulta`). Auxiliares como
  Plaquetas (conteo manual) deben poder cargarse aunque no se impriman; en el
  form llevan la leyenda “No se muestra en el informe”.
- **No** volver `renglones.valor2` a `varchar(100)`: truncaría observaciones
  largas. Migración: `2026_09_04_000002_widen_renglones_valor2_to_text`.

## Checklist al modificar

1. ¿El cambio respeta el orden runner: `formulas` → preservar plaquetas → estilos → hemograma?
2. ¿Al **entrar** al form corre `formulas()` + estilos (sin Serie Roja/Blanca)?
3. ¿Labs **sin** `hemograma_auto` siguen igual (solo formulas + carga manual)?
4. ¿El mapa del tenant tiene los `idItems` correctos de **su** `itemsinforme`?
5. ¿Hay filas en `rangovalores` para la especie (y sexo) de prueba? Si sexo vacío,
   ¿el fallback por especie sigue siendo aceptable?
6. ¿Tipo 4 sigue sin disparar `formulas()`?
7. ¿Diferencial se lee de `_2`/`_T`?
8. Tras JS/CSS: `npm run build` + `public/build/` en la lista de producción.
9. Actualizar **este** documento si cambian roles, frases, disparos o el contrato
   del runner.
10. ¿Ítems con `mostrar = 0` (p. ej. conteo manual de plaquetas) aparecen en
    carga con la leyenda “No se muestra en el informe” y **no** en el PDF?
11. ¿`renglones.valor2` es `text` (migración `widen_renglones_valor2_to_text`)?
    El tope de validación sigue la columna real de la BD.
