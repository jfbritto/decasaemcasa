<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\InscriptionController as AdminInscriptionController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\PeriodController as AdminPeriodController;
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
Route::get('/inscricao/{token}/comprovante-enviado', [InscriptionController::class, 'uploadSuccess'])->name('inscricao.upload-sucesso');
Route::post('/inscricao/{token}/solicitacao-social', [InscriptionController::class, 'submitSocialRequest'])->name('inscricao.solicitacao-social');
Route::post('/inscricao/{token}/cancelar', [InscriptionController::class, 'cancel'])->name('inscricao.cancel');

// Preview de Emails (apenas em ambiente local)
if (app()->environment('local')) {
    Route::get('/email-preview/{template}', function (string $template) {
        $allowed = ['inscription-received', 'inscription-approved', 'inscription-waitlisted', 'inscription-confirmed', 'inscription-rejected', 'inscription-cancelled', 'payment-reminder', 'social-request-submitted', 'social-request-approved', 'social-request-rejected'];
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

        // Preencher dados de preview para templates de solicitação social
        if (str_starts_with($template, 'social-request')) {
            $inscription->social_request_status = match ($template) {
                'social-request-submitted' => 'pendente',
                'social-request-approved' => 'aprovado',
                'social-request-rejected' => 'rejeitado',
            };
            $inscription->social_request_reason = $inscription->social_request_reason ?: 'Estou desempregada no momento e não consigo arcar com o valor de referência, mas tenho muito interesse em participar.';
            $inscription->social_request_amount = $inscription->social_request_amount ?: 30.00;
            $inscription->social_request_admin_message = $template === 'social-request-rejected'
                ? 'Agradecemos a transparência. Nesta edição, todas as bolsas sociais já foram preenchidas.'
                : ($template === 'social-request-approved' ? 'Combinado! Obrigado por compartilhar sua situação com a gente.' : null);
        }

        $viewData = [
            'inscription' => $inscription,
            'event' => $event,
            'statusUrl' => $statusUrl,
        ];

        if ($template === 'social-request-approved') {
            $viewData['amountFormatted'] = 'R$ '.number_format((float) $inscription->social_request_amount, 2, ',', '.');
        }

        return view("emails.{$template}", $viewData);
    })->name('email.preview');
}

// Área Administrativa
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::post('/filtro-periodo', [AdminPeriodController::class, 'update'])->name('filtro-periodo');

    // Eventos / Encontros
    Route::resource('events', AdminEventController::class)->except(['show']);
    Route::get('events/{event}', [AdminEventController::class, 'show'])->name('events.show')->withTrashed();
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
    Route::post('/inscricoes/{inscription}/cancelar', [AdminInscriptionController::class, 'cancel'])->name('inscricoes.cancelar');
    Route::post('/inscricoes/{inscription}/reverter', [AdminInscriptionController::class, 'revertCancellation'])->name('inscricoes.reverter');
    Route::post('/inscricoes/{inscription}/reverter-rejeicao', [AdminInscriptionController::class, 'revertRejection'])->name('inscricoes.reverter-rejeicao');
    Route::post('/inscricoes/{inscription}/send-reminder', [AdminInscriptionController::class, 'sendReminder'])->name('inscricoes.send-reminder');
    Route::patch('/inscricoes/{inscription}/notes', [AdminInscriptionController::class, 'updateNotes'])->name('inscricoes.update-notes');
    Route::patch('/inscricoes/{inscription}/contribution', [AdminInscriptionController::class, 'updateContribution'])->name('inscricoes.update-contribution');
    Route::patch('/inscricoes/{inscription}/participant', [AdminInscriptionController::class, 'updateParticipant'])->name('inscricoes.update-participant');
    Route::post('/inscricoes/{inscription}/migrar', [AdminInscriptionController::class, 'migrate'])->name('inscricoes.migrar');
    Route::post('/inscricoes/{inscription}/social/aprovar', [AdminInscriptionController::class, 'approveSocialRequest'])->name('inscricoes.social-aprovar');
    Route::post('/inscricoes/{inscription}/social/rejeitar', [AdminInscriptionController::class, 'rejectSocialRequest'])->name('inscricoes.social-rejeitar');

    // Notificações
    Route::get('/notificacoes', [AdminNotificationController::class, 'index'])->name('notificacoes.index');
    Route::post('/notificacoes/{notification}/resend', [AdminNotificationController::class, 'resend'])->name('notificacoes.resend');
    Route::post('/notificacoes/bulk-resend-rate-limit', [AdminNotificationController::class, 'bulkResendRateLimit'])->name('notificacoes.bulk-resend-rate-limit');

    // Gestão de Admins
    Route::get('/usuarios', [AdminUserController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/create', [AdminUserController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios', [AdminUserController::class, 'store'])->name('usuarios.store');
    Route::delete('/usuarios/{user}', [AdminUserController::class, 'destroy'])->name('usuarios.destroy');

    // Duplicar evento
    Route::post('events/{event}/duplicate', [AdminEventController::class, 'duplicate'])->name('events.duplicate');

    // Notificar pendentes sobre evento esgotado
    Route::post('events/{event}/notify-event-full', [AdminEventController::class, 'notifyEventFull'])->name('events.notify-event-full');

    // Enviar mensagem customizada para confirmados
    Route::post('events/{event}/send-custom-message', [AdminEventController::class, 'sendCustomMessage'])->name('events.send-custom-message');

    // Perfil do Admin
    Route::get('/profile', [AdminUserController::class, 'profile'])->name('profile');
    Route::put('/profile', [AdminUserController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AdminUserController::class, 'updatePassword'])->name('profile.update-password');
});
