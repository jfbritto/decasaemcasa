<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\InscriptionController as AdminInscriptionController;
use App\Http\Controllers\InscriptionController;
use Illuminate\Support\Facades\Route;

// Página inicial -> Formulário de inscrição
Route::get('/', function () {
    return redirect()->route('inscricao.create');
});

// Autenticação
require __DIR__.'/auth.php';

// Inscrição Pública (sem autenticação)
Route::get('/inscricao', [InscriptionController::class, 'create'])->name('inscricao.create');
Route::post('/inscricao', [InscriptionController::class, 'store'])->name('inscricao.store');
Route::get('/inscricao/{token}', [InscriptionController::class, 'status'])->name('inscricao.status');
Route::post('/inscricao/{token}/comprovante', [InscriptionController::class, 'uploadPaymentProof'])->name('inscricao.upload-comprovante');

// Área Administrativa
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Eventos / Encontros
    Route::resource('events', AdminEventController::class);

    // Inscrições (Curadoria)
    Route::get('/inscricoes', [AdminInscriptionController::class, 'index'])->name('inscricoes.index');
    Route::get('/inscricoes/{inscription}', [AdminInscriptionController::class, 'show'])->name('inscricoes.show');
    Route::post('/inscricoes/{inscription}/aprovar', [AdminInscriptionController::class, 'approve'])->name('inscricoes.aprovar');
    Route::post('/inscricoes/{inscription}/fila-espera', [AdminInscriptionController::class, 'waitlist'])->name('inscricoes.fila-espera');
    Route::post('/inscricoes/{inscription}/confirmar', [AdminInscriptionController::class, 'confirm'])->name('inscricoes.confirmar');
    Route::patch('/inscricoes/{inscription}/notes', [AdminInscriptionController::class, 'updateNotes'])->name('inscricoes.update-notes');
});
