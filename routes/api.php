<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ConfigController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\InscriptionController;
use App\Http\Controllers\Api\V1\PushTokenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API do app do participante (/api/v1)
|--------------------------------------------------------------------------
| Spec: docs/superpowers/specs/2026-11-08-app-mobile-design.md (AM9 a AM11).
| O grupo "api" já aplica o throttle:api (60 por minuto por participante ou
| IP). O login tem limites próprios (AM5).
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Públicas
    Route::get('config', [ConfigController::class, 'show'])->name('config');
    Route::get('events', [EventController::class, 'index'])->name('events.index');
    Route::get('events/{id}', [EventController::class, 'show'])->name('events.show');

    Route::post('auth/code', [AuthController::class, 'requestCode'])
        ->middleware('throttle:app-auth-code')
        ->name('auth.code');
    Route::post('auth/verify', [AuthController::class, 'verify'])
        ->middleware('throttle:app-auth-verify')
        ->name('auth.verify');

    // Do participante
    Route::middleware(['auth:sanctum', 'participant'])->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('inscriptions', [InscriptionController::class, 'index'])->name('inscriptions.index');
        Route::post('inscriptions', [InscriptionController::class, 'store'])->name('inscriptions.store');
        Route::get('inscriptions/{id}', [InscriptionController::class, 'show'])->name('inscriptions.show');
        Route::post('inscriptions/{id}/payment-proof', [InscriptionController::class, 'uploadPaymentProof'])->name('inscriptions.payment-proof');
        Route::post('inscriptions/{id}/social-request', [InscriptionController::class, 'submitSocialRequest'])->name('inscriptions.social-request');
        Route::post('inscriptions/{id}/cancel', [InscriptionController::class, 'cancel'])->name('inscriptions.cancel');

        Route::post('push-tokens', [PushTokenController::class, 'store'])->name('push-tokens.store');
        Route::delete('push-tokens/{token}', [PushTokenController::class, 'destroy'])
            ->where('token', '.+')
            ->name('push-tokens.destroy');

        Route::delete('account', [AccountController::class, 'destroy'])->name('account.destroy');
    });
});
