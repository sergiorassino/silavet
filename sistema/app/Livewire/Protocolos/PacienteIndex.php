<?php

namespace App\Livewire\Protocolos;

use App\Models\Paciente;
use App\Models\Renglon;
use App\Support\Envio\InformeEnvioServicio;
use App\Support\PermisosIaCatalog;
use App\Support\Resultados\InformeVisibilidadConsulta;
use App\Support\Resultados\RenglonesMaterializer;
use App\Support\Resultados\ResultadosEstadosCatalog;
use App\Support\UsuarioMenuPortal;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

class PacienteIndex extends Component
{
    use WithPagination;

    public const POR_PAGINA = 50;

    public const VISTA_HOY = 'hoy';

    public const VISTA_HISTORIAL = 'historial';

    public string $busqueda = '';

    public string $vista = self::VISTA_HOY;

    public bool $modalEnvioAbierto = false;

    public ?int $envioIdPaciente = null;

    public string $envioProtocolo = '';

    public string $envioNombrePaciente = '';

    public string $envioClienteNombre = '';

    public string $envioClienteEmail = '';

    public string $envioClienteWhatsapp = '';

    public string $envioPacienteEmail = '';

    public string $envioPacienteWhatsapp = '';

    public string $envioDestinatario = '';

    public string $envioForma = '';

    public bool $modalEdInfAbierto = false;

    public ?int $edInfIdPaciente = null;

    public string $edInfProtocolo = '';

    public string $edInfNombrePaciente = '';

    public bool $modalObsAbierto = false;

    public ?int $obsIdPaciente = null;

    public string $obsProtocolo = '';

    public string $obsNombrePaciente = '';

    public string $obsTexto = '';

    public function mount(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatingVista(): void
    {
        $this->resetPage();
    }

    public function avanzarEstado(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);

        $uid = labCtx()->idUsuarios ?? 0;
        $key = 'protocolos-estado:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($key, 60), 429);

        $paciente = $this->pacienteEnAlcance($id);
        if ($paciente === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontró el protocolo.');

            return;
        }

        RateLimiter::hit($key, 60);
        $paciente->update([
            'estado' => ResultadosEstadosCatalog::siguiente($paciente->estado),
        ]);
    }

    public function abrirModalEnvio(int $id): void
    {
        $paciente = $this->pacienteEnAlcance($id);
        if ($paciente === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontró el protocolo.');

            return;
        }

        $contactos = InformeEnvioServicio::contactos($paciente);

        $this->envioIdPaciente = (int) $paciente->idPacientes;
        $this->envioProtocolo = $contactos['protocolo'] !== '' ? $contactos['protocolo'] : '—';
        $this->envioNombrePaciente = $contactos['nombre'] !== '' ? $contactos['nombre'] : '—';
        $this->envioClienteNombre = $contactos['cliente_nombre'] !== '' ? $contactos['cliente_nombre'] : '—';
        $this->envioClienteEmail = $contactos['cliente_email'];
        $this->envioClienteWhatsapp = $contactos['cliente_whatsapp'];
        $this->envioPacienteEmail = $contactos['paciente_email'];
        $this->envioPacienteWhatsapp = $contactos['paciente_whatsapp'];

        $this->envioDestinatario = '';
        $this->envioForma = '';
        $this->modalEnvioAbierto = true;
        $this->resetErrorBag();
    }

    public function cerrarModalEnvio(): void
    {
        $this->modalEnvioAbierto = false;
        $this->envioIdPaciente = null;
        $this->resetErrorBag();
    }

    public function updatedEnvioClienteEmail(): void
    {
        $this->guardarContactoCliente();
    }

    public function updatedEnvioClienteWhatsapp(): void
    {
        $this->guardarContactoCliente();
    }

    public function updatedEnvioPacienteEmail(): void
    {
        $this->guardarContactoPaciente();
    }

    public function updatedEnvioPacienteWhatsapp(): void
    {
        $this->guardarContactoPaciente();
    }

    public function confirmarEnvio(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);

