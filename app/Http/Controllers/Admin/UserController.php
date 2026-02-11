<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Lista de administradores.
     */
    public function index()
    {
        $users = User::where('role', 'admin')
            ->orderBy('name')
            ->get();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Formulário de criação de admin.
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Armazenar novo admin.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'name.required' => 'Informe o nome.',
            'email.required' => 'Informe o e-mail.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'password.required' => 'Informe a senha.',
            'password.confirmed' => 'As senhas não conferem.',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Administrador criado com sucesso!');
    }

    /**
     * Remover admin (não pode remover a si mesmo).
     */
    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->back()
                ->with('error', 'Você não pode remover sua própria conta.');
        }

        if (! $user->isAdmin()) {
            return redirect()->back()
                ->with('error', 'Usuário não é administrador.');
        }

        $user->delete();

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Administrador removido com sucesso!');
    }

    /**
     * Tela de perfil do admin logado.
     */
    public function profile()
    {
        $user = Auth::user();

        return view('admin.users.profile', compact('user'));
    }

    /**
     * Atualizar dados do perfil.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
        ]);

        $user->update($validated);

        return redirect()->back()
            ->with('success', 'Perfil atualizado com sucesso!');
    }

    /**
     * Atualizar senha.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Informe a senha atual.',
            'password.required' => 'Informe a nova senha.',
            'password.confirmed' => 'As senhas não conferem.',
        ]);

        $user = Auth::user();

        if (! Hash::check($request->current_password, $user->password)) {
            return redirect()->back()
                ->withErrors(['current_password' => 'A senha atual está incorreta.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->back()
            ->with('success', 'Senha alterada com sucesso!');
    }
}
