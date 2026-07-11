<?php

use App\Http\Controllers\Clientes\CuentaCorrienteClientesExcelController;
use App\Http\Controllers\Clientes\CuentaCorrienteClientesPdfController;
use App\Http\Controllers\Clientes\CuentaCorrienteDetalleExcelController;
use App\Http\Controllers\Clientes\CuentaCorrienteDetallePdfController;
use App\Http\Controllers\Protocolos\InformePacientePdfController;
use App\Livewire\Abm\Clientes\ClienteForm;
use App\Livewire\Abm\Clientes\ClienteIndex;
use App\Livewire\Clientes\CuentaCorrienteDetalle;
use App\Livewire\Clientes\CuentaCorrienteIndex;
use App\Livewire\Abm\DetPorGrupo\DetPorGrupoIndex;
use App\Livewire\Abm\Grupos\GrupoForm;
use App\Livewire\Abm\Grupos\GrupoIndex;
use App\Livewire\Abm\Itemsinforme\ItemsinformeIndex;
use App\Livewire\Abm\Tipodeterminaciones\TipodeterminacionIndex;
use App\Livewire\Admin\EntornoForm;
use App\Livewire\Admin\ScriptAutomatizacionForm;
use App\Livewire\AdminDashboard;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\Protocolos\PacienteDeterminaciones;
use App\Livewire\Protocolos\PacienteForm;
use App\Livewire\Protocolos\PacienteIndex;
use App\Livewire\Protocolos\PacienteResultados;
use App\Support\Auth\CerrarSesionAplicacion;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware(['login.limpiar-sesion', 'no-store'])->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', function () {
    CerrarSesionAplicacion::ejecutar();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware(['auth', 'lab.context'])->group(function () {
    Route::get('/dashboard', Dashboard::class)
        ->middleware('menu.portal:laboratorio')
        ->name('dashboard');

    Route::prefix('admin')->middleware('menu.portal:administracion')->group(function () {
        Route::get('/dashboard', AdminDashboard::class)->name('admin.dashboard');

        Route::prefix('determinaciones')->middleware('permiso:2')->group(function () {
            Route::get('/', TipodeterminacionIndex::class)->name('admin.determinaciones.index');
        });

        Route::prefix('grupos')->middleware('permiso:8')->group(function () {
            Route::get('/', GrupoIndex::class)->name('admin.grupos.index');
            Route::get('/nuevo', GrupoForm::class)->name('admin.grupos.create');
            Route::get('/{id}/editar', GrupoForm::class)->name('admin.grupos.edit');
        });

        Route::prefix('det-por-grupo')->middleware('permiso:8')->group(function () {
            Route::get('/', DetPorGrupoIndex::class)->name('admin.det-por-grupo.index');
        });

        Route::prefix('items-informe')->middleware('permiso:8')->group(function () {
            Route::get('/', ItemsinformeIndex::class)->name('admin.items-informe.index');
        });

        Route::prefix('parametros-sistema')->middleware('permiso:8')->group(function () {
            Route::get('/', EntornoForm::class)->name('admin.parametros-sistema.edit');
        });

        Route::prefix('automatizacion')->middleware('permiso:8')->group(function () {
            Route::get('/script', ScriptAutomatizacionForm::class)->name('admin.automatizacion.script');
        });
    });

    Route::prefix('abm/clientes')->middleware(['menu.portal:laboratorio', 'permiso:0'])->group(function () {
        Route::get('/', ClienteIndex::class)->name('abm.clientes.index');
        Route::get('/nuevo', ClienteForm::class)->name('abm.clientes.create');
        Route::get('/{id}/editar', ClienteForm::class)->name('abm.clientes.edit');
    });

    Route::prefix('clientes/cuenta-corriente')->middleware(['menu.portal:laboratorio', 'permiso:6'])->group(function () {
        Route::get('/', CuentaCorrienteIndex::class)->name('clientes.cuenta-corriente.index');
        Route::get('/pdf', CuentaCorrienteClientesPdfController::class)->name('clientes.cuenta-corriente.pdf');
        Route::get('/excel', CuentaCorrienteClientesExcelController::class)->name('clientes.cuenta-corriente.excel');
        Route::get('/{id}', CuentaCorrienteDetalle::class)->name('clientes.cuenta-corriente.detalle');
        Route::get('/{id}/pdf', CuentaCorrienteDetallePdfController::class)->name('clientes.cuenta-corriente.detalle.pdf');
        Route::get('/{id}/excel', CuentaCorrienteDetalleExcelController::class)->name('clientes.cuenta-corriente.detalle.excel');
    });

    Route::prefix('protocolos')->middleware(['menu.portal:staff', 'permiso:3'])->group(function () {
        Route::get('/', PacienteIndex::class)->name('protocolos.index');
        Route::get('/nuevo', PacienteForm::class)->name('protocolos.create');
        Route::get('/{id}/editar', PacienteForm::class)->name('protocolos.edit');
        Route::get('/{id}/determinaciones', PacienteDeterminaciones::class)->name('protocolos.determinaciones');
    });

    Route::prefix('protocolos')->middleware(['menu.portal:staff', 'permiso:4'])->group(function () {
        Route::get('/{id}/resultados', PacienteResultados::class)->name('protocolos.resultados');
    });

    Route::prefix('protocolos')->middleware(['menu.portal:staff', 'permiso:5', 'no-store'])->group(function () {
        Route::get('/informe/{ref}', InformePacientePdfController::class)
            ->where('ref', '[A-Za-z0-9_-]+')
            ->name('protocolos.informe');
    });
});
