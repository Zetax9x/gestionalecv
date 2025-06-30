<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Mostra il form di login
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Gestisce il login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->only('email'));
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => 'Le credenziali fornite non sono corrette.',
            ]);
        }

        $user = Auth::user();

        if (!$user->isAttivo()) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Il tuo account è stato disattivato. Contatta l\'amministratore.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    /**
     * Gestisce il logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Logout effettuato con successo.');
    }

    /**
     * Mostra il form di registrazione
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Gestisce la registrazione
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'cognome' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'nome' => $request->nome,
            'cognome' => $request->cognome,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'ruolo' => 'volontario',
            'attivo' => false,
        ]);

        return redirect()->route('login')->with('success', 'Registrazione completata!');
    }

    /**
     * Aggiorna ultimo accesso (AJAX)
     */
    public function updateAccesso(Request $request)
    {
        if (Auth::check()) {
            return response()->json(['status' => 'success']);
        }
        
        return response()->json(['status' => 'error'], 401);
    }
}