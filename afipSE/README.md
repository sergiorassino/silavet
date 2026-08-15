# AFIP — SILAVET

Integración WSAA / WSFEv1 (mismo enfoque que Sistemas Escolares).

## Certificados por usuario

Cada usuario con `permisoAfip` usa su carpeta:

```
afipSE/cert/{idUsuarios}/
  archivo.key   ← nombre en usuarios.key
  archivo.crt   ← nombre en usuarios.crt
  TA.xml        ← generado en runtime
  TRA.xml
```

Ejemplo: usuario `idUsuarios = 3` → `afipSE/cert/3/mi-certificado.crt`.

Los archivos se cargan desde **Gestión de Usuarios** (ABM): campos de upload de clave (`.key` / `.pem`) y certificado (`.crt` / `.cer` / `.pem`). Al guardar se copian a `afipSE/cert/{idUsuarios}/` y se persiste solo el nombre en `usuarios.key` / `usuarios.crt`. Un upload nuevo reemplaza el archivo anterior; **Borrar** elimina el archivo de esa carpeta y deja `key`/`crt` en `0`. En ambos casos se invalidan `TA.xml` / `TRA.xml` para forzar un ticket WSAA nuevo.

## WSDL

Los WSDL de producción/homologación están en `afipSE/wsdl/`.

No versionar certificados ni tickets en git (ver `.gitignore`).
