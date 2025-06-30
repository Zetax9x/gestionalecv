<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\{
    AuthController,
    EventiController,
    VolontariController,
    MezziController,
    TicketsController,
    MagazzinoController,
    DpiController,
    NotificheController,
    DashboardController,
    PermissionController
};

/*
|--------------------------------------------------------------------------
| ROUTES DI AUTENTICAZIONE
|--------------------------------------------------------------------------
*/

// Route pubbliche (guest)
Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    
    // Registrazione
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    
    // Password reset
    Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
});

// Route protette (auth)
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Aggiorna ultimo accesso (AJAX)
    Route::post('/user/update-accesso', [AuthController::class, 'updateAccesso'])->name('user.update-accesso');
    
    // Profilo utente
    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile.edit');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AuthController::class, 'changePassword'])->name('profile.password');
});

/*
|--------------------------------------------------------------------------
| REDIRECT ROUTES
|--------------------------------------------------------------------------
*/

// Redirect root
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');

// Redirect home
Route::get('/home', function () {
    return redirect()->route('dashboard');
});

/*
|--------------------------------------------------------------------------
| ROUTES AUTENTICATE DEL GESTIONALE
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    
    // Dashboard principale
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    /*
    |--------------------------------------------------------------------------
    | EVENTI - Gestione Eventi Formativi
    |--------------------------------------------------------------------------
    */
    Route::prefix('eventi')->name('eventi.')->group(function () {
        Route::get('/', [EventiController::class, 'index'])->name('index');
        Route::get('/create', [EventiController::class, 'create'])->name('create');
        Route::post('/', [EventiController::class, 'store'])->name('store');
        Route::get('/{evento}', [EventiController::class, 'show'])->name('show');
        Route::get('/{evento}/edit', [EventiController::class, 'edit'])->name('edit');
        Route::put('/{evento}', [EventiController::class, 'update'])->name('update');
        Route::delete('/{evento}', [EventiController::class, 'destroy'])->name('destroy');
        
        // Routes aggiuntive per eventi
        Route::patch('/{evento}/status', [EventiController::class, 'changeStatus'])->name('change-status');
        Route::post('/{evento}/duplicate', [EventiController::class, 'duplicate'])->name('duplicate');
        Route::post('/{evento}/partecipa', [EventiController::class, 'partecipa'])->name('partecipa');
        Route::delete('/{evento}/partecipa', [EventiController::class, 'rimuoviPartecipazione'])->name('rimuovi-partecipazione');
        
        // Export e Reports
        Route::get('/export/pdf', [EventiController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/export/excel', [EventiController::class, 'exportExcel'])->name('export.excel');
        Route::get('/calendario', [EventiController::class, 'calendario'])->name('calendario');
    });

    /*
    |--------------------------------------------------------------------------
    | VOLONTARI - Gestione Volontari
    |--------------------------------------------------------------------------
    */
    Route::prefix('volontari')->name('volontari.')->group(function () {
        Route::get('/', [VolontariController::class, 'index'])->name('index');
        Route::get('/create', [VolontariController::class, 'create'])->name('create');
        Route::post('/', [VolontariController::class, 'store'])->name('store');
        Route::get('/{volontario}', [VolontariController::class, 'show'])->name('show');
        Route::get('/{volontario}/edit', [VolontariController::class, 'edit'])->name('edit');
        Route::put('/{volontario}', [VolontariController::class, 'update'])->name('update');
        Route::delete('/{volontario}', [VolontariController::class, 'destroy'])->name('destroy');
        
        // Routes aggiuntive per volontari
        Route::patch('/{volontario}/status', [VolontariController::class, 'changeStatus'])->name('change-status');
        Route::get('/{volontario}/eventi', [VolontariController::class, 'eventi'])->name('eventi');
        Route::get('/{volontario}/disponibilita', [VolontariController::class, 'disponibilita'])->name('disponibilita');
        Route::post('/{volontario}/certificazioni', [VolontariController::class, 'addCertificazione'])->name('add-certificazione');
        Route::get('/{volontario}/documenti', [VolontariController::class, 'documenti'])->name('documenti');
        Route::post('/{volontario}/documenti', [VolontariController::class, 'uploadDocumento'])->name('documenti.upload');
        
        // Import/Export
        Route::get('/import/template', [VolontariController::class, 'downloadTemplate'])->name('import.template');
        Route::post('/import', [VolontariController::class, 'import'])->name('import');
        Route::get('/export/excel', [VolontariController::class, 'exportExcel'])->name('export.excel');
    });

    /*
    |--------------------------------------------------------------------------
    | MEZZI - Gestione Mezzi di Trasporto
    |--------------------------------------------------------------------------
    */
    Route::prefix('mezzi')->name('mezzi.')->group(function () {
        Route::get('/', [MezziController::class, 'index'])->name('index');
        Route::get('/create', [MezziController::class, 'create'])->name('create');
        Route::post('/', [MezziController::class, 'store'])->name('store');
        Route::get('/{mezzo}', [MezziController::class, 'show'])->name('show');
        Route::get('/{mezzo}/edit', [MezziController::class, 'edit'])->name('edit');
        Route::put('/{mezzo}', [MezziController::class, 'update'])->name('update');
        Route::delete('/{mezzo}', [MezziController::class, 'destroy'])->name('destroy');
        
        // Manutenzioni
        Route::get('/{mezzo}/manutenzioni', [MezziController::class, 'manutenzioni'])->name('manutenzioni');
        Route::post('/{mezzo}/manutenzioni', [MezziController::class, 'addManutenzione'])->name('add-manutenzione');
        Route::patch('/manutenzioni/{manutenzione}', [MezziController::class, 'updateManutenzione'])->name('update-manutenzione');
        
        // Checklist
        Route::get('/{mezzo}/checklist', [MezziController::class, 'checklist'])->name('checklist');
        Route::post('/{mezzo}/checklist', [MezziController::class, 'saveChecklist'])->name('checklist.store');
        
        // Controlli e scadenze
        Route::get('/scadenze', [MezziController::class, 'scadenze'])->name('scadenze');
        Route::patch('/{mezzo}/status', [MezziController::class, 'changeStatus'])->name('change-status');
        
        // Utilizzo e statistiche
        Route::get('/{mezzo}/utilizzo', [MezziController::class, 'utilizzo'])->name('utilizzo');
        Route::get('/statistiche', [MezziController::class, 'statistiche'])->name('statistiche');
    });

    /*
    |--------------------------------------------------------------------------
    | TICKETS - Sistema Ticketing
    |--------------------------------------------------------------------------
    */
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [TicketsController::class, 'index'])->name('index');
        Route::get('/create', [TicketsController::class, 'create'])->name('create');
        Route::post('/', [TicketsController::class, 'store'])->name('store');
        Route::get('/{ticket}', [TicketsController::class, 'show'])->name('show');
        Route::get('/{ticket}/edit', [TicketsController::class, 'edit'])->name('edit');
        Route::put('/{ticket}', [TicketsController::class, 'update'])->name('update');
        Route::delete('/{ticket}', [TicketsController::class, 'destroy'])->name('destroy');
        
        // Gestione stato ticket
        Route::patch('/{ticket}/status', [TicketsController::class, 'changeStatus'])->name('change-status');
        Route::patch('/{ticket}/assign', [TicketsController::class, 'assign'])->name('assign');
        Route::post('/{ticket}/assign', [TicketsController::class, 'assign'])->name('assign.post');
        Route::post('/{ticket}/close', [TicketsController::class, 'close'])->name('close');
        Route::post('/{ticket}/comment', [TicketsController::class, 'addComment'])->name('add-comment');
        Route::post('/{ticket}/allegati', [TicketsController::class, 'uploadAllegato'])->name('allegati.upload');
        
        // Filtri e viste speciali
        Route::get('/my/assigned', [TicketsController::class, 'myAssigned'])->name('my-assigned');
        Route::get('/my/created', [TicketsController::class, 'myCreated'])->name('my-created');
        Route::get('/category/{category}', [TicketsController::class, 'byCategory'])->name('by-category');
        Route::get('/priority/{priority}', [TicketsController::class, 'byPriority'])->name('by-priority');
    });

    /*
    |--------------------------------------------------------------------------
    | MAGAZZINO - Gestione Inventario
    |--------------------------------------------------------------------------
    */
    Route::prefix('magazzino')->name('magazzino.')->group(function () {
        Route::get('/', [MagazzinoController::class, 'index'])->name('index');
        Route::get('/create', [MagazzinoController::class, 'create'])->name('create');
        Route::post('/', [MagazzinoController::class, 'store'])->name('store');
        Route::get('/{item}', [MagazzinoController::class, 'show'])->name('show');
        Route::get('/{item}/edit', [MagazzinoController::class, 'edit'])->name('edit');
        Route::put('/{item}', [MagazzinoController::class, 'update'])->name('update');
        Route::delete('/{item}', [MagazzinoController::class, 'destroy'])->name('destroy');
        
        // Movimenti magazzino
        Route::post('/{item}/carico', [MagazzinoController::class, 'carico'])->name('carico');
        Route::post('/{item}/scarico', [MagazzinoController::class, 'scarico'])->name('scarico');
        Route::post('/{item}/movimento', [MagazzinoController::class, 'addMovimento'])->name('movimento');
        Route::get('/{item}/movimenti', [MagazzinoController::class, 'movimenti'])->name('movimenti');
        
        // Inventari e controlli
        Route::get('/inventario/nuovo', [MagazzinoController::class, 'nuovoInventario'])->name('nuovo-inventario');
        Route::post('/inventario', [MagazzinoController::class, 'salvaInventario'])->name('salva-inventario');
        Route::get('/scorte-minime', [MagazzinoController::class, 'scorteMinime'])->name('scorte-minime');
        Route::get('/scadenze', [MagazzinoController::class, 'scadenze'])->name('scadenze');
        
        // Reports
        Route::get('/reports/giacenze', [MagazzinoController::class, 'reportGiacenze'])->name('report-giacenze');
        Route::get('/report/inventario', [MagazzinoController::class, 'reportInventario'])->name('report.inventario');
        Route::get('/reports/movimenti', [MagazzinoController::class, 'reportMovimenti'])->name('report-movimenti');
    });

    /*
    |--------------------------------------------------------------------------
    | DPI - Dispositivi di Protezione Individuale
    |--------------------------------------------------------------------------
    */
    Route::prefix('dpi')->name('dpi.')->group(function () {
        Route::get('/', [DpiController::class, 'index'])->name('index');
        Route::get('/create', [DpiController::class, 'create'])->name('create');
        Route::post('/', [DpiController::class, 'store'])->name('store');
        Route::get('/{dpi}', [DpiController::class, 'show'])->name('show');
        Route::get('/{dpi}/edit', [DpiController::class, 'edit'])->name('edit');
        Route::put('/{dpi}', [DpiController::class, 'update'])->name('update');
        Route::delete('/{dpi}', [DpiController::class, 'destroy'])->name('destroy');
        
        // Assegnazioni DPI
        Route::get('/{dpi}/assegnazioni', [DpiController::class, 'assegnazioni'])->name('assegnazioni');
        Route::post('/{dpi}/assegna', [DpiController::class, 'assegna'])->name('assegna');
        Route::post('/{dpi}/ritira', [DpiController::class, 'ritira'])->name('ritira');
        Route::patch('/assegnazioni/{assegnazione}/restituisci', [DpiController::class, 'restituisci'])->name('restituisci');
        
        // Controlli e manutenzioni
        Route::post('/{dpi}/controllo', [DpiController::class, 'addControllo'])->name('add-controllo');
        Route::get('/scadenze', [DpiController::class, 'scadenze'])->name('scadenze');
        Route::get('/controlli-periodici', [DpiController::class, 'controlliPeriodici'])->name('controlli-periodici');
        
        // Reports DPI
        Route::get('/reports/assegnazioni', [DpiController::class, 'reportAssegnazioni'])->name('report-assegnazioni');
        Route::get('/reports/scadenze', [DpiController::class, 'reportScadenze'])->name('report-scadenze');
    });

    /*
    |--------------------------------------------------------------------------
    | NOTIFICHE - Centro Notifiche
    |--------------------------------------------------------------------------
    */
    Route::prefix('notifiche')->name('notifiche.')->group(function () {
        Route::get('/', [NotificheController::class, 'index'])->name('index');
        Route::get('/create', [NotificheController::class, 'create'])->name('create');
        Route::post('/', [NotificheController::class, 'store'])->name('store');
        Route::get('/{notifica}', [NotificheController::class, 'show'])->name('show');
        Route::patch('/{notifica}/read', [NotificheController::class, 'markAsRead'])->name('mark-read');
        Route::post('/{notifica}/mark-read', [NotificheController::class, 'markAsRead'])->name('mark-read.post');
        Route::delete('/{notifica}', [NotificheController::class, 'destroy'])->name('destroy');
        
        // Azioni multiple
        Route::patch('/mark-all-read', [NotificheController::class, 'markAllRead'])->name('mark-all-read');
        Route::post('/mark-all-read', [NotificheController::class, 'markAllAsRead'])->name('mark-all-read.post');
        Route::delete('/clear-read', [NotificheController::class, 'clearRead'])->name('clear-read');
        
        // API per notifiche real-time
        Route::get('/api/unread-count', [NotificheController::class, 'unreadCount'])->name('api.unread-count');
        Route::get('/api/recent', [NotificheController::class, 'recent'])->name('api.recent');
    });

    /*
    |--------------------------------------------------------------------------
    | AMMINISTRAZIONE - Gestione Permessi e Sistema
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->middleware('can:admin')->group(function () {
        Route::get('/', [DashboardController::class, 'admin'])->name('index');
        
        // Gestione permessi ACL
       Route::prefix('permissions')->name('permissions.')->group(function () {
    Route::get('/', [PermissionController::class, 'index'])->name('index');
    Route::put('/', [PermissionController::class, 'update'])->name('update');
});   
        // Gestione utenti
        Route::get('/utenti', [DashboardController::class, 'utenti'])->name('utenti');
        Route::get('/utenti/{user}/edit', [DashboardController::class, 'editUtente'])->name('utenti.edit');
        Route::put('/utenti/{user}', [DashboardController::class, 'updateUtente'])->name('utenti.update');
        Route::patch('/utenti/{user}/toggle-status', [DashboardController::class, 'toggleUserStatus'])->name('utenti.toggle-status');
        
        // Logs di sistema
        Route::get('/logs', [DashboardController::class, 'logs'])->name('logs');
        Route::get('/logs/{file}', [DashboardController::class, 'showLog'])->name('logs.show');
        Route::delete('/logs/{file}', [DashboardController::class, 'deleteLog'])->name('logs.delete');
        
        // Statistiche sistema
        Route::get('/statistiche', [DashboardController::class, 'statistiche'])->name('statistiche');
        
        // Impostazioni
        Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
        Route::put('/settings', [DashboardController::class, 'updateSettings'])->name('settings.update');
    });

    /*
    |--------------------------------------------------------------------------
    | API ROUTES - Per chiamate AJAX
    |--------------------------------------------------------------------------
    */
    Route::prefix('api')->name('api.')->group(function () {
        
        // Volontari disponibili per evento
        Route::get('/volontari/disponibili', [VolontariController::class, 'apiDisponibili'])->name('volontari.disponibili');
        
        // Mezzi disponibili per periodo
        Route::get('/mezzi/disponibili', [MezziController::class, 'apiDisponibili'])->name('mezzi.disponibili');
        
        // Ricerca rapida
        Route::get('/search', function(Request $request) {
            $query = $request->get('q');
            $results = [];
            
            if (strlen($query) >= 3) {
                // Cerca in volontari
                $volontari = App\Models\Volontario::where('nome', 'like', "%{$query}%")
                    ->orWhere('cognome', 'like', "%{$query}%")
                    ->limit(5)->get(['id', 'nome', 'cognome']);
                
                foreach ($volontari as $vol) {
                    $results[] = [
                        'type' => 'volontario',
                        'id' => $vol->id,
                        'text' => "{$vol->cognome} {$vol->nome}",
                        'url' => route('volontari.show', $vol->id)
                    ];
                }
                
                // Cerca in eventi
                $eventi = App\Models\Evento::where('titolo', 'like', "%{$query}%")
                    ->limit(5)->get(['id', 'titolo', 'data_inizio']);
                
                foreach ($eventi as $evento) {
                    $results[] = [
                        'type' => 'evento',
                        'id' => $evento->id,
                        'text' => $evento->titolo,
                        'url' => route('eventi.show', $evento->id)
                    ];
                }
            }
            
            return response()->json($results);
        })->name('search');
        
        // Statistiche dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'apiStats'])->name('dashboard.stats');
        
        // Check disponibilità in tempo reale
        Route::post('/check-availability', function(Request $request) {
            $data = $request->validate([
                'type' => 'required|in:volontario,mezzo',
                'id' => 'required|integer',
                'data_inizio' => 'required|date',
                'data_fine' => 'required|date|after_or_equal:data_inizio',
                'evento_id' => 'nullable|integer'
            ]);
            
            $available = true;
            $conflicts = [];
            
            if ($data['type'] === 'volontario') {
                $conflicts = App\Models\Evento::whereHas('volontari', function($q) use ($data) {
                    $q->where('volontario_id', $data['id']);
                })
                ->where('stato', '!=', 'cancellato')
                ->where('id', '!=', $data['evento_id'] ?? 0)
                ->where(function($q) use ($data) {
                    $q->whereBetween('data_inizio', [$data['data_inizio'], $data['data_fine']])
                      ->orWhereBetween('data_fine', [$data['data_inizio'], $data['data_fine']])
                      ->orWhere(function($subQ) use ($data) {
                          $subQ->where('data_inizio', '<=', $data['data_inizio'])
                               ->where('data_fine', '>=', $data['data_fine']);
                      });
                })->get(['id', 'titolo', 'data_inizio', 'data_fine']);
            }
            
            $available = $conflicts->isEmpty();
            
            return response()->json([
                'available' => $available,
                'conflicts' => $conflicts
            ]);
        })->name('check-availability');
    });

});

/*
|--------------------------------------------------------------------------
| FALLBACK ROUTE
|--------------------------------------------------------------------------
*/
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});

/*
|--------------------------------------------------------------------------
| DEVELOPMENT ROUTES (solo in ambiente di sviluppo)
|--------------------------------------------------------------------------
*/
if (app()->environment('local')) {
    Route::get('/test/email', function() {
        return new App\Mail\EventoCreato(App\Models\Evento::first());
    });
    
    Route::get('/test/notification', function() {
        $user = Auth::user();
        $user->notify(new App\Notifications\EventoAssegnato(App\Models\Evento::first()));
        return 'Notifica inviata!';
    });
}