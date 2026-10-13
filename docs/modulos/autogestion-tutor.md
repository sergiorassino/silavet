# Módulo: Autogestión del tutor

Portal para el propietario de la mascota. Ve los estudios ya cerrados de los
protocolos cargados con su DNI.

## Propósito

Una URL propia, distinta del login de personal y de clínicas, para que el tutor
consulte sus mascotas. La grilla copia la de autogestión del cliente y deja
solo los datos del paciente.

## Modalidades / variantes

Ninguna. Igual en todos los tenants. Siempre entran los estados `Final` y
`Final/Env`, aunque el laboratorio use flujo de 3 estados.

## Actores y permisos

| Actor | Acceso |
|-------|--------|
| Tutor (DNI de `pacientes.dni`) | Login `/loginTutor` y listado de sus estudios finalizados |
| Cliente veterinario, staff | Sin esta pantalla. Su sesión no abre el portal del tutor |

No usa `usuarios` ni `tienePermiso`. La sesión guarda `tutor.dni` y el nombre
del propietario. Abrir `/login` o `/loginTutor` cierra cualquier sesión previa.

## Tablas y campos críticos

| Tabla | Rol |
|-------|-----|
| `pacientes.dni` | Único dato de ingreso (8 caracteres). No hay contraseña |
| `pacientes.propietario` | Nombre mostrado del tutor |
| `pacientes.estado` | Solo `Final` y `Final/Env` |
| `pacientes.tipoRegistro` | Solo protocolos (`1`). Sin pagos globales ni egresos |

## Flujo principal

1. El tutor entra a `/loginTutor`.
2. Ingresa el DNI cargado en el protocolo (se ignoran puntos). No hay contraseña.
3. Hay ingreso solo si existe al menos un protocolo de ese DNI en Final o Final/Env.
4. La grilla muestra fecha, protocolo, nombre, tutor, especie, raza, sexo y edad,
   más el informe PDF. Sin columna de estado, precios, saldo, determinaciones ni IA.
5. El PDF usa token opaco y vuelve a comprobar DNI, tipo y estado.

## Fuente de verdad

Filas de `pacientes` con `dni` igual al de la sesión, `tipoRegistro = 1` y
`estado` en `Final` o `Final/Env`.

## Archivos clave

- `app/Livewire/Auth/LoginTutor.php`
- `app/Livewire/Tutor/PacienteIndex.php`
- `resources/views/livewire/tutor/paciente-index.blade.php`
- `app/Support/Tutor/TutorAcceso.php`
- `app/Support/Tutor/TutorContext.php`
- `app/Support/Tutor/TutorPacientesConsulta.php`
- `app/Http/Controllers/Tutor/InformeTutorPdfController.php`
- Rutas: `login.tutor`, `tutor.pacientes`, `tutor.pacientes.informe`, `logout.tutor`

## Qué no hacer / reglas de negocio

- No listar En Proc., Parcial, ni ningún otro estado.
- No mostrar importes, saldo, lupa de determinaciones ni asistente IA.
- No autenticar al tutor contra `usuarios`.
- No poner DNI, `idPacientes` ni número de protocolo en la URL.
- No ampliar el alcance a otros tutores ni a todo el cliente veterinario.
- Si el DNI no tiene estudios en Final o Final/Env, el mensaje lo dice y no abre la grilla.

## Checklist al modificar

- [ ] Alcance por DNI de sesión + Final / Final/Env + tipo protocolo
- [ ] Token de informe atado al hash del DNI y revalidado al descargar
- [ ] Rate limit en login (5 / minuto por IP) y en el PDF
- [ ] Sin IDs en la URL
- [ ] Si cambia el comportamiento, actualizar este archivo
