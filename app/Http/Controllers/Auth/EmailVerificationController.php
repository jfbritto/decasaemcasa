<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * Exibe a página de aviso de verificação de email
     */
    public function notice()
    {
        return view('auth.verify-email');
    }

    /**
     * Verifica o email do usuário
     */
    public function verify(Request $request)
    {
        $user = \App\Models\User::findOrFail($request->route('id'));

        if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            return redirect()->route('verification.notice')
                ->with('error', 'Link de verificação inválido.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('events.index')
                ->with('info', 'Seu e-mail já foi verificado.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->route('events.index')
            ->with('success', 'E-mail verificado com sucesso! Agora você pode garantir seus lugares nos encontros.');
    }

    /**
     * Reenvia o email de verificação
     */
    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('events.index')
                ->with('info', 'Seu e-mail já foi verificado.');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Link de verificação reenviado! Verifique sua caixa de entrada.');
    }
}
