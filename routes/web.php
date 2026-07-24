<?php

use App\Http\Controllers\Cliente\ListaPreciosPdfController;
use App\Http\Controllers\Clientes\CuentaCorrienteClientesExcelController;
use App\Http\Controllers\Clientes\CuentaCorrienteClientesPdfController;
use App\Http\Controllers\Clientes\CuentaCorrienteDetalleExcelController;
use App\Http\Controllers\Clientes\CuentaCorrienteDetallePdfController;
use App\Http\Controllers\Facturacion\CompAfipPdfController;
use App\Http\Controllers\Protocolos\EtiquetasTuboPdfController;
use App\Http\Controllers\Protocolos\InformePacientePdfController;
use App\Livewire\Abm\Clientes\ClienteForm;
use App\Livewire\Abm\Clientes\ClienteIndex;
use App\Livewire\Abm\Derivaciones\DerivacionForm;
use App\Livewire\Abm\Derivaciones\DerivacionIndex as CentrosDerivacionIndex;
use App\Livewire\Abm\Especies\EspecieForm;
use App\Livewire\Abm\Especies\EspecieIndex;
use App\Livewire\Abm\Razas\RazaForm;
use App\Livewire\Abm\Razas\RazaIndex;
use App\Livewire\Abm\MuestrasPorDeterminacion\MuestrasPorDeterminacionIndex;
use App\Livewire\Abm\Requerimientos\RequerimientoForm;
use App\Livewire\Abm\Requerimientos\RequerimientoIndex;
use App\Livewire\Abm\Usuarios\UsuarioForm;
use App\Livewire\Abm\Usuarios\UsuarioIndex;
use App\Livewire\Cliente\ClienteHome;
use App\Livewire\Cliente\ListaPrecios;
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
use App\Livewire\Facturacion\ComprobantesAfipIndex;
use App\Livewire\Protocolos\DerivacionIndex;
use App\Livewire\Protocolos\PacienteDeterminaciones;
use App\Livewire\Protocolos\PacienteForm;
use App\Livewire\Protocolos\PacienteIndex;
use App\Livewire\Protocolos\PacienteResultados;
use App\Livewire\Tesoreria\ConceptoForm;
use App\Livewire\Tesoreria\ConceptoIndex;
use App\Livewire\Tesoreria\ProveedorForm;
use App\Livewire\Tesoreria\ProveedorIndex;
use App\Livewire\Tesoreria\CuentaDetalleForm;
use App\Livewire\Tesoreria\CuentaDetalleIndex;
use App\Livewire\Tesoreria\CuentaForm;
use App\Livewire\Tesoreria\CuentaIndex;
use App\Livewire\Tesoreria\MovimientoIndex;
use App\Livewire\Tesoreria\MovimientosCajaIndex;
use App\Livewire\Tesoreria\MovimientosEntreCuentas;
use App\Livewire\Tesoreria\SaldosPorDiaIndex;
use App\Livewire\Tesoreria\TransferenciaIntercuenta;
use App\Support\Facturacion\FacturacionAfipConfig;
use App\Support\Tesoreria\TesoreriaConfig;
use App\Http\Controllers\Listados\CantidadDeterminacionesComparacChartPdfController;
use App\Http\Controllers\Listados\CantidadDeterminacionesComparacExcelController;
use App\Http\Controllers\Listados\CantidadDeterminacionesComparacPdfController;
use App\Http\Controllers\Listados\ExcelPacientesExcelController;
use App\Http\Controllers\Listados\HistorialDeterminacionesExcelController;
use App\Http\Controllers\Listados\HistorialDeterminacionesPdfController;
use App\Http\Controllers\Listados\ListadoEstadisticoPacientesExcelController;
use App\Http\Controllers\Listados\ListadoEstadisticoPacientesPdfController;
use App\Livewire\Listados\CantidadDeterminacionesComparac;
use App\Livewire\Listados\EstimacionCostos;
use App\Livewire\Listados\ExcelPacientes;
use App\Livewire\Listados\HistorialDeterminaciones;
use App\Livewire\Listados\ListadoEstadisticoPacientes;
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
    Route::prefix('cliente')->middleware('menu.portal:cliente')->group(function () {
        Route::get('/', ClienteHome::class)->name('cliente.home');
        Route::get('/pacientes', PacienteIndex::class)->name('cliente.pacientes');
        Route::get('/pacientes/informe/{ref}', InformePacientePdfController::class)
            ->middleware('no-store')
            ->where('ref', '[A-Za-z0-9_-]+')
            ->name('cliente.pacientes.informe');
        Route::get('/lista-precios', ListaPrecios::class)->name('cliente.lista-precios');
        Route::get('/lista-precios/pdf', ListaPreciosPdfController::class)
            ->middleware(['throttle:20,1', 'no-store'])
            ->name('cliente.lista-precios.pdf');
        Route::get('/estimacion-costos', EstimacionCostos::class)->name('cliente.estimacion-costos');
    });

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

    Route::prefix('abm/especies')->middleware(['menu.portal:laboratorio', 'permiso:1'])->group(function () {
        Route::get('/', EspecieIndex::class)->name('abm.especies.index');
        Route::get('/nuevo', EspecieForm::class)->name('abm.especies.create');
        Route::get('/{id}/editar', EspecieForm::class)->name('abm.especies.edit');
    });

    Route::prefix('abm/razas')->middleware(['menu.portal:laboratorio', 'permiso:1'])->group(function () {
        Route::get('/', RazaIndex::class)->name('abm.razas.index');
        Route::get('/nuevo', RazaForm::class)->name('abm.razas.create');
        Route::get('/{id}/editar', RazaForm::class)->name('abm.razas.edit');
    });

    Route::prefix('abm/usuarios')->middleware(['menu.portal:staff', 'permiso:9'])->group(function () {
        Route::get('/', UsuarioIndex::class)->name('abm.usuarios.index');
        Route::get('/nuevo', UsuarioForm::class)->name('abm.usuarios.create');
        Route::get('/{id}/editar', UsuarioForm::class)->name('abm.usuarios.edit');
    });

    Route::prefix('abm/derivaciones')->middleware(['menu.portal:laboratorio', 'permiso:8'])->group(function () {
        Route::get('/', CentrosDerivacionIndex::class)->name('abm.derivaciones.index');
        Route::get('/nuevo', DerivacionForm::class)->name('abm.derivaciones.create');
        Route::get('/{id}/editar', DerivacionForm::class)->name('abm.derivaciones.edit');
    });

    Route::prefix('abm/procedimientos')->middleware(['menu.portal:laboratorio', 'permiso:8'])->group(function () {
        Route::get('/', RequerimientoIndex::class)->name('abm.requerimientos.index');
        Route::get('/nuevo', RequerimientoForm::class)->name('abm.requerimientos.create');
        Route::get('/{id}/editar', RequerimientoForm::class)->name('abm.requerimientos.edit');
    });

    Route::prefix('abm/muestras-por-determinacion')->middleware(['menu.portal:laboratorio', 'permiso:8'])->group(function () {
        Route::get('/', MuestrasPorDeterminacionIndex::class)->name('abm.muestras-por-determinacion.index');
    });

    Route::prefix('clientes/cuenta-corriente')->middleware(['menu.portal:laboratorio', 'permiso:6'])->group(function () {
        Route::get('/', CuentaCorrienteIndex::class)->name('clientes.cuenta-corriente.index');
        Route::get('/pdf', CuentaCorrienteClientesPdfController::class)->name('clientes.cuenta-corriente.pdf');
        Route::get('/excel', CuentaCorrienteClientesExcelController::class)->name('clientes.cuenta-corriente.excel');
        Route::get('/{id}', CuentaCorrienteDetalle::class)->name('clientes.cuenta-corriente.detalle');
        Route::get('/{id}/pdf', CuentaCorrienteDetallePdfController::class)->name('clientes.cuenta-corriente.detalle.pdf');
        Route::get('/{id}/excel', CuentaCorrienteDetalleExcelController::class)->name('clientes.cuenta-corriente.detalle.excel');
    });

    if (FacturacionAfipConfig::habilitada()) {
        Route::prefix('facturacion/afip')->middleware(['menu.portal:staff', 'permiso:6'])->group(function () {
            Route::get('/comprobantes/{ref}', ComprobantesAfipIndex::class)
                ->where('ref', '[A-Za-z0-9_-]+')
                ->name('facturacion.afip.comprobantes');
            Route::get('/comprobante/{ref}', CompAfipPdfController::class)
                ->where('ref', '[A-Za-z0-9_-]+')
                ->middleware(['throttle:30,1', 'no-store'])
                ->name('facturacion.afip.comprobante.pdf');
        });
    }

    Route::prefix('tesoreria/movimientos')->middleware(['menu.portal:staff', 'permiso:6'])->group(function () {
        $movimientosComponent = TesoreriaConfig::usaPacientes()
            ? MovimientosCajaIndex::class
            : MovimientoIndex::class;
        Route::get('/', $movimientosComponent)->name('tesoreria.movimientos.index');
    });

    if (TesoreriaConfig::usaPacientes()) {
        Route::prefix('tesoreria/movimientos-entre-cuentas')->middleware(['menu.portal:staff', 'permiso:6'])->group(function () {
            Route::get('/', MovimientosEntreCuentas::class)->name('tesoreria.movimientos-entre-cuentas.index');
        });

        Route::prefix('tesoreria/saldos-por-dia')->middleware(['menu.portal:staff', 'permiso:6'])->group(function () {
            Route::get('/', SaldosPorDiaIndex::class)->name('tesoreria.saldos-por-dia.index');
        });

        Route::prefix('tesoreria/conceptos')->middleware(['menu.portal:staff', 'permiso:6'])->group(function () {
            Route::get('/', ConceptoIndex::class)->name('tesoreria.conceptos.index');
            Route::get('/nuevo', ConceptoForm::class)->name('tesoreria.conceptos.create');
            Route::get('/{id}/editar', ConceptoForm::class)->name('tesoreria.conceptos.edit');
        });

        Route::prefix('tesoreria/proveedores')->middleware(['menu.portal:staff', 'permiso:6'])->group(function () {
            Route::get('/', ProveedorIndex::class)->name('tesoreria.proveedores.index');
            Route::get('/nuevo', ProveedorForm::class)->name('tesoreria.proveedores.create');
            Route::get('/{id}/editar', ProveedorForm::class)->name('tesoreria.proveedores.edit');
        });
    }

    if (TesoreriaConfig::usaMovimientos()) {
        Route::prefix('tesoreria/transferencias')->middleware(['menu.portal:staff', 'permiso:6'])->group(function () {
            Route::get('/', TransferenciaIntercuenta::class)->name('tesoreria.transferencias.index');
        });

        Route::prefix('tesoreria/cuentas')->middleware(['menu.portal:staff', 'permiso:6'])->group(function () {
            Route::get('/', CuentaIndex::class)->name('tesoreria.cuentas.index');
            Route::get('/nuevo', CuentaForm::class)->name('tesoreria.cuentas.create');
            Route::get('/{id}/editar', CuentaForm::class)->name('tesoreria.cuentas.edit');
        });

        Route::prefix('tesoreria/cuentas-detalle')->middleware(['menu.portal:staff', 'permiso:6'])->group(function () {
            Route::get('/', CuentaDetalleIndex::class)->name('tesoreria.cuentas-detalle.index');
            Route::get('/nuevo', CuentaDetalleForm::class)->name('tesoreria.cuentas-detalle.create');
            Route::get('/{id}/editar', CuentaDetalleForm::class)->name('tesoreria.cuentas-detalle.edit');
        });
    }

    Route::prefix('protocolos')->middleware(['menu.portal:staff', 'permiso:3'])->group(function () {
        Route::get('/', PacienteIndex::class)->name('protocolos.index');
        Route::get('/nuevo', PacienteForm::class)->name('protocolos.create');
        Route::get('/etiquetas/{ref}', EtiquetasTuboPdfController::class)
            ->where('ref', '[A-Za-z0-9_-]+')
            ->middleware(['throttle:30,1', 'no-store'])
            ->name('protocolos.etiquetas');
        Route::get('/{id}/editar', PacienteForm::class)->name('protocolos.edit');
        Route::get('/{id}/determinaciones', PacienteDeterminaciones::class)->name('protocolos.determinaciones');
    });

    Route::prefix('derivaciones')->middleware(['menu.portal:staff', 'permiso:3'])->group(function () {
        Route::get('/', DerivacionIndex::class)->name('derivaciones.index');
    });

    Route::prefix('listados')->middleware(['menu.portal:staff', 'permiso:10'])->group(function () {
        Route::get('/estimacion-costos', EstimacionCostos::class)->name('listados.estimacion-costos');
        Route::get('/estadistico-pacientes', ListadoEstadisticoPacientes::class)->name('listados.estadistico-pacientes');
        Route::get('/estadistico-pacientes/pdf', ListadoEstadisticoPacientesPdfController::class)
            ->middleware('throttle:15,1')
            ->name('listados.estadistico-pacientes.pdf');
        Route::get('/estadistico-pacientes/excel', ListadoEstadisticoPacientesExcelController::class)
            ->middleware('throttle:10,1')
            ->name('listados.estadistico-pacientes.excel');
        Route::get('/historial-determinaciones', HistorialDeterminaciones::class)->name('listados.historial-determinaciones');
        Route::get('/historial-determinaciones/pdf', HistorialDeterminacionesPdfController::class)
            ->middleware('throttle:15,1')
            ->name('listados.historial-determinaciones.pdf');
        Route::get('/historial-determinaciones/excel', HistorialDeterminacionesExcelController::class)
            ->middleware('throttle:10,1')
            ->name('listados.historial-determinaciones.excel');
        Route::get('/cantidad-determinaciones-comparac', CantidadDeterminacionesComparac::class)
            ->name('listados.cantidad-determinaciones-comparac');
        Route::get('/cantidad-determinaciones-comparac/pdf', CantidadDeterminacionesComparacPdfController::class)
            ->middleware('throttle:15,1')
            ->name('listados.cantidad-determinaciones-comparac.pdf');
        Route::get('/cantidad-determinaciones-comparac/excel', CantidadDeterminacionesComparacExcelController::class)
            ->middleware('throttle:10,1')
            ->name('listados.cantidad-determinaciones-comparac.excel');
        Route::post('/cantidad-determinaciones-comparac/chart-pdf', CantidadDeterminacionesComparacChartPdfController::class)
            ->middleware('throttle:10,1')
            ->name('listados.cantidad-determinaciones-comparac.chart-pdf');
        Route::get('/excel-pacientes', ExcelPacientes::class)->name('listados.excel-pacientes');
        Route::get('/excel-pacientes/excel', ExcelPacientesExcelController::class)
            ->middleware('throttle:10,1')
            ->name('listados.excel-pacientes.excel');
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
