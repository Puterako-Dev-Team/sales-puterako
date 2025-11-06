<?php
// filepath: c:\laragon\www\sales-puterako\routes\web.php

use App\Http\Controllers\JasaController;
use App\Http\Controllers\JasaDetailController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PenawaranController;
use App\Http\Controllers\AuthController;

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
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Penawaran routes (protected)
    Route::prefix('penawaran')->group(function () {
        Route::get('/list', [PenawaranController::class, 'index'])->name('penawaran.list');
        Route::get('/detail-penawaran', [PenawaranController::class, 'show'])->name('penawaran.show');
        Route::post('/detail-penawaran/save', [PenawaranController::class, 'save'])->name('penawaran.save');
        Route::post('/tambah-penawaran', [PenawaranController::class, 'store'])->name('penawaran.store');
        Route::get('/follow-up', [PenawaranController::class, 'followUp'])->name('penawaran.followup');
        Route::get('/rekap-survey', [PenawaranController::class, 'rekapSurvey'])->name('penawaran.rekap-survey');
        Route::get('/preview', [PenawaranController::class, 'preview'])->name('penawaran.preview');
        Route::get('/export-pdf', [PenawaranController::class, 'exportPdf'])->name('penawaran.exportPdf');
        Route::post('/{id}/save-notes', [PenawaranController::class, 'saveNotes'])->name('penawaran.saveNotes');
        Route::post('/{id}/save-best-price', [PenawaranController::class, 'saveBestPrice'])->name('penawaran.saveBestPrice');
        Route::post('/{id}/create-revision', [PenawaranController::class, 'createRevision'])->name('penawaran.createRevision');
        Route::post('/{id}/update-status', [PenawaranController::class, 'updateStatus'])->name('penawaran.updateStatus');
    });

    // Jasa routes (protected)
    Route::prefix('jasa')->group(function () {
        Route::get('/detail', [JasaDetailController::class, 'show'])->name('jasa.detail');
        Route::post('/save', [JasaController::class, 'save'])->name('jasa.save');
        Route::post('/save-ringkasan/{id_penawaran}', [JasaController::class, 'saveRingkasan'])->name('jasa.saveRingkasan');
    });
});

