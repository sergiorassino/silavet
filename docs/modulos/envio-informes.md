# Módulo: Envío de informes

En UI: botón **Enviar** (columna ENV.) en Gestión de Pacientes y Derivaciones
(Menú de Laboratorio). Modal «Enviar informe».

No hay pantalla propia: vive en `PacienteIndex` / `DerivacionIndex`.

## Propósito

Enviar el informe PDF del protocolo al **cliente veterinario** y/o al **paciente**
(propietario), por **mail** y/o **WhatsApp Web**, según lo que el tenant habilite.

## Modalidades / variantes

Flags en `config/tenant.php` → `envio_informes`. Un laboratorio declara **solo**
lo que difiere del default (`config/tenants/{slug}.php`).

| Clave | Default | Efecto |
|-------|---------|--------|
| `destinatario_cliente` | `true` | Opción y bloque de contacto Cliente |
| `destinatario_paciente` | `true` | Opción y bloque de contacto Paciente |
| `forma_mail` | `true` | Canal Mail (SMTP de `entorno`) |
| `forma_whatsapp` | `true` | Canal WhatsApp Web |

Si queda un solo destinatario o un solo canal, el modal lo selecciona solo y
oculta el combo correspondiente.

Si **ambos** destinos (o ambas formas) se declaran `false`, el helper cae a
**cliente** / **mail** para no dejar el modal vacío.

```php
// config/tenants/epizoolab.php — solo cliente, solo mail
'envio_informes' => [
    'destinatario_paciente' => false,
    'forma_whatsapp' => false,
],
```

Helper: `App\Support\Envio\InformeEnvioConfig`. No ramificar por `TENANT_SLUG`
en Blade.

## Actores y permisos

Staff con `PermisosIaCatalog::PROTOCOLOS`. Autogestión no abre este modal.

## Tablas y campos críticos

| Tabla | Campo | Rol |
|-------|--------|-----|
| `clientes` | `email`, `whatsapp` | Destino cliente (email admite varios `;` / `,`) |
| `pacientes` | `email`, `whatsapp` | Destino paciente/propietario |
| `entorno` | `ctaEnvioMail`, `passEnvioMail`, `fromMail`, pie | SMTP y firma |

No hay columnas nuevas: la parametrización es de config, no de BD.

## Flujo principal

1. Icono Enviar → carga contactos del protocolo.
2. El operador completa/edita los datos visibles (blur persiste en la columna
   correspondiente: mail → `email`, WhatsApp → `whatsapp`).
3. Destinatario + forma (si hay más de una opción).
4. Mail: genera PDF TCPDF y envía SMTP. WhatsApp: abre WhatsApp Web con link
   público opaco del informe.

## Fuente de verdad

`config('tenant.envio_informes.*')` vía `InformeEnvioConfig`. La UI y la
validación de `confirmarEnvio()` usan las mismas listas; no se puede enviar a
un destino o canal deshabilitado aunque se manipule el request.

## Archivos clave

| Pieza | Ruta |
|-------|------|
| Config default | `config/tenant.php` → `envio_informes` |
| Override lab | `config/tenants/{slug}.php` |
| Helper | `app/Support/Envio/InformeEnvioConfig.php` |
| Envío mail / WhatsApp | `app/Support/Envio/InformeEnvioServicio.php` |
| Modal + acciones | `PacienteIndex` + `partials/paciente-protocolo-modales.blade.php` |

## Qué no hacer / reglas de negocio

- No usar `if (tenant === 'epizoolab')` en Blade ni en Livewire.
- No persistir un campo oculto en otra columna. Si WhatsApp está off, no tocar
  `whatsapp` al guardar el email (y viceversa).
- No mostrar el combo Destinatario/Forma si hay una sola opción: va preelegida.
- SMTP sigue en `entorno`; este flag solo controla qué opciones aparecen.

## Checklist al modificar

- [ ] ¿El default sigue siendo cliente + paciente y mail + WhatsApp?
- [ ] ¿epizoolab sigue solo cliente + mail?
- [ ] ¿La validación de `confirmarEnvio` usa `InformeEnvioConfig` (no `in:` fijo)?
- [ ] ¿Un lab nuevo solo declara lo que **apaga**?
