<?php

use App\Http\Controllers\JasaController;
use App\Http\Controllers\JasaDetailController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PenawaranController;


Route::get('/', function () {
    return view('layouts.app');
});

// Penawaran routes
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
});

Route::prefix('jasa')->group(function () {
    Route::get('/detail', [JasaDetailController::class, 'show'])->name('jasa.detail');
    // Route::post('/save', [JasaDetailController::class, 'save'])->name('jasa.save');
    Route::post('/save', [JasaController::class, 'save'])->name('jasa.save');
    Route::post('/save-ringkasan/{id_penawaran}', [JasaController::class, 'saveRingkasan'])->name('jasa.saveRingkasan');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');