<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\InscriptionController as AdminInscriptionController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
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
Route::post('/inscricao/{token}/cancelar', [InscriptionController::class, 'cancel'])->name('inscricao.cancel');

// Preview de Emails (apenas em ambiente local)
if (app()->environment('local')) {
    Route::get('/email-preview/{template}', function (string $template) {
        $allowed = ['inscription-received', 'inscription-approved', 'inscription-waitlisted', 'inscription-confirmed', 'inscription-rejected', 'inscription-cancelled', 'payment-reminder'];
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
    Route::get('/inscricoes/export-csv', [AdminInscriptionController::class, 'exportCsv'])->name('inscricoes.export-csv');
    Route::post('/inscricoes/bulk-action', [AdminInscriptionController::class, 'bulkAction'])->name('inscricoes.bulk-action');
    Route::get('/inscricoes/{inscription}', [AdminInscriptionController::class, 'show'])->name('inscricoes.show');
    Route::post('/inscricoes/{inscription}/aprovar', [AdminInscriptionController::class, 'approve'])->name('inscricoes.aprovar');
    Route::post('/inscricoes/{inscription}/fila-espera', [AdminInscriptionController::class, 'waitlist'])->name('inscricoes.fila-espera');
    Route::post('/inscricoes/{inscription}/confirmar', [AdminInscriptionController::class, 'confirm'])->name('inscricoes.confirmar');
    Route::post('/inscricoes/{inscription}/rejeitar', [AdminInscriptionController::class, 'reject'])->name('inscricoes.rejeitar');
    Route::post('/inscricoes/{inscription}/send-reminder', [AdminInscriptionController::class, 'sendReminder'])->name('inscricoes.send-reminder');
    Route::patch('/inscricoes/{inscription}/notes', [AdminInscriptionController::class, 'updateNotes'])->name('inscricoes.update-notes');

    // Notificações
    Route::get('/notificacoes', [AdminNotificationController::class, 'index'])->name('notificacoes.index');
    Route::post('/notificacoes/{notification}/resend', [AdminNotificationController::class, 'resend'])->name('notificacoes.resend');

    // Gestão de Admins
    Route::get('/usuarios', [AdminUserController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/create', [AdminUserController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios', [AdminUserController::class, 'store'])->name('usuarios.store');
    Route::delete('/usuarios/{user}', [AdminUserController::class, 'destroy'])->name('usuarios.destroy');

    // Duplicar evento
    Route::post('events/{event}/duplicate', [AdminEventController::class, 'duplicate'])->name('events.duplicate');

    // Perfil do Admin
    Route::get('/profile', [AdminUserController::class, 'profile'])->name('profile');
    Route::put('/profile', [AdminUserController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AdminUserController::class, 'updatePassword'])->name('profile.update-password');
});
