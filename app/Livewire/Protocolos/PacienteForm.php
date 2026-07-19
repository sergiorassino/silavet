<?php

namespace App\Livewire\Protocolos;

use App\Models\Cliente;
use App\Models\Especie;
use App\Models\Paciente;
use App\Models\Raza;
use App\Models\Usuario;
use App\Support\CuitInput;
use App\Support\DniInput;
use App\Support\PermisosIaCatalog;
use App\Support\ProtocoloNumero;
use App\Support\Protocolos\PacienteListadoFiltros;
use App\Support\Resultados\ResultadosEstadosCatalog;
use App\Support\SexoCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use RuntimeException;
use Throwable;

class PacienteForm extends Component
{
    public ?int $idPacientes = null;

    public ?int $idClientes = null;

    public ?int $idUsuarios = null;

    public string $fechhoy = '';

    public string $tipoProtocolo = 'L';

    public string $nombreProtocolo = '';

    public string $nombre = '';

    public string $propietario = '';

    public string $dni = '';

    public string $cuit = '';

    public string $email = '';

    public string $whatsapp = '';

    public ?int $idEspecies = null;

    public ?int $idRazas = null;

    public string $sexo = '';

    public string $edad = '';

    public string $observaciones = '';

    /** @var array{vista?: string, filtroEstado?: string, page?: int} */
    public array $listadoFiltros = [];

