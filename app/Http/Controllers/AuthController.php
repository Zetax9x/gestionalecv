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
        ], [
            'email.required' => 'L\'email è obbligatoria',
            'email.email' => 'Inserisci un\'email valida',
            'password.required' => 'La password è obbligatoria',
            'password.min' => 'La password deve essere di almeno 6 caratteri',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        // Verifica credenziali
        if (!Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => 'Le credenziali fornite non sono corrette.',
            ]);
        }

        $user = Auth::user();

        // Verifica se l'utente è attivo
        if (!$user->isAttivo()) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Il tuo account è stato disattivato. Contatta l\'amministratore.',
            ]);
        }

        // Aggiorna ultimo accesso
        $user->updateUltimoAccesso();

        // Rigenera la sessione per sicurezza
        $request->session()->regenerate();

        // Log dell'accesso
        activity()
            ->causedBy($user)
            ->log('Accesso effettuato da IP: ' . $request->ip());

        return redirect()->intended('/dashboard');
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
            'telefono' => 'nullable|string|max:20',
            'data_nascita' => 'nullable|date|before:today',
            'codice_fiscale' => 'nullable|string|size:16|unique:users',
            'indirizzo' => 'nullable|string|max:255',
            'citta' => 'nullable|string|max:100',
            'cap' => 'nullable|string|max:10',
            'provincia' => 'nullable|string|max:5',
        ], [
            'nome.required' => 'Il nome è obbligatorio',
            'cognome.required' => 'Il cognome è obbligatorio',
            'email.required' => 'L\'email è obbligatoria',
            'email.unique' => 'Questa email è già registrata',
            'password.required' => 'La password è obbligatoria',
            'password.min' => 'La password deve essere di almeno 8 caratteri',
            'password.confirmed' => 'Le password non coincidono',
            'codice_fiscale.size' => 'Il codice fiscale deve essere di 16 caratteri',
            'codice_fiscale.unique' => 'Questo codice fiscale è già registrato',
            'data_nascita.before' => 'La data di nascita deve essere nel passato',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except('password', 'password_confirmation'));
        }

        // Crea l'utente
        $user = User::create([
            'nome' => $request->nome,
            'cognome' => $request->cognome,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'telefono' => $request->telefono,
            'data_nascita' => $request->data_nascita,
            'codice_fiscale' => strtoupper($request->codice_fiscale),
            'indirizzo' => $request->indirizzo,
            'citta' => $request->citta,
            'cap' => $request->cap,
            'provincia' => strtoupper($request->provincia),
            'ruolo' => 'volontario', // Ruolo predefinito
            'attivo' => false, // Deve essere attivato da un admin
        ]);

        // Log della registrazione
        activity()
            ->causedBy($user)
            ->log('Nuovo utente registrato: ' . $user->nome_completo);

        return redirect()->route('login')->with('success', 
            'Registrazione completata! Il tuo account deve essere attivato da un amministratore prima di poter accedere.');
    }

    /**
     * Gestisce il logout
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        
        if ($user) {
            // Log del logout
            activity()
                ->causedBy($user)
                ->log('Logout effettuato');
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Logout effettuato con successo.');
    }

    /**
     * Aggiorna ultimo accesso (AJAX)
     */
    public function updateAccesso(Request $request)
    {
        if (Auth::check()) {
            Auth::user()->updateUltimoAccesso();
            return response()->json(['status' => 'success']);
        }
        
        return response()->json(['status' => 'error'], 401);
    }

    /**
     * Mostra il form per richiedere reset password
     */
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Mostra il profilo utente
     */
    public function showProfile()
    {
        return view('auth.profile', [
            'user' => Auth::user()
        ]);
    }

    /**
     * Aggiorna il profilo utente
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'cognome' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'telefono' => 'nullable|string|max:20',
            'indirizzo' => 'nullable|string|max:255',
            'citta' => 'nullable|string|max:100',
            'cap' => 'nullable|string|max:10',
            'provincia' => 'nullable|string|max:5',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Gestione upload avatar
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $avatarPath;
        }

        // Aggiorna i dati
        $user->update([
            'nome' => $request->nome,
            'cognome' => $request->cognome,
            'email' => $request->email,
            'telefono' => $request->telefono,
            'indirizzo' => $request->indirizzo,
            'citta' => $request->citta,
            'cap' => $request->cap,
            'provincia' => strtoupper($request->provincia),
        ]);

        return back()->with('success', 'Profilo aggiornato con successo!');
    }

    /**
     * Cambia password
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'La password attuale è obbligatoria',
            'password.required' => 'La nuova password è obbligatoria',
            'password.min' => 'La nuova password deve essere di almeno 8 caratteri',
            'password.confirmed' => 'Le password non coincidono',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $user = Auth::user();

        // Verifica password attuale
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'La password attuale non è corretta']);
        }

        // Aggiorna password
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Log cambio password
        activity()
            ->causedBy($user)
            ->log('Password cambiata');

        return back()->with('success', 'Password cambiata con successo!');
    }
}