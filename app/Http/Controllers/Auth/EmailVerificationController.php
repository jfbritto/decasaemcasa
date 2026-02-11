<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
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

        if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            return redirect()->route('verification.notice')
                ->with('error', 'Link de verificação inválido.');
        }

        if ($user->hasVerifiedEmail()) {
            $redirect = $user->isAdmin() ? route('admin.dashboard') : route('inscricao.create');

            return redirect($redirect)
                ->with('info', 'Seu e-mail já foi verificado.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        $redirect = $user->isAdmin() ? route('admin.dashboard') : route('inscricao.create');

        return redirect($redirect)
            ->with('success', 'E-mail verificado com sucesso!');
    }

    /**
     * Reenvia o email de verificação
     */
    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            $redirect = $request->user()->isAdmin() ? route('admin.dashboard') : route('inscricao.create');

            return redirect($redirect)
                ->with('info', 'Seu e-mail já foi verificado.');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Link de verificação reenviado! Verifique sua caixa de entrada.');
    }
}