        $uid = labCtx()->idUsuarios ?? 0;
        $key = 'protocolos-enviar:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($key, 20), 429);

        $this->validate([
            'envioClienteEmail' => ['nullable', 'email', 'max:150'],
            'envioClienteWhatsapp' => ['nullable', 'string', 'max:20'],
            'envioPacienteEmail' => ['nullable', 'email', 'max:150'],
            'envioPacienteWhatsapp' => ['nullable', 'string', 'max:20'],
            'envioDestinatario' => ['required', 'in:cliente,paciente'],
            'envioForma' => ['required', 'in:mail,whatsapp'],
        ], [
            'envioClienteEmail.email' => 'El email del cliente no es válido.',
            'envioClienteEmail.max' => 'El email del cliente no puede superar 150 caracteres.',
            'envioClienteWhatsapp.max' => 'El WhatsApp del cliente no puede superar 20 caracteres.',
            'envioPacienteEmail.email' => 'El email del paciente no es válido.',
            'envioPacienteEmail.max' => 'El email del paciente no puede superar 150 caracteres.',
            'envioPacienteWhatsapp.max' => 'El WhatsApp del paciente no puede superar 20 caracteres.',
            'envioDestinatario.required' => 'Seleccione el destinatario.',
            'envioDestinatario.in' => 'Seleccione el destinatario.',
            'envioForma.required' => 'Seleccione la forma de envío.',
            'envioForma.in' => 'Seleccione la forma de envío.',
        ]);

        if ($this->envioIdPaciente === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No hay protocolo seleccionado.');

            return;
        }

        $paciente = $this->pacienteEnAlcance($this->envioIdPaciente);
        if ($paciente === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontró el protocolo.');
            $this->cerrarModalEnvio();

            return;
        }

        RateLimiter::hit($key, 60);

        // Usar valores del form (pueden no haber disparado blur aún).
        $paciente->email = trim($this->envioPacienteEmail);
        $paciente->whatsapp = trim($this->envioPacienteWhatsapp);
        if ($paciente->cliente !== null) {
            $paciente->cliente->email = trim($this->envioClienteEmail);
            $paciente->cliente->whatsapp = trim($this->envioClienteWhatsapp);
        }

        if ($this->envioForma === InformeEnvioServicio::FORMA_MAIL) {
            $resultado = InformeEnvioServicio::enviarMail($paciente, $this->envioDestinatario);
            if (! $resultado['ok']) {
                $this->dispatch('vl-swal-error', mensaje: $resultado['error']);

                return;
            }

            $this->cerrarModalEnvio();
            $this->dispatch('vl-swal-exito', mensaje: 'Mail enviado correctamente.');

            return;
        }

        $resultado = InformeEnvioServicio::urlWhatsappWeb($paciente, $this->envioDestinatario);
        if (! $resultado['ok']) {
            $this->dispatch('vl-swal-error', mensaje: $resultado['error']);

            return;
        }

