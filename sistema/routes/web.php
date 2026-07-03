<?php

use App\Livewire\Abm\Clientes\ClienteForm;
use App\Livewire\Abm\Clientes\ClienteIndex;
use App\Livewire\AdminDashboard;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Support\LabContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', function () {
    Auth::logout();
    LabContext::clear();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware(['auth', 'lab.context'])->group(function () {
    Route::get('/dashboard', Dashboard::class)
        ->middleware('menu.portal:laboratorio')
        ->name('dashboard');

    Route::prefix('admin')->middleware('menu.portal:administracion')->group(function () {
        Route::get('/dashboard', AdminDashboard::class)->name('admin.dashboard');
    });

    Route::prefix('abm/clientes')->middleware(['menu.portal:laboratorio', 'permiso:0'])->group(function () {
        Route::get('/', ClienteIndex::class)->name('abm.clientes.index');
        Route::get('/nuevo', ClienteForm::class)->name('abm.clientes.create');
        Route::get('/{id}/editar', ClienteForm::class)->name('abm.clientes.edit');
    });
});
