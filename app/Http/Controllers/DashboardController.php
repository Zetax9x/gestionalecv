<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Volontario;
use App\Models\Mezzo;
use App\Models\Ticket;
use App\Models\Magazzino;
use App\Models\Dpi;
use App\Models\Notifica;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
            
            // Eventi prossimi
            $eventiProssimi = $this->getEventiProssimi();
            
            // Alerts e notifiche importanti
            $alerts = $this->getAlerts();
            
            // Attività recenti
            $attivitaRecenti = $this->getAttivitaRecenti();
            
            // Statistiche per grafici
            $grafici = $this->getDatiGrafici();
            
            // Quick actions per l'utente
            $quickActions = $this->getQuickActions($user);
            
            // Tickets urgenti
            $ticketsUrgenti = $this->getTicketsUrgenti();
            
            // Scadenze imminenti
            $scadenzeImminenti = $this->getScadenzeImminenti();

            return view('dashboard.index', compact(
                'statistiche',
                'eventiProssimi', 
                'alerts',
                'attivitaRecenti',
                'grafici',
                'quickActions',
                'ticketsUrgenti',
                'scadenzeImminenti'
            ));

        } catch (\Exception $e) {
            Log::error('Errore nel caricamento dashboard: ' . $e->getMessage());
            return view('dashboard.index')->with('error', 'Errore nel caricamento della dashboard');
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
            'colore' => 'blue'
        ];

        // Se può gestire volontari
        if ($user->hasPermission('volontari', 'create')) {
            $actions[] = [
                'titolo' => 'Nuovo Volontario',
                'descrizione' => 'Registra un nuovo volontario',
                'icona' => 'user-plus',
                'url' => route('volontari.create'),
                'colore' => 'green'
            ];
        }

        // Se può gestire mezzi
        if ($user->hasPermission('mezzi', 'visualizza')) {
            $actions[] = [
                'titolo' => 'Gestione Mezzi',
                'descrizione' => 'Visualizza stato mezzi',
                'icona' => 'truck',
                'url' => route('mezzi.index'),
                'colore' => 'orange'
            ];
        }

        $actions[] = [
            'titolo' => 'Nuovo Ticket',
            'descrizione' => 'Apri un ticket di supporto',
            'icona' => 'ticket',
            'url' => route('tickets.create'),
            'colore' => 'purple'
        ];

        // Se è admin
        if ($user->isAdmin()) {
            $actions[] = [
                'titolo' => 'Configurazione',
                'descrizione' => 'Impostazioni sistema',
                'icona' => 'settings',
                'url' => route('admin.index'),
                'colore' => 'gray'
            ];
        }

        return $actions;

    } catch (\Exception $e) {
        Log::error('Errore nel caricamento quick actions: ' . $e->getMessage());
        return [];
    }
}

    /**
     * Statistiche generali del sistema
     */
    private function getStatisticheGenerali()
    {
        try {
            $oggi = now();
            $inizioMese = $oggi->copy()->startOfMonth();
            $fineUltimMese = $inizioMese->copy()->subDay()->endOfMonth();
            $inizioUltimMese = $fineUltimMese->copy()->startOfMonth();

            // Eventi
            $eventiTotali = Evento::count();
            $eventiMese = Evento::whereBetween('data_inizio', [$inizioMese, $oggi])->count();
            $eventiMeseScorso = Evento::whereBetween('data_inizio', [$inizioUltimMese, $fineUltimMese])->count();
            $eventiTrend = $eventiMeseScorso > 0 ? (($eventiMese - $eventiMeseScorso) / $eventiMeseScorso) * 100 : 0;

            // Volontari
            $volontariAttivi = Volontario::where('stato', 'attivo')->count();
            $volontariTotali = Volontario::count();
            $nuoviVolontari = Volontario::whereBetween('created_at', [$inizioMese, $oggi])->count();

            // Mezzi
            $mezziDisponibili = Mezzo::where('stato', 'disponibile')->count();
            $mezziTotali = Mezzo::count();
            $mezziManutenzione = Mezzo::where('stato', 'in_manutenzione')->count();

            // Tickets
            $ticketsAperti = Ticket::whereIn('stato', ['aperto', 'in_lavorazione'])->count();
            $ticketsTotali = Ticket::count();
            $ticketsChiusiMese = Ticket::where('stato', 'chiuso')
                                     ->whereBetween('updated_at', [$inizioMese, $oggi])
                                     ->count();

            // Notifiche non lette
            $notificheNonLette = Auth::user()->notifiche()->whereNull('read_at')->count();

            return [
                'eventi' => [
                    'totali' => $eventiTotali,
                    'mese' => $eventiMese,
                    'trend' => round($eventiTrend, 1),
                    'programmati' => Evento::where('stato', 'programmato')->count(),
                    'in_corso' => Evento::where('stato', 'in_corso')->count(),
                    'completati' => Evento::where('stato', 'completato')->count()
                ],
                'volontari' => [
                    'attivi' => $volontariAttivi,
                    'totali' => $volontariTotali,
                    'nuovi_mese' => $nuoviVolontari,
                    'percentuale_attivi' => $volontariTotali > 0 ? round(($volontariAttivi / $volontariTotali) * 100, 1) : 0
                ],
                'mezzi' => [
                    'disponibili' => $mezziDisponibili,
                    'totali' => $mezziTotali,
                    'manutenzione' => $mezziManutenzione,
                    'percentuale_disponibili' => $mezziTotali > 0 ? round(($mezziDisponibili / $mezziTotali) * 100, 1) : 0
                ],
                'tickets' => [
                    'aperti' => $ticketsAperti,
                    'totali' => $ticketsTotali,
                    'chiusi_mese' => $ticketsChiusiMese,
                    'urgenti' => Ticket::where('priorita', 'urgente')->whereIn('stato', ['aperto', 'in_lavorazione'])->count()
                ],
                'notifiche' => [
                    'non_lette' => $notificheNonLette
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Errore nel calcolo statistiche generali: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Eventi prossimi (prossimi 30 giorni)
     */
    private function getEventiProssimi()
    {
        try {
            return Evento::with(['volontari', 'mezzi'])
                ->where('data_inizio', '>=', now())
                ->where('data_inizio', '<=', now()->addDays(30))
                ->where('stato', '!=', 'cancellato')
                ->orderBy('data_inizio', 'asc')
                ->limit(10)
                ->get()
                ->map(function($evento) {
                    return [
                        'id' => $evento->id,
                        'titolo' => $evento->titolo,
                        'data_inizio' => $evento->data_inizio,
                        'data_fine' => $evento->data_fine,
                        'luogo' => $evento->luogo,
                        'tipo_evento' => $evento->tipo_evento,
                        'stato' => $evento->stato,
                        'volontari_count' => $evento->volontari->count(),
                        'mezzi_count' => $evento->mezzi->count(),
                        'giorni_rimanenti' => now()->diffInDays(Carbon::parse($evento->data_inizio), false),
                        'url' => route('eventi.show', $evento->id)
                    ];
                });

        } catch (\Exception $e) {
            Log::error('Errore nel caricamento eventi prossimi: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Alerts e avvisi importanti
     */
    private function getAlerts()
    {
        try {
            $alerts = [];

            // Scorte minime magazzino
            $scorteMinime = Magazzino::whereRaw('quantita <= scorta_minima')
                                   ->where('scorta_minima', '>', 0)
                                   ->count();
            
            if ($scorteMinime > 0) {
                $alerts[] = [
                    'tipo' => 'warning',
                    'icona' => 'package-x',
                    'titolo' => 'Scorte Minime',
                    'messaggio' => "$scorteMinime articoli hanno raggiunto la scorta minima",
                    'url' => route('magazzino.scorte-minime'),
                    'priorita' => 'alta'
                ];
            }

            // DPI in scadenza (prossimi 30 giorni)
            $dpiScadenza = Dpi::where('data_scadenza', '<=', now()->addDays(30))
                             ->where('data_scadenza', '>=', now())
                             ->count();
            
            if ($dpiScadenza > 0) {
                $alerts[] = [
                    'tipo' => 'warning',
                    'icona' => 'shield-alert',
                    'titolo' => 'DPI in Scadenza',
                    'messaggio' => "$dpiScadenza DPI scadranno nei prossimi 30 giorni",
                    'url' => route('dpi.scadenze'),
                    'priorita' => 'alta'
                ];
            }

            // Mezzi con manutenzioni scadute
            $manutenzioniScadute = Mezzo::where('prossima_manutenzione', '<', now())
                                       ->where('stato', '!=', 'fuori_servizio')
                                       ->count();
            
            if ($manutenzioniScadute > 0) {
                $alerts[] = [
                    'tipo' => 'danger',
                    'icona' => 'truck',
                    'titolo' => 'Manutenzioni Scadute',
                    'messaggio' => "$manutenzioniScadute mezzi hanno manutenzioni scadute",
                    'url' => route('mezzi.scadenze'),
                    'priorita' => 'urgente'
                ];
            }

            // Documenti volontari in scadenza (prossimi 30 giorni)
            $documentiScadenza = Volontario::where('stato', 'attivo')
                ->where(function($query) {
                    $query->whereBetween('scadenza_patente', [now(), now()->addDays(30)])
                          ->orWhereBetween('scadenza_certificato_medico', [now(), now()->addDays(30)])
                          ->orWhereBetween('scadenza_formazione', [now(), now()->addDays(30)]);
                })
                ->count();

            if ($documentiScadenza > 0) {
                $alerts[] = [
                    'tipo' => 'warning',
                    'icona' => 'file-clock',
                    'titolo' => 'Documenti in Scadenza',
                    'messaggio' => "$documentiScadenza volontari hanno documenti in scadenza",
                    'url' => route('volontari.index', ['filter' => 'scadenze']),
                    'priorita' => 'normale'
                ];
            }

            // Tickets urgenti non assegnati
            $ticketsUrgentiNonAssegnati = Ticket::where('priorita', 'urgente')
                                                ->where('stato', 'aperto')
                                                ->whereNull('assigned_to')
                                                ->count();

            if ($ticketsUrgentiNonAssegnati > 0) {
                $alerts[] = [
                    'tipo' => 'danger',
                    'icona' => 'alert-triangle',
                    'titolo' => 'Tickets Urgenti',
                    'messaggio' => "$ticketsUrgentiNonAssegnati tickets urgenti non sono ancora assegnati",
                    'url' => route('tickets.index', ['priorita' => 'urgente', 'stato' => 'aperto']),
                    'priorita' => 'urgente'
                ];
            }

            // Ordina per priorità
            usort($alerts, function($a, $b) {
                $priorita = ['urgente' => 4, 'alta' => 3, 'normale' => 2, 'bassa' => 1];
                return ($priorita[$b['priorita']] ?? 0) - ($priorita[$a['priorita']] ?? 0);
            });

            return array_slice($alerts, 0, 5); // Massimo 5 alerts

        } catch (\Exception $e) {
            Log::error('Errore nel caricamento alerts: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Attività recenti del sistema
     */
    private function getAttivitaRecenti()
    {
        try {
            $attivita = [];

            // Ultimi eventi creati
            $ultimiEventi = Evento::with('createdBy')
                                 ->orderBy('created_at', 'desc')
                                 ->limit(5)
                                 ->get();

            foreach ($ultimiEventi as $evento) {
                $attivita[] = [
                    'tipo' => 'evento_creato',
                    'icona' => 'calendar-plus',
                    'titolo' => 'Nuovo evento creato',
                    'descrizione' => $evento->titolo,
                    'utente' => $evento->createdBy ? $evento->createdBy->name : 'Sistema',
                    'timestamp' => $evento->created_at,
                    'url' => route('eventi.show', $evento->id)
                ];
            }

            // Ultimi volontari registrati
            $ultimiVolontari = Volontario::orderBy('created_at', 'desc')
                                       ->limit(3)
                                       ->get();

            foreach ($ultimiVolontari as $volontario) {
                $attivita[] = [
                    'tipo' => 'volontario_registrato',
                    'icona' => 'user-plus',
                    'titolo' => 'Nuovo volontario',
                    'descrizione' => "{$volontario->nome} {$volontario->cognome}",
                    'utente' => 'Sistema',
                    'timestamp' => $volontario->created_at,
                    'url' => route('volontari.show', $volontario->id)
                ];
            }

            // Ultimi tickets chiusi
            $ultimiTicketsChiusi = Ticket::with('assignedTo')
                                        ->where('stato', 'chiuso')
                                        ->orderBy('updated_at', 'desc')
                                        ->limit(3)
                                        ->get();

            foreach ($ultimiTicketsChiusi as $ticket) {
                $attivita[] = [
                    'tipo' => 'ticket_chiuso',
                    'icona' => 'check-circle',
                    'titolo' => 'Ticket risolto',
                    'descrizione' => $ticket->titolo,
                    'utente' => $ticket->assignedTo ? $ticket->assignedTo->name : 'Non assegnato',
                    'timestamp' => $ticket->updated_at,
                    'url' => route('tickets.show', $ticket->id)
                ];
            }

            // Ordina per timestamp
            usort($attivita, function($a, $b) {
                return $b['timestamp']->getTimestamp() - $a['timestamp']->getTimestamp();
            });

            return array_slice($attivita, 0, 10);

        } catch (\Exception $e) {
            Log::error('Errore nel caricamento attività recenti: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Dati per i grafici della dashboard
     */
    private function getDatiGrafici()
    {
        try {
            // Grafico eventi ultimi 12 mesi
            $eventiUltimi12Mesi = [];
            for ($i = 11; $i >= 0; $i--) {
                $mese = now()->subMonths($i);
                $count = Evento::whereYear('data_inizio', $mese->year)
                              ->whereMonth('data_inizio', $mese->month)
                              ->count();
                
                $eventiUltimi12Mesi[] = [
                    'mese' => $mese->format('M Y'),
                    'eventi' => $count
                ];
            }

            // Distribuzione tipi evento
            $tipiEvento = Evento::selectRaw('tipo_evento, COUNT(*) as count')
                               ->groupBy('tipo_evento')
                               ->pluck('count', 'tipo_evento')
                               ->toArray();

            // Stati tickets
            $statiTickets = Ticket::selectRaw('stato, COUNT(*) as count')
                                 ->groupBy('stato')
                                 ->pluck('count', 'stato')
                                 ->toArray();

            // Utilizzo mezzi (ultimi 30 giorni)
            $utilizzoMezzi = DB::table('evento_mezzo')
                ->join('eventi', 'evento_mezzo.evento_id', '=', 'eventi.id')
                ->join('mezzi', 'evento_mezzo.mezzo_id', '=', 'mezzi.id')
                ->where('eventi.data_inizio', '>=', now()->subDays(30))
                ->select('mezzi.targa', DB::raw('COUNT(*) as utilizzi'))
                ->groupBy('mezzi.id', 'mezzi.targa')
                ->orderBy('utilizzi', 'desc')
                ->limit(10)
                ->get()
                ->toArray();

            return [
                'eventi_mesi' => $eventiUltimi12Mesi,
                'tipi_evento' => $tipiEvento,
                'stati_tickets' => $statiTickets,
                'utilizzo_mezzi' => $utilizzoMezzi
            ];

        } catch (\Exception $e) {
            Log::error('Errore nel caricamento dati grafici: ' . $e->getMessage());
            return [];
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
                'colore' => 'blue'
            ];

            // Se può gestire volontari
            if ($user->hasPermission('volontari.create')) {
                $actions[] = [
                    'titolo' => 'Nuovo Volontario',
                    'descrizione' => 'Registra un nuovo volontario',
                    'icona' => 'user-plus',
                    'url' => route('volontari.create'),
                    'colore' => 'green'
                ];
            }

            // Se può gestire mezzi
            if ($user->hasPermission('mezzi.view')) {
                $actions[] = [
                    'titolo' => 'Gestione Mezzi',
                    'descrizione' => 'Visualizza stato mezzi',
                    'icona' => 'truck',
                    'url' => route('mezzi.index'),
                    'colore' => 'orange'
                ];
            }

            $actions[] = [
                'titolo' => 'Nuovo Ticket',
                'descrizione' => 'Apri un ticket di supporto',
                'icona' => 'ticket',
                'url' => route('tickets.create'),
                'colore' => 'purple'
            ];

            // Se è admin
            if ($user->hasPermission('admin.access')) {
                $actions[] = [
                    'titolo' => 'Configurazione',
                    'descrizione' => 'Impostazioni sistema',
                    'icona' => 'settings',
                    'url' => route('configurazione.index'),
                    'colore' => 'gray'
                ];
            }

            return $actions;

        } catch (\Exception $e) {
            Log::error('Errore nel caricamento quick actions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Tickets urgenti per l'utente corrente
     */
    private function getTicketsUrgenti()
    {
        try {
            return Ticket::with('createdBy')
                ->where(function($query) {
                    $query->where('assigned_to', Auth::id())
                          ->orWhere('created_by', Auth::id());
                })
                ->where('priorita', 'urgente')
                ->whereIn('stato', ['aperto', 'in_lavorazione'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

        } catch (\Exception $e) {
            Log::error('Errore nel caricamento tickets urgenti: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Scadenze imminenti (prossimi 7 giorni)
     */
    private function getScadenzeImminenti()
    {
        try {
            $scadenze = [];
            $prossimi7Giorni = now()->addDays(7);

            // DPI in scadenza
            $dpiScadenza = Dpi::where('data_scadenza', '<=', $prossimi7Giorni)
                             ->where('data_scadenza', '>=', now())
                             ->orderBy('data_scadenza')
                             ->limit(5)
                             ->get();

            foreach ($dpiScadenza as $dpi) {
                $scadenze[] = [
                    'tipo' => 'DPI',
                    'nome' => $dpi->nome,
                    'scadenza' => $dpi->data_scadenza,
                    'giorni_rimanenti' => now()->diffInDays($dpi->data_scadenza, false),
                    'url' => route('dpi.show', $dpi->id),
                    'priorita' => now()->diffInDays($dpi->data_scadenza, false) <= 3 ? 'alta' : 'normale'
                ];
            }

            // Manutenzioni mezzi
            $manutenzioni = Mezzo::where('prossima_manutenzione', '<=', $prossimi7Giorni)
                                ->where('prossima_manutenzione', '>=', now())
                                ->orderBy('prossima_manutenzione')
                                ->limit(5)
                                ->get();

            foreach ($manutenzioni as $mezzo) {
                $scadenze[] = [
                    'tipo' => 'Manutenzione',
                    'nome' => "Mezzo {$mezzo->targa}",
                    'scadenza' => $mezzo->prossima_manutenzione,
                    'giorni_rimanenti' => now()->diffInDays($mezzo->prossima_manutenzione, false),
                    'url' => route('mezzi.show', $mezzo->id),
                    'priorita' => now()->diffInDays($mezzo->prossima_manutenzione, false) <= 1 ? 'alta' : 'normale'
                ];
            }

            // Ordina per scadenza
            usort($scadenze, function($a, $b) {
                return $a['scadenza']->getTimestamp() - $b['scadenza']->getTimestamp();
            });

            return array_slice($scadenze, 0, 8);

        } catch (\Exception $e) {
            Log::error('Errore nel caricamento scadenze imminenti: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * API per aggiornamento dati dashboard
     */
    public function apiStats(Request $request)
    {
        try {
            $type = $request->get('type', 'general');

            switch ($type) {
                case 'general':
                    return response()->json($this->getStatisticheGenerali());
                
                case 'events':
                    return response()->json($this->getEventiProssimi());
                
                case 'alerts':
                    return response()->json($this->getAlerts());
                
                case 'charts':
                    return response()->json($this->getDatiGrafici());
                
                default:
                    return response()->json(['error' => 'Tipo non valido'], 400);
            }

        } catch (\Exception $e) {
            Log::error('Errore API stats dashboard: ' . $e->getMessage());
            return response()->json(['error' => 'Errore nel caricamento dati'], 500);
        }
    }
}
