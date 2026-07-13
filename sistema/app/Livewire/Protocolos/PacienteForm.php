<?php

namespace App\Livewire\Protocolos;

use App\Models\Cliente;
use App\Models\Especie;
use App\Models\Paciente;
use App\Models\Raza;
use App\Models\Usuario;
use App\Support\DniInput;
use App\Support\PermisosIaCatalog;
use App\Support\ProtocoloNumero;
use App\Support\Resultados\ResultadosEstadosCatalog;
use App\Support\SexoCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use RuntimeException;

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

    public string $email = '';

    public string $whatsapp = '';

    public ?int $idEspecies = null;

    public ?int $idRazas = null;

    public string $sexo = '';

    public string $edad = '';

    public string $observaciones = '';

    public function mount(?int $id = null): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);

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
            $this->dni = (string) ($paciente->fechnaci ?? '');
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
        $this->dni = DniInput::normalize($value);
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
            'dni' => ['nullable', 'string', 'max:'.DniInput::MAX_LENGTH],
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

        $payload = [
            'idClientes' => (int) $data['idClientes'],
            'idUsuarios' => $idUsuarios,
            'idEspecies' => (int) $data['idEspecies'],
            'idRazas' => (int) ($data['idRazas'] ?? 0),
            'fechhoy' => $data['fechhoy'],
            'nombre' => trim($data['nombre']),
            'propietario' => trim((string) ($data['propietario'] ?? '')),
            'fechnaci' => trim((string) ($data['dni'] ?? '')),
            'email' => trim((string) ($data['email'] ?? '')),
            'whatsapp' => trim((string) ($data['whatsapp'] ?? '')),
            'sexo' => trim((string) ($data['sexo'] ?? '')),
            'edad' => trim((string) ($data['edad'] ?? '')),
            'observaciones' => trim((string) ($data['observaciones'] ?? '')),
        ];

        if ($this->idPacientes) {
            $payload['nombreProtocolo'] = trim($data['nombreProtocolo']);
            $paciente = $this->pacienteEnAlcance($this->idPacientes);
            $paciente->update($payload);
            $mensaje = 'Protocolo actualizado correctamente.';
        } else {
            try {
                $tipo = ProtocoloNumero::usaTipoProtocolo()
                    ? ($data['tipoProtocolo'] ?? 'L')
                    : null;

                ProtocoloNumero::withSiguienteReservado($payload['fechhoy'], function (string $numero) use ($payload): void {
                    Paciente::create(array_merge($payload, [
                        'nombreProtocolo' => $numero,
                        'tipoRegistro' => 1,
                        'estado' => ResultadosEstadosCatalog::EN_PROC,
                    ]));
                }, $tipo);
            } catch (RuntimeException $e) {
                throw ValidationException::withMessages([
                    'nombreProtocolo' => $e->getMessage(),
                ]);
            }

            $mensaje = 'Protocolo registrado correctamente.';
        }

        RateLimiter::hit($key, 60);
        $this->dispatch('vl-swal-exito', mensaje: $mensaje);

        $this->redirectRoute('protocolos.index', navigate: false);
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

        return view('livewire.protocolos.paciente-form', compact(
            'titulo',
            'clientes',
            'medicos',
            'especies',
            'razas',
            'sexos',
            'clienteBloqueado',
            'usaTipoProtocolo',
        ))->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }
}
