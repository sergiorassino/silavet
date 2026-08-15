<?php

namespace App\Livewire\Abm\Usuarios;

use App\Models\Cliente;
use App\Models\Rol;
use App\Models\Usuario;
use App\Support\Afip\AfipCertificadosStorage;
use App\Support\CuitInput;
use App\Support\PermisosIaCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class UsuarioForm extends Component
{
    use WithFileUploads;

    public ?int $idUsuarios = null;

    public string $apenom = '';

    public string $dni = '';

    public string $password = '';

    public string $idRoles = '';

    public string $idClientes = '';

    public bool $permisoAfip = false;

    public string $cuit = '';

    public string $razonSocial = '';

    public string $domicComerc = '';

    public string $condIva = '';

    public string $ingresosBrutos = '';

    public string $inicioActiv = '';

    public string $PtoVta = '';

    public string $CbteTipo = '';

    public string $NtaCredTipo = '';

    public string $Concepto = '';

    public string $DocTipo = '';

    public string $CondicionIVAReceptorId = '';

    public string $keyActual = '';

    public string $crtActual = '';

    /** Fecha persistida (Y-m-d) de usuarios.crtVencimiento. */
    public string $crtVencimiento = '';

    /** Fecha leída del archivo recién seleccionado, aún no guardada. */
    public string $crtVencimientoPreview = '';

    /** @var UploadedFile|null */
    public $keyUpload = null;

    /** @var UploadedFile|null */
    public $crtUpload = null;

    public function mount(?int $id = null): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::USUARIOS), 403);

        if ($id) {
            $usuario = Usuario::query()->findOrFail($id);
            $this->idUsuarios = $usuario->idUsuarios;
            $this->apenom = (string) $usuario->apenom;
            $this->dni = (string) $usuario->dni;
            $this->password = (string) $usuario->password;
            $this->idRoles = $usuario->idRoles !== null ? (string) $usuario->idRoles : '';
            $this->idClientes = $usuario->idClientes !== null && (int) $usuario->idClientes > 0
                ? (string) $usuario->idClientes
                : '';
            $this->permisoAfip = (int) $usuario->permisoAfip === 1;
            $this->cargarCamposAfip($usuario);
        }
    }

    public function updatedCuit(string $value): void
    {
        $this->resetErrorBag('cuit');
        $this->cuit = CuitInput::format($value);
    }

    public function rules(): array
    {
        $afip = $this->permisoAfip;

        return [
            'apenom' => ['required', 'string', 'max:150'],
            'dni' => [
                'required',
                'string',
                'max:10',
                Rule::unique('usuarios', 'dni')->ignore($this->idUsuarios, 'idUsuarios'),
            ],
            'password' => ['required', 'string', 'max:10'],
            'idRoles' => ['required', 'integer', Rule::exists('roles', 'id')],
            'idClientes' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $v = trim((string) $value);
                    if ($v === '') {
                        return;
                    }
                    if (! ctype_digit($v) || ! Cliente::query()->whereKey((int) $v)->exists()) {
                        $fail('El cliente seleccionado no es válido.');
                    }
                },
            ],
            'permisoAfip' => ['boolean'],
            'cuit' => [
                $afip ? 'required' : 'nullable',
                'string',
                'max:'.CuitInput::FORMATTED_LENGTH,
                function (string $attribute, mixed $value, \Closure $fail) use ($afip): void {
                    $digits = CuitInput::normalize((string) $value);
                    if ($digits === '') {
                        if ($afip) {
                            $fail('El CUIT es obligatorio cuando el permiso AFIP está habilitado.');
                        }

                        return;
                    }
                    if (strlen($digits) !== CuitInput::DIGITS_LENGTH) {
                        $fail('El CUIT debe tener 11 dígitos (formato 99-99999999-9).');
                    }
                },
            ],
            'razonSocial' => [$afip ? 'required' : 'nullable', 'string', 'max:100'],
            'domicComerc' => ['nullable', 'string', 'max:50'],
            'condIva' => ['nullable', 'string', 'max:30'],
            'ingresosBrutos' => ['nullable', 'string', 'max:30'],
            'inicioActiv' => ['nullable', 'date'],
            'PtoVta' => [$afip ? 'required' : 'nullable', 'integer', 'min:0', 'max:99'],
            'CbteTipo' => ['nullable', 'integer', 'min:0', 'max:99'],
            'NtaCredTipo' => ['nullable', 'integer', 'min:0', 'max:99'],
            'Concepto' => ['nullable', 'integer', 'min:0', 'max:99'],
            'DocTipo' => ['nullable', 'integer', 'min:0', 'max:99'],
            'CondicionIVAReceptorId' => ['nullable', 'integer', 'min:0', 'max:99'],
            'keyUpload' => [
                'nullable',
                'file',
                'max:'.AfipCertificadosStorage::MAX_KB,
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $this->validarExtensionCertificado($value, AfipCertificadosStorage::EXT_KEY, $fail, 'La clave privada debe ser un archivo .key o .pem.');
                },
            ],
            'crtUpload' => [
                'nullable',
                'file',
                'max:'.AfipCertificadosStorage::MAX_KB,
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $this->validarExtensionCertificado($value, AfipCertificadosStorage::EXT_CRT, $fail, 'El certificado debe ser un archivo .crt, .cer o .pem.');
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'apenom.required' => 'El nombre y apellido es obligatorio.',
            'apenom.max' => 'El nombre no puede superar 150 caracteres.',
            'dni.required' => 'El DNI / usuario de login es obligatorio.',
            'dni.max' => 'El DNI no puede superar 10 caracteres.',
            'dni.unique' => 'Ya existe un usuario con ese DNI.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.max' => 'La contraseña no puede superar 10 caracteres.',
            'idRoles.required' => 'Debe seleccionar un rol.',
            'idRoles.exists' => 'El rol seleccionado no es válido.',
            'cuit.required' => 'El CUIT es obligatorio cuando el permiso AFIP está habilitado.',
            'razonSocial.required' => 'La razón social es obligatoria cuando el permiso AFIP está habilitado.',
            'PtoVta.required' => 'El punto de venta es obligatorio cuando el permiso AFIP está habilitado.',
            'inicioActiv.date' => 'La fecha de inicio de actividades no es válida.',
            'keyUpload.file' => 'La clave privada no es un archivo válido.',
            'keyUpload.max' => 'La clave privada no puede superar '.AfipCertificadosStorage::MAX_KB.' KB.',
            'crtUpload.file' => 'El certificado no es un archivo válido.',
            'crtUpload.max' => 'El certificado no puede superar '.AfipCertificadosStorage::MAX_KB.' KB.',
        ];
    }

    public function save(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::USUARIOS), 403);

        $key = 'usuario-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $this->normalizarVaciosNumericos();

        $data = $this->validate();

        $idClientesRaw = trim((string) ($data['idClientes'] ?? ''));

        $payload = [
            'apenom' => trim($data['apenom']),
            'dni' => trim($data['dni']),
            'password' => trim($data['password']),
            'idRoles' => (int) $data['idRoles'],
            'idClientes' => $idClientesRaw !== '' ? (int) $idClientesRaw : null,
            'permisoAfip' => $this->permisoAfip ? 1 : 0,
        ];

        $payload = array_merge($payload, $this->payloadAfip($data));

        $this->validarCrtUploadAntesDeGuardar();

        if ($this->idUsuarios) {
            $usuario = Usuario::query()->findOrFail($this->idUsuarios);
            $usuario->update($payload);
            $mensaje = 'Usuario actualizado correctamente.';
        } else {
            $usuario = Usuario::query()->create($payload);
            $this->idUsuarios = (int) $usuario->idUsuarios;
            $mensaje = 'Usuario creado correctamente.';
        }

        $this->persistirCertificados($usuario);

        RateLimiter::hit($key, 60);
        $this->dispatch('vl-swal-exito', mensaje: $mensaje);

        $this->redirectRoute('abm.usuarios.index', navigate: false);
    }

    public function eliminarCertificado(string $tipo): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::USUARIOS), 403);

        if (! in_array($tipo, [AfipCertificadosStorage::TIPO_KEY, AfipCertificadosStorage::TIPO_CRT], true)) {
            return;
        }

        $limiter = 'usuario-cert-del:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($limiter, 10), 429);
        RateLimiter::hit($limiter, 60);

        if (! $this->idUsuarios) {
            $this->quitarSeleccionCertificado($tipo);

            return;
        }

        $usuario = Usuario::query()->findOrFail($this->idUsuarios);
        $campo = $tipo === AfipCertificadosStorage::TIPO_KEY ? 'key' : 'crt';
        $nombre = $this->valorAfipTexto($usuario->{$campo});

        AfipCertificadosStorage::eliminar((int) $this->idUsuarios, $nombre);
        $payloadCert = [$campo => '0'];
        if ($tipo === AfipCertificadosStorage::TIPO_CRT && AfipCertificadosStorage::tieneColumnaVencimiento()) {
            $payloadCert['crtVencimiento'] = null;
        }
        $usuario->update($payloadCert);
        AfipCertificadosStorage::invalidarTickets((int) $this->idUsuarios);

        if ($tipo === AfipCertificadosStorage::TIPO_KEY) {
            $this->keyActual = '';
            $this->keyUpload = null;
            $this->resetErrorBag('keyUpload');
        } else {
            $this->crtActual = '';
            $this->crtVencimiento = '';
            $this->crtVencimientoPreview = '';
            $this->crtUpload = null;
            $this->resetErrorBag('crtUpload');
        }

        $etiqueta = $tipo === AfipCertificadosStorage::TIPO_KEY ? 'clave privada' : 'certificado';
        $this->dispatch('vl-swal-exito', mensaje: 'Se borró la '.$etiqueta.' AFIP de este usuario.');
    }

    public function quitarSeleccionCertificado(string $tipo): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::USUARIOS), 403);

        if ($tipo === AfipCertificadosStorage::TIPO_KEY) {
            $this->keyUpload = null;
            $this->resetErrorBag('keyUpload');

            return;
        }

        if ($tipo === AfipCertificadosStorage::TIPO_CRT) {
            $this->crtUpload = null;
            $this->crtVencimientoPreview = '';
            $this->resetErrorBag('crtUpload');
        }
    }

    public function updatedCrtUpload(): void
    {
        if (! $this->crtUpload instanceof UploadedFile) {
            $this->crtVencimientoPreview = '';

            return;
        }

        $fecha = AfipCertificadosStorage::vencimientoDesdeUpload($this->crtUpload);
        if ($fecha === null) {
            $this->crtUpload = null;
            $this->crtVencimientoPreview = '';
            $this->addError(
                'crtUpload',
                'No se pudo leer la fecha de vencimiento. Verifique que el archivo sea un certificado X.509 válido (.crt, .cer o .pem).'
            );

            return;
        }

        $this->crtVencimientoPreview = $fecha;
        $this->resetErrorBag('crtUpload');
    }

    private function normalizarVaciosNumericos(): void
    {
        foreach ([
            'PtoVta',
            'CbteTipo',
            'NtaCredTipo',
            'Concepto',
            'DocTipo',
            'CondicionIVAReceptorId',
        ] as $campo) {
            if (trim((string) $this->{$campo}) === '') {
                $this->{$campo} = '0';
            }
        }
    }

    public function render()
    {
        $titulo = $this->idUsuarios ? 'Editar usuario' : 'Nuevo usuario';
        $roles = Rol::query()->orderBy('rol')->get(['id', 'rol']);
        $clientes = Cliente::query()->orderBy('nombre')->get(['idClientes', 'nombre']);
        $id = (int) ($this->idUsuarios ?? 0);
        $keyEnDisco = $id > 0 && $this->keyActual !== '' && AfipCertificadosStorage::existe($id, $this->keyActual);
        $crtEnDisco = $id > 0 && $this->crtActual !== '' && AfipCertificadosStorage::existe($id, $this->crtActual);
        $maxKbCert = AfipCertificadosStorage::MAX_KB;
        [$crtVencimientoTexto, $crtVencido, $crtVencimientoDesdeArchivo] = $this->datosVencimientoParaVista($id, $crtEnDisco);

        return view('livewire.abm.usuarios.usuario-form', compact(
            'titulo',
            'roles',
            'clientes',
            'keyEnDisco',
            'crtEnDisco',
            'maxKbCert',
            'crtVencimientoTexto',
            'crtVencido',
            'crtVencimientoDesdeArchivo',
        ))
            ->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function cargarCamposAfip(Usuario $usuario): void
    {
        $this->cuit = $this->valorAfipTexto($usuario->cuit);
        $this->cuit = CuitInput::format($this->cuit);
        $this->razonSocial = $this->valorAfipTexto($usuario->razonSocial);
        $this->domicComerc = $this->valorAfipTexto($usuario->domicComerc);
        $this->condIva = $this->valorAfipTexto($usuario->condIva);
        $this->ingresosBrutos = $this->valorAfipTexto($usuario->ingresosBrutos);
        $this->inicioActiv = $usuario->inicioActiv
            ? substr((string) $usuario->inicioActiv, 0, 10)
            : '';
        $this->PtoVta = (string) ((int) ($usuario->PtoVta ?? 0));
        $this->CbteTipo = (string) ((int) ($usuario->CbteTipo ?? 0));
        $this->NtaCredTipo = (string) ((int) ($usuario->NtaCredTipo ?? 0));
        $this->Concepto = (string) ((int) ($usuario->Concepto ?? 0));
        $this->DocTipo = (string) ((int) ($usuario->DocTipo ?? 0));
        $this->CondicionIVAReceptorId = (string) ((int) ($usuario->CondicionIVAReceptorId ?? 0));
        $this->keyActual = $this->valorAfipTexto($usuario->key);
        $this->crtActual = $this->valorAfipTexto($usuario->crt);
        $this->crtVencimiento = $this->fechaVencimientoDesdeUsuario($usuario);
        $this->crtVencimientoPreview = '';
    }

    /** @param  array<string, mixed>  $data */
    private function payloadAfip(array $data): array
    {
        $cuit = CuitInput::normalize(trim((string) ($data['cuit'] ?? '')));
        $razon = trim((string) ($data['razonSocial'] ?? ''));
        $domic = trim((string) ($data['domicComerc'] ?? ''));
        $cond = trim((string) ($data['condIva'] ?? ''));
        $iibb = trim((string) ($data['ingresosBrutos'] ?? ''));
        $inicio = trim((string) ($data['inicioActiv'] ?? ''));

        $payload = [
            'cuit' => $cuit !== '' ? $cuit : '0',
            'razonSocial' => $razon !== '' ? $razon : '0',
            'domicComerc' => $domic !== '' ? $domic : '0',
            'condIva' => $cond !== '' ? $cond : '0',
            'ingresosBrutos' => $iibb !== '' ? $iibb : '0',
            'inicioActiv' => $inicio !== '' ? $inicio : null,
            'PtoVta' => (int) ($data['PtoVta'] ?? 0),
            'CbteTipo' => (int) ($data['CbteTipo'] ?? 0),
            'NtaCredTipo' => (int) ($data['NtaCredTipo'] ?? 0),
            'Concepto' => (int) ($data['Concepto'] ?? 0),
            'DocTipo' => (int) ($data['DocTipo'] ?? 0),
            'CondicionIVAReceptorId' => (int) ($data['CondicionIVAReceptorId'] ?? 0),
        ];

        if ($this->idUsuarios === null) {
            $payload['key'] = '0';
            $payload['crt'] = '0';
        }

        return $payload;
    }

    private function persistirCertificados(Usuario $usuario): void
    {
        $id = (int) $usuario->idUsuarios;
        $cambios = [];

        if ($this->keyUpload instanceof UploadedFile) {
            $cambios['key'] = AfipCertificadosStorage::guardar(
                $id,
                $this->keyUpload,
                AfipCertificadosStorage::TIPO_KEY,
                'keyUpload',
            );
        }

        if ($this->crtUpload instanceof UploadedFile) {
            $vencimiento = $this->validarCrtUploadAntesDeGuardar();
            $cambios['crt'] = AfipCertificadosStorage::guardar(
                $id,
                $this->crtUpload,
                AfipCertificadosStorage::TIPO_CRT,
                'crtUpload',
            );
            $cambios['crtVencimiento'] = $vencimiento;
        }

        if ($cambios === []) {
            $this->completarCrtVencimientoSiFalta($usuario);

            return;
        }

        $usuario->update($cambios);

        if (isset($cambios['key'])) {
            AfipCertificadosStorage::eliminarObsoleto($id, $this->keyActual, $cambios['key']);
            $this->keyActual = $cambios['key'];
            $this->keyUpload = null;
        }
        if (isset($cambios['crt'])) {
            AfipCertificadosStorage::eliminarObsoleto($id, $this->crtActual, $cambios['crt']);
            $this->crtActual = $cambios['crt'];
            $this->crtVencimiento = (string) ($cambios['crtVencimiento'] ?? '');
            $this->crtVencimientoPreview = '';
            $this->crtUpload = null;
        } else {
            $this->completarCrtVencimientoSiFalta($usuario);
        }

        AfipCertificadosStorage::invalidarTickets($id);
    }

    private function validarCrtUploadAntesDeGuardar(): string
    {
        if (! $this->crtUpload instanceof UploadedFile) {
            return '';
        }

        $this->assertColumnaCrtVencimientoSiHaceFalta(true);

        $fecha = AfipCertificadosStorage::vencimientoDesdeUpload($this->crtUpload);
        if ($fecha === null) {
            $mensaje = 'No se pudo leer la fecha de vencimiento del certificado. '
                .'Verifique que el archivo sea un certificado X.509 válido (.crt, .cer o .pem).';
            $this->dispatch('vl-swal-error', mensaje: $mensaje);
            throw ValidationException::withMessages(['crtUpload' => $mensaje]);
        }

        $this->crtVencimientoPreview = $fecha;

        return $fecha;
    }

    private function completarCrtVencimientoSiFalta(Usuario $usuario): void
    {
        if ($this->crtVencimiento !== '' || $this->crtActual === '') {
            return;
        }

        if (! AfipCertificadosStorage::tieneColumnaVencimiento()) {
            return;
        }

        $id = (int) $usuario->idUsuarios;
        if (! AfipCertificadosStorage::existe($id, $this->crtActual)) {
            return;
        }

        $ruta = AfipCertificadosStorage::rutaAbsoluta($id, $this->crtActual);
        if ($ruta === null) {
            return;
        }

        $fecha = AfipCertificadosStorage::vencimientoDesdeRuta($ruta);
        if ($fecha === null) {
            return;
        }

        $usuario->update(['crtVencimiento' => $fecha]);
        $this->crtVencimiento = $fecha;
    }

    /**
     * Si hay que persistir o borrar la fecha y falta la columna, error visible (no éxito silencioso).
     */
    private function assertColumnaCrtVencimientoSiHaceFalta(bool $obligatorio): void
    {
        if (! $obligatorio || AfipCertificadosStorage::tieneColumnaVencimiento()) {
            return;
        }

        $mensaje = 'No se puede guardar la fecha de vencimiento del certificado: falta la columna usuarios.crtVencimiento en este laboratorio. '
            .'Ejecute la migración (php artisan lb:migrate-legacy --force) o el SQL de database/sql/usuarios_crt_vencimiento.sql.';
        $this->dispatch('vl-swal-error', mensaje: $mensaje);
        throw ValidationException::withMessages(['crtUpload' => $mensaje]);
    }

    /**
     * @return array{0: string, 1: bool, 2: bool}
     */
    private function datosVencimientoParaVista(int $id, bool $crtEnDisco): array
    {
        $fecha = $this->crtVencimientoPreview !== '' ? $this->crtVencimientoPreview : $this->crtVencimiento;
        $desdeArchivo = false;

        if ($fecha === '' && $crtEnDisco) {
            $ruta = AfipCertificadosStorage::rutaAbsoluta($id, $this->crtActual);
            if ($ruta !== null) {
                $leida = AfipCertificadosStorage::vencimientoDesdeRuta($ruta);
                if ($leida !== null) {
                    $fecha = $leida;
                    $desdeArchivo = true;
                }
            }
        }

        if ($fecha === '') {
            return ['', false, false];
        }

        $ts = strtotime($fecha.' 00:00:00');
        $texto = $ts !== false ? date('d/m/Y', $ts) : $fecha;
        $vencido = $fecha < date('Y-m-d');

        return [$texto, $vencido, $desdeArchivo];
    }

    private function fechaVencimientoDesdeUsuario(Usuario $usuario): string
    {
        if (! AfipCertificadosStorage::tieneColumnaVencimiento()) {
            return '';
        }

        $valor = $usuario->crtVencimiento;
        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        $texto = trim((string) ($valor ?? ''));
        if ($texto === '' || $texto === '0000-00-00') {
            return '';
        }

        return substr($texto, 0, 10);
    }

    /** @param  list<string>  $extensiones */
    private function validarExtensionCertificado(mixed $value, array $extensiones, \Closure $fail, string $mensaje): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $ext = strtolower($value->getClientOriginalExtension() ?: '');
        if (! AfipCertificadosStorage::extensionPermitida($ext, $extensiones)) {
            $fail($mensaje);
        }
    }

    private function valorAfipTexto(mixed $value): string
    {
        $texto = trim((string) ($value ?? ''));

        return ($texto === '' || $texto === '0') ? '' : $texto;
    }
}
