<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetLinkController extends Controller
{
    /**
     * Exibe o formulário de solicitação de reset de senha
     */
    public function create()
    {
        return view('auth.forgot-password');
    }

    /**
     * Envia o email de reset de senha
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            return back()->with('success', 'Enviamos um link de redefinição de senha para seu e-mail!');
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
