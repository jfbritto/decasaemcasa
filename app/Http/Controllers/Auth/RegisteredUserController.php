<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'cpf' => 'nullable|string|max:14',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'cpf' => $request->cpf,
            'phone' => $request->phone,
            'role' => 'client',
        ]);

        event(new Registered($user));

        // Enviar email de verificação
        $user->sendEmailVerificationNotification();

        // Não fazer login automático - usuário precisa verificar email primeiro
        return redirect()->route('verification.notice')
            ->with('success', 'Conta criada com sucesso! Por favor, verifique seu e-mail para ativar sua conta.');
    }
}

