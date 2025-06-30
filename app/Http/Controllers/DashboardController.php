<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Volontario;
use App\Models\Mezzo;
use App\Models\Ticket;
use App\Models\Magazzino;
use App\Models\Dpi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Dashboard principale del gestionale
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Statistiche generali
            $statistiche = $this->getStatisticheGenerali();
            
            // Quick actions per l'utente
            $quickActions = $this->getQuickActions($user);
            
            // Alerts semplici
            $alerts = $this->getAlerts();

            return view('dashboard.index', compact(
                'statistiche',
                'quickActions',
                'alerts'
            ));

        } catch (\Exception $e) {
            Log::error('Errore nel caricamento dashboard: ' . $e->getMessage());
            return view('dashboard.index')->with('error', 'Errore nel caricamento della dashboard');
        }
    }

    /**
     * Statistiche generali del sistema
     */
    private function getStatisticheGenerali()
    {
        try {
            return [
                'eventi' => [
                    'totali' => Evento::count(),
                    'futuri' => Evento::where('data_inizio', '>', now())->count(),
                ],
                'volontari' => [
                    'totali' => Volontario::count(),
                    'attivi' => Volontario::where('stato', 'attivo')->count(),
                ],
                'mezzi' => [
                    'totali' => Mezzo::count(),
                    'disponibili' => Mezzo::where('stato', 'disponibile')->count(),
                ],
                'tickets' => [
                    'totali' => Ticket::count(),
                    'aperti' => Ticket::whereIn('stato', ['aperto', 'in_lavorazione'])->count(),
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Errore nel calcolo statistiche: ' . $e->getMessage());
            return [
                'eventi' => ['totali' => 0, 'futuri' => 0],
                'volontari' => ['totali' => 0, 'attivi' => 0],
                'mezzi' => ['totali' => 0, 'disponibili' => 0],
                'tickets' => ['totali' => 0, 'aperti' => 0]
            ];
        }
    }

    /**
     * Quick actions personalizzate per l'utente
     */
    private function getQuickActions($user)
    {
        try {
            $actions = [];

            // Azioni base per tutti
            $actions[] = [
                'titolo' => 'Nuovo Evento',
                'descrizione' => 'Crea un nuovo evento formativo',
                'icona' => 'calendar-plus',
                'url' => route('eventi.create'),
                'colore' => 'primary'
            ];

            // Se può gestire volontari
            if ($user->hasPermission('volontari', 'create')) {
                $actions[] = [
                    'titolo' => 'Nuovo Volontario',
                    'descrizione' => 'Registra un nuovo volontario',
                    'icona' => 'user-plus',
                    'url' => route('volontari.create'),
                    'colore' => 'success'
                ];
            }

            // Se può gestire mezzi
            if ($user->hasPermission('mezzi', 'visualizza')) {
                $actions[] = [
                    'titolo' => 'Gestione Mezzi',
                    'descrizione' => 'Visualizza stato mezzi',
                    'icona' => 'truck',
                    'url' => route('mezzi.index'),
                    'colore' => 'warning'
                ];
            }

            $actions[] = [
                'titolo' => 'Nuovo Ticket',
                'descrizione' => 'Apri un ticket di supporto',
                'icona' => 'ticket-perforated',
                'url' => route('tickets.create'),
                'colore' => 'info'
            ];

            // Se è admin
            if ($user->isAdmin()) {
                $actions[] = [
                    'titolo' => 'Amministrazione',
                    'descrizione' => 'Pannello amministrativo',
                    'icona' => 'gear',
                    'url' => route('admin.index'),
                    'colore' => 'secondary'
                ];
            }

            return $actions;

        } catch (\Exception $e) {
            Log::error('Errore nel caricamento quick actions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Alerts semplici
     */
    private function getAlerts()
    {
        try {
            $alerts = [];

            // Scorte minime magazzino
            try {
                $scorteMinime = Magazzino::whereRaw('quantita_disponibile <= scorta_minima')
                                       ->where('scorta_minima', '>', 0)
                                       ->count();
                
                if ($scorteMinime > 0) {
                    $alerts[] = [
                        'tipo' => 'warning',
                        'titolo' => 'Scorte Minime',
                        'messaggio' => "$scorteMinime articoli hanno raggiunto la scorta minima"
                    ];
                }
            } catch (\Exception $e) {
                // Ignora errori se la tabella magazzino non esiste
            }

            // DPI in scadenza
            try {
                $dpiScadenza = Dpi::where('data_scadenza', '<=', now()->addDays(30))
                                 ->where('data_scadenza', '>=', now())
                                 ->count();
                
                if ($dpiScadenza > 0) {
                    $alerts[] = [
                        'tipo' => 'warning',
                        'titolo' => 'DPI in Scadenza',
                        'messaggio' => "$dpiScadenza DPI scadranno nei prossimi 30 giorni"
                    ];
                }
            } catch (\Exception $e) {
                // Ignora errori se la tabella dpi non esiste
            }

            return $alerts;

        } catch (\Exception $e) {
            Log::error('Errore nel caricamento alerts: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * API per statistiche dashboard
     */
    public function apiStats(Request $request)
    {
        try {
            return response()->json($this->getStatisticheGenerali());
        } catch (\Exception $e) {
            Log::error('Errore API stats dashboard: ' . $e->getMessage());
            return response()->json(['error' => 'Errore nel caricamento dati'], 500);
        }
    }

    /**
     * Sezione amministrazione (solo admin)
     */
    public function admin()
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Accesso negato');
        }

        return view('admin.index');
    }
}