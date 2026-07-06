<?php

use App\Livewire\Abm\Clientes\ClienteForm;
use App\Livewire\Abm\Clientes\ClienteIndex;
use App\Livewire\Abm\DetPorGrupo\DetPorGrupoIndex;
use App\Livewire\Abm\Grupos\GrupoForm;
use App\Livewire\Abm\Grupos\GrupoIndex;
use App\Livewire\Abm\Tipodeterminaciones\TipodeterminacionIndex;
use App\Livewire\AdminDashboard;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\Protocolos\PacienteForm;
use App\Livewire\Protocolos\PacienteIndex;
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
    });

    Route::prefix('abm/clientes')->middleware(['menu.portal:laboratorio', 'permiso:0'])->group(function () {
        Route::get('/', ClienteIndex::class)->name('abm.clientes.index');
        Route::get('/nuevo', ClienteForm::class)->name('abm.clientes.create');
        Route::get('/{id}/editar', ClienteForm::class)->name('abm.clientes.edit');
    });

    Route::prefix('protocolos')->middleware(['menu.portal:staff', 'permiso:3'])->group(function () {
        Route::get('/', PacienteIndex::class)->name('protocolos.index');
        Route::get('/nuevo', PacienteForm::class)->name('protocolos.create');
        Route::get('/{id}/editar', PacienteForm::class)->name('protocolos.edit');
    });
});
