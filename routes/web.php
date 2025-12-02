<?php
// filepath: c:\laragon\www\sales-puterako\routes\web.php

use App\Http\Controllers\JasaController;
use App\Http\Controllers\JasaDetailController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PenawaranController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MitraController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\UserController;

// Redirect root ke login
Route::get('/', function () {
    return redirect()->route('login');
});

// Auth routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Dashboard route (protected)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // Penawaran routes (protected)
    Route::prefix('penawaran')->group(function () {
        Route::get('/list', [PenawaranController::class, 'index'])->name('penawaran.list');
        Route::get('/detail-penawaran', [PenawaranController::class, 'show'])->name('penawaran.show');
        Route::post('/detail-penawaran/save', [PenawaranController::class, 'save'])->name('penawaran.save');
        Route::post('/tambah-penawaran', [PenawaranController::class, 'store'])->name('penawaran.store');
        Route::get('{id}/follow-up', [PenawaranController::class, 'followUp'])->name('penawaran.followUp');
        Route::post('{id}/follow-up/store', [PenawaranController::class, 'storeFollowUp'])->name('penawaran.followUp.store');
        Route::get('/rekap-survey', [PenawaranController::class, 'rekapSurvey'])->name('penawaran.rekap-survey');
        Route::get('/preview', [PenawaranController::class, 'preview'])->name('penawaran.preview');
        Route::get('/export-pdf', [PenawaranController::class, 'exportPdf'])->name('penawaran.exportPdf');
        Route::post('/{id}/save-notes', [PenawaranController::class, 'saveNotes'])->name('penawaran.saveNotes');
        Route::post('/{id}/save-best-price', [PenawaranController::class, 'saveBestPrice'])->name('penawaran.saveBestPrice');
        Route::post('/{id}/create-revision', [PenawaranController::class, 'createRevision'])->name('penawaran.createRevision');
        Route::post('/{id}/update-status', [PenawaranController::class, 'updateStatus'])->name('penawaran.updateStatus');
        Route::get('/filter', [PenawaranController::class, 'filter'])->name('penawaran.filter');
        Route::get('/datatable', [PenawaranController::class, 'datatable'])->name('penawaran.datatable');
        Route::get('/{id}/edit', [PenawaranController::class, 'edit'])->name('edit');          // AJAX get data
        Route::put('/{id}', [PenawaranController::class, 'update'])->name('update');
        Route::post('/{id}/restore', [PenawaranController::class, 'restore'])->name('penawaran.restore');
        Route::delete('/{id}', [PenawaranController::class, 'destroy'])->name('penawaran.delete');
    });

    // Jasa routes (protected)
    Route::prefix('jasa')->group(function () {
        Route::get('/detail', [JasaDetailController::class, 'show'])->name('jasa.detail');
        Route::post('/save', [JasaController::class, 'save'])->name('jasa.save');
        Route::post('/save-ringkasan/{id_penawaran}', [JasaController::class, 'saveRingkasan'])->name('jasa.saveRingkasan');
    });

    // Mitra routes (protected)
    Route::prefix('mitra')->group(function () {
        Route::get('/list', [MitraController::class, 'index'])->name('mitra.list');
        Route::get('/filter', [MitraController::class, 'filter'])->name('mitra.filter');
        Route::post('/store', [MitraController::class, 'store'])->name('mitra.store');
        Route::get('/{id}/edit', [MitraController::class, 'edit'])->name('edit');
        Route::put('/{id}', [MitraController::class, 'update'])->name('update');
        Route::delete('/{id}', [MitraController::class, 'destroy'])->name('destroy');
    });

    // Rekap routes (protected)
    Route::prefix('rekap')->group(function () {
        Route::get('/list', [RekapController::class, 'index'])->name('rekap.list');
        Route::get('/create', [RekapController::class, 'create'])->name('rekap.create');
        Route::post('/store', [RekapController::class, 'store'])->name('rekap.store');
        Route::get('/{id}', [RekapController::class, 'show'])->name('rekap.show');
        Route::get('/{id}/edit', [RekapController::class, 'edit'])->name('rekap.edit');
        Route::put('/{id}', [RekapController::class, 'update'])->name('rekap.update');
        Route::delete('/{id}', [RekapController::class, 'destroy'])->name('rekap.delete');
        Route::post('/{rekap_id}/add-item', [RekapController::class, 'addItem'])->name('rekap.addItem');
        Route::post('/{rekap_id}/update-items', [RekapController::class, 'updateItems'])->name('rekap.updateItems');    
    });
    Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('users.index');
    Route::get('/filter', [UserController::class, 'filter'])->name('users.filter');
    Route::post('/', [UserController::class, 'store'])->name('users.store');
    Route::get('/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/permissions', [UserController::class, 'permissions'])->name('users.permissions');
    Route::post('/{user}/permissions', [UserController::class, 'updatePermissions'])->name('users.permissions.update');
    });
});