        $this->cerrarModalEnvio();
        $this->dispatch('vl-abrir-url', url: $resultado['url']);
        $this->dispatch('vl-swal-exito', mensaje: 'Se abrió WhatsApp Web para completar el envío.');
    }

    public function abrirModalEdInf(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);

        if (! Schema::hasTable('renglones')) {
            $this->dispatch('vl-swal-error', mensaje: 'La tabla de renglones no está disponible.');

            return;
        }

        $paciente = $this->pacienteEnAlcance($id);
        if ($paciente === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontró el protocolo.');

            return;
        }

        (new RenglonesMaterializer)->asegurarParaPaciente($paciente);

        $this->edInfIdPaciente = (int) $paciente->idPacientes;
        $this->edInfProtocolo = $paciente->nombreProtocolo !== null && $paciente->nombreProtocolo !== ''
            ? (string) $paciente->nombreProtocolo
            : '—';
        $this->edInfNombrePaciente = $paciente->nombre !== null && $paciente->nombre !== ''
            ? (string) $paciente->nombre
            : '—';
        $this->modalEdInfAbierto = true;
    }

    public function cerrarModalEdInf(): void
    {
        $this->modalEdInfAbierto = false;
        $this->edInfIdPaciente = null;
        $this->edInfProtocolo = '';
        $this->edInfNombrePaciente = '';
    }

    public function abrirModalObs(int $id): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);

        $paciente = $this->pacienteEnAlcance($id);
        if ($paciente === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontró el protocolo.');

            return;
        }

        $this->obsIdPaciente = (int) $paciente->idPacientes;
        $this->obsProtocolo = $paciente->nombreProtocolo !== null && $paciente->nombreProtocolo !== ''
            ? (string) $paciente->nombreProtocolo
            : '—';
        $this->obsNombrePaciente = $paciente->nombre !== null && $paciente->nombre !== ''
            ? (string) $paciente->nombre
            : '—';
        $this->obsTexto = (string) ($paciente->observaciones ?? '');
        $this->modalObsAbierto = true;
        $this->resetErrorBag();
    }

    public function cerrarModalObs(): void
    {
        $this->modalObsAbierto = false;
        $this->obsIdPaciente = null;
        $this->obsProtocolo = '';
        $this->obsNombrePaciente = '';
        $this->obsTexto = '';
        $this->resetErrorBag();
    }

    public function guardarObservaciones(): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);

        $uid = labCtx()->idUsuarios ?? 0;
        $key = 'protocolos-obs:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($key, 30), 429);

        $this->validate([
            'obsTexto' => ['nullable', 'string'],
        ]);

        if ($this->obsIdPaciente === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No hay protocolo seleccionado.');

            return;
        }

        $paciente = $this->pacienteEnAlcance($this->obsIdPaciente);
        if ($paciente === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontró el protocolo.');
            $this->cerrarModalObs();

            return;
        }

        RateLimiter::hit($key, 60);
        $paciente->update([
            'observaciones' => trim($this->obsTexto),
        ]);

        $this->cerrarModalObs();
    }

    public function setMostrarRenglon(int $idRenglones, mixed $mostrar): void
    {
        abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS), 403);

        $uid = labCtx()->idUsuarios ?? 0;
        $key = 'protocolos-edinf:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($key, 60), 429);

        if ($this->edInfIdPaciente === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No hay protocolo seleccionado.');

            return;
        }

        $mostrar = (int) $mostrar;
        if (! in_array($mostrar, [0, 1], true)) {
            $this->dispatch('vl-swal-error', mensaje: 'Valor de visibilidad inválido.');

            return;
        }

        $paciente = $this->pacienteEnAlcance($this->edInfIdPaciente);
        if ($paciente === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontró el protocolo.');
            $this->cerrarModalEdInf();

            return;
        }

        $renglon = Renglon::query()
            ->where('idPacientes', $paciente->idPacientes)
            ->where('idRenglones', $idRenglones)
            ->first();

        if ($renglon === null) {
            $this->dispatch('vl-swal-error', mensaje: 'No se encontró el renglón del informe.');

            return;
        }

        RateLimiter::hit($key, 60);
        $renglon->update(['mostrar' => $mostrar]);
    }

    public function render()
    {
        $term = trim($this->busqueda);
        $ctx = labCtx();

        $pacientes = Paciente::query()
            ->with(['cliente', 'especie', 'raza'])
            ->when($ctx->esCliente() && $ctx->idClientes, function ($q) use ($ctx) {
                $q->where('pacientes.idClientes', $ctx->idClientes);
            })
            ->when($this->vista === self::VISTA_HOY, function ($q) {
                $q->whereDate('pacientes.fechhoy', now()->toDateString());
            })
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('pacientes.nombreProtocolo', 'like', "%{$term}%")
                        ->orWhere('pacientes.nombre', 'like', "%{$term}%")
                        ->orWhere('pacientes.propietario', 'like', "%{$term}%")
                        ->orWhere('pacientes.email', 'like', "%{$term}%")
                        ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%{$term}%"));
                });
            })
            ->orderByDesc('pacientes.fechhoy')
            ->orderByDesc('pacientes.nombreProtocolo')
            ->paginate(self::POR_PAGINA);

        $edInfRenglones = [];
        if ($this->modalEdInfAbierto && $this->edInfIdPaciente !== null) {
            $pacienteEdInf = $this->pacienteEnAlcance($this->edInfIdPaciente);
            if ($pacienteEdInf !== null) {
                $edInfRenglones = (new InformeVisibilidadConsulta)->listar($pacienteEdInf);
            }
        }

        return view('livewire.protocolos.paciente-index', [
            'pacientes' => $pacientes,
            'edInfRenglones' => $edInfRenglones,
        ])->layout('layouts.staff', UsuarioMenuPortal::staffLayoutParams(labCtx()->idRoles));
    }

    private function pacienteEnAlcance(int $id): ?Paciente
    {
        $ctx = labCtx();

        return Paciente::query()
            ->with('cliente')
            ->when($ctx->esCliente() && $ctx->idClientes, function ($q) use ($ctx) {
                $q->where('pacientes.idClientes', $ctx->idClientes);
            })
            ->where('pacientes.idPacientes', $id)
            ->first();
    }

    private function guardarContactoCliente(): void
    {
        if ($this->envioIdPaciente === null) {
            return;
        }

        $this->envioClienteEmail = trim($this->envioClienteEmail);
        $this->envioClienteWhatsapp = trim($this->envioClienteWhatsapp);

        $this->validateOnly('envioClienteEmail', [
            'envioClienteEmail' => ['nullable', 'email', 'max:150'],
        ], [
            'envioClienteEmail.email' => 'El email del cliente no es válido.',
            'envioClienteEmail.max' => 'El email del cliente no puede superar 150 caracteres.',
        ]);
        $this->validateOnly('envioClienteWhatsapp', [
            'envioClienteWhatsapp' => ['nullable', 'string', 'max:20'],
        ], [
            'envioClienteWhatsapp.max' => 'El WhatsApp del cliente no puede superar 20 caracteres.',
        ]);

        $uid = labCtx()->idUsuarios ?? 0;
        $key = 'protocolos-envio-contacto:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($key, 40), 429);
        RateLimiter::hit($key, 60);

        $paciente = $this->pacienteEnAlcance($this->envioIdPaciente);
        $cliente = $paciente?->cliente;
        if ($cliente === null) {
            return;
        }

        $cliente->update([
            'email' => $this->envioClienteEmail,
            'whatsapp' => $this->envioClienteWhatsapp,
        ]);
    }

    private function guardarContactoPaciente(): void
    {
        if ($this->envioIdPaciente === null) {
            return;
        }

        $this->envioPacienteEmail = trim($this->envioPacienteEmail);
        $this->envioPacienteWhatsapp = trim($this->envioPacienteWhatsapp);

        $this->validateOnly('envioPacienteEmail', [
            'envioPacienteEmail' => ['nullable', 'email', 'max:150'],
        ], [
            'envioPacienteEmail.email' => 'El email del paciente no es válido.',
            'envioPacienteEmail.max' => 'El email del paciente no puede superar 150 caracteres.',
        ]);
        $this->validateOnly('envioPacienteWhatsapp', [
            'envioPacienteWhatsapp' => ['nullable', 'string', 'max:20'],
        ], [
            'envioPacienteWhatsapp.max' => 'El WhatsApp del paciente no puede superar 20 caracteres.',
        ]);

        $uid = labCtx()->idUsuarios ?? 0;
        $key = 'protocolos-envio-contacto:'.$uid;
        abort_if(RateLimiter::tooManyAttempts($key, 40), 429);
        RateLimiter::hit($key, 60);

        $paciente = $this->pacienteEnAlcance($this->envioIdPaciente);
        if ($paciente === null) {
            return;
        }

        $paciente->update([
            'email' => $this->envioPacienteEmail,
            'whatsapp' => $this->envioPacienteWhatsapp,
        ]);
    }
}