    public function mount(?int $id = null): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);

        $this->listadoFiltros = PacienteListadoFiltros::desdeRequest();

        $ctx = labCtx();

        if ($id) {
            $paciente = $this->pacienteEnAlcance($id);
            abort_if($paciente->esPagoGlobal(), 404);

            $this->idPacientes = $paciente->idPacientes;
            $this->idClientes = $paciente->idClientes;
            $this->idUsuarios = $paciente->idUsuarios ?: null;
            $this->fechhoy = $paciente->fechhoy?->format('Y-m-d') ?? '';
            $this->nombreProtocolo = (string) $paciente->nombreProtocolo;
            $this->nombre = (string) $paciente->nombre;
            $this->propietario = (string) $paciente->propietario;
            $this->dni = self::tieneColumnaDni()
                ? (string) ($paciente->dni ?? '')
                : '';
            $this->cuit = self::tieneColumnaCuit()
                ? CuitInput::format((string) ($paciente->cuit ?? ''))
                : '';
            $this->email = (string) $paciente->email;
            $this->whatsapp = (string) $paciente->whatsapp;
            $this->idEspecies = $paciente->idEspecies ?: null;
            $this->idRazas = $paciente->idRazas ?: null;
            $this->sexo = (string) $paciente->sexo;
            $this->edad = (string) $paciente->edad;
            $this->observaciones = (string) ($paciente->observaciones ?? '');
        } else {
            $this->fechhoy = now()->format('Y-m-d');
            $this->tipoProtocolo = (string) config('tenant.protocolos.dual_corto_largo.tipo_default', 'L');
            $this->actualizarPreviewProtocolo();

            if ($ctx->esCliente() && $ctx->idClientes) {
                $this->idClientes = $ctx->idClientes;
            }
        }
    }

    public function updatedFechhoy(string $value): void
    {
        if ($this->idPacientes || $value === '') {
            return;
        }

        $this->actualizarPreviewProtocolo();
    }

    public function updatedTipoProtocolo(): void
    {
        if ($this->idPacientes) {
            return;
        }

        $this->actualizarPreviewProtocolo();
    }

    public function updatedIdClientes(): void
    {
        $this->idUsuarios = null;
    }

    public function updatedIdEspecies(): void
    {
        $this->idRazas = null;
    }

    public function updatedDni(string $value): void
    {
        $this->resetErrorBag('dni');
        $this->dni = DniInput::normalize($value, 8);
    }

    public function updatedCuit(string $value): void
    {
        $this->resetErrorBag('cuit');
        $this->cuit = CuitInput::format($value);
    }

    public function rules(): array
    {
        return [
            'idClientes' => ['required', 'integer', 'exists:clientes,idClientes'],
            'idUsuarios' => ['nullable', 'integer'],
            'fechhoy' => ['required', 'date'],
            'tipoProtocolo' => [
                Rule::requiredIf(! $this->idPacientes && ProtocoloNumero::usaTipoProtocolo()),
                'string',
                Rule::in(['L', 'C']),
            ],
            'nombreProtocolo' => [$this->idPacientes ? 'required' : 'nullable', 'string', 'max:50'],
            'nombre' => ['required', 'string', 'max:50'],
            'propietario' => ['nullable', 'string', 'max:100'],
            'dni' => ['nullable', 'string', 'max:8'],
            'cuit' => [
                'nullable',
                'string',
                'max:'.CuitInput::FORMATTED_LENGTH,
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $digits = CuitInput::normalize((string) $value);
                    if ($digits !== '' && strlen($digits) !== CuitInput::DIGITS_LENGTH) {
                        $fail('El CUIT debe tener 11 dígitos (formato 99-99999999-9).');
                    }
                },
            ],
            'email' => ['nullable', 'email', 'max:150'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'idEspecies' => ['required', 'integer', 'exists:especies,idEspecies'],
            'idRazas' => ['nullable', 'integer', 'exists:razas,idRazas'],
            'sexo' => ['nullable', 'string', 'max:100', Rule::in(SexoCatalog::opciones()->all())],
            'edad' => ['nullable', 'string', 'max:50'],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    public function save(): void
    {
        $key = 'paciente-save:'.auth()->id();
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $data = $this->validate();
        $ctx = labCtx();

        if ($ctx->esCliente() && $ctx->idClientes) {
            abort_unless((int) $data['idClientes'] === $ctx->idClientes, 403);
        }

        $idUsuarios = (int) ($data['idUsuarios'] ?? 0);
        if ($idUsuarios > 0) {
            $medicoValido = Usuario::query()
                ->where('idUsuarios', $idUsuarios)
                ->where('idClientes', $data['idClientes'])
                ->exists();

            if (! $medicoValido) {
                throw ValidationException::withMessages([
                    'idUsuarios' => 'El médico solicitante no pertenece al cliente seleccionado.',
                ]);
            }
        }

        if (! empty($data['idRazas'])) {
            $razaValida = Raza::query()
                ->where('idRazas', $data['idRazas'])
                ->where('idEspecies', $data['idEspecies'])
                ->exists();

            if (! $razaValida) {
                throw ValidationException::withMessages([
                    'idRazas' => 'La raza no corresponde a la especie seleccionada.',
                ]);
            }
        }

        $dni = trim((string) ($data['dni'] ?? ''));
        $cuit = CuitInput::normalize(trim((string) ($data['cuit'] ?? '')));

        if ($dni !== '' && ! self::tieneColumnaDni()) {
            $mensaje = 'No se puede guardar el DNI: falta la columna pacientes.dni en este laboratorio. '
                .'Ejecute la migración (php artisan lb:migrate-legacy --force) o el SQL de database/sql/dni_cuit_pacientes_clientes.sql.';
            $this->dispatch('vl-swal-error', mensaje: $mensaje);
            throw ValidationException::withMessages(['dni' => $mensaje]);
        }

        if ($cuit !== '' && ! self::tieneColumnaCuit()) {
            $mensaje = 'No se puede guardar el CUIT: falta la columna pacientes.cuit en este laboratorio. '
                .'Ejecute la migración (php artisan lb:migrate-legacy --force) o el SQL de database/sql/dni_cuit_pacientes_clientes.sql.';
            $this->dispatch('vl-swal-error', mensaje: $mensaje);
            throw ValidationException::withMessages(['cuit' => $mensaje]);
        }

        $payload = [
            'idClientes' => (int) $data['idClientes'],
            'idUsuarios' => $idUsuarios,
            'idEspecies' => (int) $data['idEspecies'],
            'idRazas' => (int) ($data['idRazas'] ?? 0),
            'fechhoy' => $data['fechhoy'],
            'nombre' => trim($data['nombre']),
            'propietario' => trim((string) ($data['propietario'] ?? '')),
            'email' => trim((string) ($data['email'] ?? '')),
            'whatsapp' => trim((string) ($data['whatsapp'] ?? '')),
            'sexo' => trim((string) ($data['sexo'] ?? '')),
            'edad' => trim((string) ($data['edad'] ?? '')),
            'observaciones' => trim((string) ($data['observaciones'] ?? '')),
        ];

        if (self::tieneColumnaDni()) {
            $payload['dni'] = $dni;
        }
        if (self::tieneColumnaCuit()) {
            $payload['cuit'] = $cuit;
        }

        try {
            if ($this->idPacientes) {
                $payload['nombreProtocolo'] = trim($data['nombreProtocolo']);
                $paciente = $this->pacienteEnAlcance($this->idPacientes);
                $paciente->update($payload);
                $mensaje = 'Protocolo actualizado correctamente.';
                $focoId = $this->idPacientes;
                $filtrosVolver = $this->listadoFiltros;
            } else {
                try {
                    $tipo = ProtocoloNumero::usaTipoProtocolo()
                        ? ($data['tipoProtocolo'] ?? 'L')
                        : null;

                    $nuevoId = null;
                    ProtocoloNumero::withSiguienteReservado($payload['fechhoy'], function (string $numero) use ($payload, &$nuevoId): void {
                        $creado = Paciente::create(array_merge($payload, [
                            'nombreProtocolo' => $numero,
                            'tipoRegistro' => 1,
                            'estado' => ResultadosEstadosCatalog::EN_PROC,
                        ]));
                        $nuevoId = (int) $creado->idPacientes;
                    }, $tipo);
                    $this->idPacientes = $nuevoId;
                } catch (RuntimeException $e) {
                    throw ValidationException::withMessages([
                        'nombreProtocolo' => $e->getMessage(),
                    ]);
                }

                $mensaje = 'Protocolo registrado correctamente.';
                $focoId = $this->idPacientes;
                // Alta nueva: suele estar al inicio del listado (página 1).
                $filtrosVolver = $this->listadoFiltros;
                unset($filtrosVolver['page']);
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (QueryException $e) {
            report($e);
            $mensaje = 'No se pudo guardar el protocolo en la base de datos.';
            $this->dispatch('vl-swal-error', mensaje: $mensaje);
            throw ValidationException::withMessages([
                'nombre' => $mensaje,
            ]);
        } catch (Throwable $e) {
            report($e);
            $mensaje = 'No se pudo guardar el protocolo: '.$e->getMessage();
            $this->dispatch('vl-swal-error', mensaje: $mensaje);
            throw ValidationException::withMessages([
                'nombre' => $mensaje,
            ]);
        }

        RateLimiter::hit($key, 60);
        $this->dispatch('vl-swal-exito', mensaje: $mensaje);

        $this->redirect(
            PacienteListadoFiltros::urlIndex($filtrosVolver, $focoId),
            navigate: false
        );
    }

    protected static function tieneColumnaDni(): bool
    {
        return Schema::hasColumn('pacientes', 'dni');
    }

    protected static function tieneColumnaCuit(): bool
    {
        return Schema::hasColumn('pacientes', 'cuit');
    }

    protected function actualizarPreviewProtocolo(): void
    {
        if ($this->fechhoy === '') {
            return;
        }

        $tipo = ProtocoloNumero::usaTipoProtocolo() ? $this->tipoProtocolo : null;
        $this->nombreProtocolo = ProtocoloNumero::previsualizarParaFecha($this->fechhoy, $tipo);
    }

    protected function pacienteEnAlcance(int $id): Paciente
    {
        $ctx = labCtx();

        return Paciente::query()
            ->when($ctx->esCliente() && $ctx->idClientes, fn ($q) => $q->where('idClientes', $ctx->idClientes))
            ->where('idPacientes', $id)
            ->firstOrFail();
    }

    public function render()
    {
        $ctx = labCtx();
        $titulo = $this->idPacientes ? 'Editar protocolo' : 'Nuevo paciente';

        $clientes = Cliente::query()
            ->when($ctx->esCliente() && $ctx->idClientes, fn ($q) => $q->where('idClientes', $ctx->idClientes))
            ->orderBy('nombre')
            ->get(['idClientes', 'nombre']);

        $medicos = collect();
        if ($this->idClientes) {
            $medicos = Usuario::query()
                ->where('idClientes', $this->idClientes)
                ->orderBy('apenom')
                ->get(['idUsuarios', 'apenom']);
        }

        $especies = Especie::query()->orderBy('nombre')->get(['idEspecies', 'nombre']);

        $razas = collect();
        if ($this->idEspecies) {
            $razas = Raza::query()
                ->where('idEspecies', $this->idEspecies)
                ->orderBy('nombre')
                ->get(['idRazas', 'nombre']);
        }

        $sexos = SexoCatalog::opciones();
        $clienteBloqueado = $ctx->esCliente() && $ctx->idClientes;
        $usaTipoProtocolo = ProtocoloNumero::usaTipoProtocolo();

        return view('livewire.protocolos.paciente-form', [
            'titulo' => $titulo,
            'clientes' => $clientes,
            'medicos' => $medicos,
            'especies' => $especies,
            'razas' => $razas,
            'sexos' => $sexos,
            'clienteBloqueado' => $clienteBloqueado,
            'usaTipoProtocolo' => $usaTipoProtocolo,
            'urlVolver' => PacienteListadoFiltros::urlIndex($this->listadoFiltros, $this->idPacientes),
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
