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

## WSDL

Los WSDL de producción/homologación están en `afipSE/wsdl/`.

No versionar certificados ni tickets en git (ver `.gitignore`).
