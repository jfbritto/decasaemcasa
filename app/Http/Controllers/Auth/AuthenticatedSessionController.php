<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Verificar se o email foi verificado (exceto para admins)
            if (! $user->isAdmin() && ! $user->hasVerifiedEmail()) {
                Auth::logout();

                return redirect()->route('verification.notice')
                    ->with('error', 'Por favor, verifique seu e-mail antes de fazer login. Verifique sua caixa de entrada.');
            }

            if ($user->isAdmin()) {
                return redirect()->intended(route('admin.dashboard'))
                    ->with('success', 'Bem-vindo, '.$user->name.'!');
            }

            return redirect()->intended(route('inscricao.create'))
                ->with('success', 'Bem-vindo, '.$user->name.'!');
        }

        return back()->withErrors([
            'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
        ])->onlyInput('email');
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
