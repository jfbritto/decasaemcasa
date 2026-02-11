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

// Preview de Emails (apenas em ambiente local)
if (app()->environment('local')) {
    Route::get('/email-preview/{template}', function (string $template) {
        $allowed = ['inscription-received', 'inscription-approved', 'inscription-waitlisted', 'inscription-confirmed'];
        if (! in_array($template, $allowed)) {
            abort(404, 'Template não encontrado. Disponíveis: '.implode(', ', $allowed));
        }

        $event = \App\Models\Event::first();
        if (! $event) {
            return 'Nenhum evento cadastrado. Crie um evento primeiro.';
        }

        $inscription = $event->inscriptions()->first();
        if (! $inscription) {
            // Cria dados fictícios para preview
            $inscription = new \App\Models\Inscription([
                'full_name' => 'Maria da Silva',
                'email' => 'maria@example.com',
                'whatsapp' => '(27) 99999-0000',
                'token' => 'preview-token',
            ]);
            $inscription->setRelation('event', $event);
        }

        $statusUrl = route('inscricao.status', $inscription->token ?? 'preview-token');

        return view("emails.{$template}", [
            'inscription' => $inscription,
            'event' => $event,
            'statusUrl' => $statusUrl,
        ]);
    })->name('email.preview');
}

// Área Administrativa
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Eventos / Encontros
    Route::resource('events', AdminEventController::class);
    Route::get('events/{event}/participantes-pdf', [AdminEventController::class, 'exportPdf'])->name('events.participantes-pdf');

    // Inscrições (Curadoria)
    Route::get('/inscricoes', [AdminInscriptionController::class, 'index'])->name('inscricoes.index');
    Route::get('/inscricoes/{inscription}', [AdminInscriptionController::class, 'show'])->name('inscricoes.show');
    Route::post('/inscricoes/{inscription}/aprovar', [AdminInscriptionController::class, 'approve'])->name('inscricoes.aprovar');
    Route::post('/inscricoes/{inscription}/fila-espera', [AdminInscriptionController::class, 'waitlist'])->name('inscricoes.fila-espera');
    Route::post('/inscricoes/{inscription}/confirmar', [AdminInscriptionController::class, 'confirm'])->name('inscricoes.confirmar');
    Route::patch('/inscricoes/{inscription}/notes', [AdminInscriptionController::class, 'updateNotes'])->name('inscricoes.update-notes');
});
