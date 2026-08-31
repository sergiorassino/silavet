# Módulo: Hoja de Ruta (Hemograma)

Planilla PDF de trabajo del día para anotar a mano los valores de hemograma
de cada protocolo.

## Propósito

Imprimir una hoja de ruta A4 vertical con un bloque por protocolo de la fecha
elegida en el listado de pacientes. Cada celda queda en blanco para escritura
manual: **blanca** si el ítem está pedido (`renglones.mostrar = 1`), **gris**
si no. No es un informe de cliente ni lleva membrete institucional.

Entrada: Gestión de Pacientes (staff) → vista **Por día** → botón **Hoja de Ruta (Hemograma)**
en el encabezado, junto a Nuevo Paciente. Usa la fecha del selector “Día”
(`fechaVista`). En **Historial** el botón no se muestra.

## Modalidades / variantes

| Variante | Config | Efecto |
|----------|--------|--------|
| Activa (default) | `tenant.hoja_ruta_hemograma.activo` = `true` | Botón visible; PDF con IDs del mapa |
| Inactiva | `activo` = `false` | Sin botón; ruta 404 |
| IDs del catálogo | `columnas` / `especiales` | Remapear `idItems` si el lab no usa el catálogo NeoLab |

Los IDs de la grilla coinciden con el ScriptCase. La columna de citologías
(Líq.Punción / Cit.Oído / Cit.Vaginal / Cit.Piel) **no se imprime**: ese
ancho (18 mm) más 10 mm hacia el margen derecho se reparte en las 16
columnas de determinación. La columna de identificación es 34 mm; cada
columna de resultado 10,375 mm (bloque 200 mm).

A la izquierda, **una sola columna de identificación** (34 mm) reúne
protocolo, paciente, especie · raza, sexo · edad y veterinaria (cliente,
abreviado si es largo). La grilla de determinaciones queda a la derecha.
Debajo: **Obs** y luego **Hemoparásitos**.

| Rol | Título | idItems default |
|-----|--------|-----------------|
| wbc…plt | WBC … PLT (14 cols amarillas) | 6, 7, 8, 9, 10, 11, 1, 2, 3, 29, 4, 5, 13, 18 |
| r_ipr, pt | %R/IPR, PT (azules) | 15, 21 |
| hemoparasitos | Hemoparásitos | 14 |

Un laboratorio con otro `itemsinforme` declara solo el mapa distinto en
`config/tenants/{slug}.php`. No usar `if (tenant === …)` en Blade.

## Actores y permisos

| Actor | Permiso | Alcance |
|-------|---------|---------|
| Staff (Menú de Laboratorio) | `PROTOCOLOS` (3) + `menu.portal:staff` | Botón + PDF del día |
| Usuario cliente / autogestión | — | Sin botón; la ruta aborta 403 |

## Tablas y campos críticos

| Tabla | Rol |
|-------|-----|
| `pacientes` | Protocolos del día (`fechhoy`, `tipoRegistro = 1`, `nombreProtocolo`, `nombre`, `sexo`, `edad`, `idEspecies`, `idRazas`, `idClientes`) |
| `especies` / `razas` / `clientes` | Nombres para la columna de identificación |
| `renglones` | Ítems pedidos: `idItems` con `mostrar = 1` (mismo criterio ScriptCase) |

No escribe nada. No usa `determinaciones` ni valores de `renglones.valor`.

## Flujo principal

1. Staff elige el día en el listado (o deja “hoy”).
2. Clic en **Hoja de Ruta (Hemograma)** → `vl-abrir-url` al PDF.
3. Consulta: protocolos `tipoRegistro = 1` de esa `fechhoy`, orden
   `nombreProtocolo`.
4. Por protocolo, `idItems` de renglones visibles → pinta celdas.
5. 9 bloques por página; fecha centrada y “Pag N” arriba (encabezado 8 mm).
6. Si no hay protocolos, PDF con solo el encabezado de fecha.

Rate limit: 20/min al abrir y al generar el PDF.

## Fuente de verdad

| Dato | Quién escribe | Quién solo lee |
|------|---------------|----------------|
| Protocolos del día | Alta de pacientes | Esta planilla |
| Ítems pedidos / visibles | Carga de determinaciones + visibilidad de informe | Esta planilla (`renglones.mostrar`) |
| Valores de hemograma | Carga de resultados (otro módulo) | **No** se imprimen aquí |

## Archivos clave

- `app/Support/Protocolos/HojaRutaHemogramaConfig.php`
- `app/Support/Protocolos/HojaRutaHemogramaConsulta.php`
- `app/Support/Protocolos/HojaRutaHemogramaTcpdf.php`
- `app/Http/Controllers/Protocolos/HojaRutaHemogramaPdfController.php`
- `app/Livewire/Protocolos/PacienteIndex.php` (`abrirHojaRutaHemograma`)
- `resources/views/livewire/protocolos/paciente-index.blade.php`
- Ruta: `protocolos.hoja-ruta-hemograma` (`?fecha=Y-m-d`)
- Config: `config/tenant.php` → `hoja_ruta_hemograma`

## Qué no hacer / reglas de negocio

- No agregar membrete / logo / `TcpdfHeaderInstitucional`: es planilla de banco.
- No imprimir resultados (`renglones.valor`): las celdas van vacías.
- No incluir pagos globales ni egresos (`tipoRegistro` ≠ 1).
- No filtrar por el buscador del listado: van **todos** los protocolos del día.
- No hardcodear `idItems` fuera de `tenant.hoja_ruta_hemograma`.
- No usar DomPDF ni vistas Blade para este PDF.
- No mostrar el botón en autogestión ni en la vista Historial.
- No volver a dibujar la columna de citologías (Líq.Punción / Cit.*) sin pedido explícito.
- No separar de nuevo Protocolo / Paciente / Esp: van en la columna única de identificación.

## Checklist al modificar

- [ ] ¿El layout sigue cabiendo en A4 vertical (9 bloques/página)?
- [ ] ¿Las celdas gris/blanco siguen el `mostrar = 1` de renglones?
- [ ] ¿Un lab con otro catálogo puede remapear IDs sin tocar el TCPDF?
- [ ] ¿Sigue sin membrete institucional?
- [ ] ¿El botón respeta `activo`, solo staff y solo la vista Por día?
