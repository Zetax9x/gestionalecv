<?php

namespace App\Http\Controllers;

use App\Models\Notifica;
use App\Models\User;
use App\Models\Volontario;
use App\Models\Evento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotificheController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of notifiche per l'utente corrente
     */
    public function index(Request $request)
    {
        try {
            $query = Auth::user()->notifiche()->orderBy('created_at', 'desc');

            // Filtri
            if ($request->filled('tipo')) {
                $query->where('tipo', $request->tipo);
            }

            if ($request->filled('stato')) {
                if ($request->stato === 'lette') {
                    $query->whereNotNull('read_at');
                } elseif ($request->stato === 'non_lette') {
                    $query->whereNull('read_at');
                }
            }

            if ($request->filled('priorita')) {
                $query->where('priorita', $request->priorita);
            }

            // Filtro per periodo
            if ($request->filled('data_da')) {
                $query->where('created_at', '>=', $request->data_da);
            }
            if ($request->filled('data_a')) {
                $query->where('created_at', '<=', $request->data_a . ' 23:59:59');
            }

            $notifiche = $query->paginate(20)->withQueryString();

            // Statistiche
            $stats = [
                'totali' => Auth::user()->notifiche()->count(),
                'non_lette' => Auth::user()->notifiche()->whereNull('read_at')->count(),
                'oggi' => Auth::user()->notifiche()->whereDate('created_at', today())->count(),
                'questa_settimana' => Auth::user()->notifiche()
                    ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                    ->count()
            ];

            // Tipi di notifiche per filtro
            $tipi_notifiche = [
                'evento_assegnato' => 'Evento Assegnato',
                'evento_modificato' => 'Evento Modificato',
                'evento_cancellato' => 'Evento Cancellato',
                'scadenza_documento' => 'Scadenza Documento',
                'scadenza_dpi' => 'Scadenza DPI',
                'manutenzione_mezzo' => 'Manutenzione Mezzo',
                'ticket_assegnato' => 'Ticket Assegnato',
                'ticket_aggiornato' => 'Ticket Aggiornato',
                'scorte_minime' => 'Scorte Minime',
                'sistema' => 'Sistema',
                'altro' => 'Altro'
            ];

            return view('notifiche.index', compact('notifiche', 'stats', 'tipi_notifiche'));

        } catch (\Exception $e) {
            Log::error('Errore nel caricamento notifiche: ' . $e->getMessage());
            return back()->with('error', 'Errore nel caricamento delle notifiche');
        }
    }

    /**
     * Show the form for creating a new notifica
     */
    public function create()
    {
        try {
            // Solo admin e responsabili possono creare notifiche
            if (!Auth::user()->hasPermission('notifiche.create')) {
                abort(403, 'Non hai i permessi per creare notifiche');
            }

            $utenti = User::where('id', '!=', Auth::id())
                         ->orderBy('name')
                         ->get();

            $volontari = Volontario::where('stato', 'attivo')
                                 ->orderBy('cognome')
                                 ->get();

            $tipi_notifiche = [
                'evento_assegnato' => 'Evento Assegnato',
                'evento_modificato' => 'Evento Modificato',
                'scadenza_documento' => 'Scadenza Documento',
                'manutenzione_mezzo' => 'Manutenzione Mezzo',
                'ticket_assegnato' => 'Ticket Assegnato',
                'scorte_minime' => 'Scorte Minime',
                'sistema' => 'Sistema',
                'comunicazione' => 'Comunicazione',
                'altro' => 'Altro'
            ];

            $priorita_levels = [
                'bassa' => 'Bassa',
                'normale' => 'Normale',
                'alta' => 'Alta',
                'urgente' => 'Urgente'
            ];

            return view('notifiche.create', compact('utenti', 'volontari', 'tipi_notifiche', 'priorita_levels'));

        } catch (\Exception $e) {
            Log::error('Errore nel caricamento form notifica: ' . $e->getMessage());
            return back()->with('error', 'Errore nel caricamento del modulo');
        }
    }

    /**
     * Store a newly created notifica
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'destinatari' => 'required|array|min:1',
            'destinatari.*' => 'exists:users,id',
            'tipo' => 'required|string|max:50',
            'titolo' => 'required|string|max:255',
            'messaggio' => 'required|string',
            'priorita' => 'required|in:bassa,normale,alta,urgente',
            'url_azione' => 'nullable|url|max:500',
            'testo_azione' => 'nullable|string|max:100',
            'scade_il' => 'nullable|date|after:today',
            'invia_email' => 'boolean',
            'invia_push' => 'boolean'
        ]);

        try {
            DB::beginTransaction();

            $notificheCreate = [];

            foreach ($validated['destinatari'] as $userId) {
                $notifica = Notifica::create([
                    'user_id' => $userId,
                    'tipo' => $validated['tipo'],
                    'titolo' => $validated['titolo'],
                    'messaggio' => $validated['messaggio'],
                    'priorita' => $validated['priorita'],
                    'url_azione' => $validated['url_azione'],
                    'testo_azione' => $validated['testo_azione'],
                    'scade_il' => $validated['scade_il'],
                    'metadati' => json_encode([
                        'created_by' => Auth::id(),
                        'created_by_name' => Auth::user()->name,
                        'invia_email' => $validated['invia_email'] ?? false,
                        'invia_push' => $validated['invia_push'] ?? false
                    ])
                ]);

                $notificheCreate[] = $notifica;

                // Invia email se richiesto
                if ($validated['invia_email'] ?? false) {
                    $user = User::find($userId);
                    // Qui andrà l'invio email
                    // Mail::to($user)->send(new NotificaEmail($notifica));
                }

                // Invia notifica push se richiesta
                if ($validated['invia_push'] ?? false) {
                    // Qui andrà l'invio push notification
                    // $this->sendPushNotification($notifica);
                }
            }

            DB::commit();

            Log::info('Notifiche create in massa', [
                'count' => count($notificheCreate),
                'tipo' => $validated['tipo'],
                'created_by' => Auth::id()
            ]);

            return redirect()->route('notifiche.index')
                           ->with('success', 'Notifiche inviate con successo a ' . count($notificheCreate) . ' utenti');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore nella creazione notifiche: ' . $e->getMessage());
            return back()->withInput()
                        ->with('error', 'Errore nell\'invio delle notifiche: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified notifica
     */
    public function show(Notifica $notifica)
    {
        try {
            // Verifica che l'utente possa vedere questa notifica
            if ($notifica->user_id !== Auth::id() && !Auth::user()->hasPermission('notifiche.view_all')) {
                abort(403, 'Non autorizzato a visualizzare questa notifica');
            }

            // Marca come letta se non lo è già
            if (!$notifica->read_at && $notifica->user_id === Auth::id()) {
                $notifica->update(['read_at' => now()]);
            }

            return view('notifiche.show', compact('notifica'));

        } catch (\Exception $e) {
            Log::error('Errore nel caricamento notifica: ' . $e->getMessage());
            return back()->with('error', 'Errore nel caricamento della notifica');
        }
    }

    /**
     * Marca una notifica come letta
     */
    public function markAsRead(Notifica $notifica)
    {
        try {
            // Verifica che l'utente possa modificare questa notifica
            if ($notifica->user_id !== Auth::id()) {
                return response()->json(['error' => 'Non autorizzato'], 403);
            }

            if (!$notifica->read_at) {
                $notifica->update(['read_at' => now()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notifica marcata come letta'
            ]);

        } catch (\Exception $e) {
            Log::error('Errore nel marcare notifica come letta: ' . $e->getMessage());
            return response()->json(['error' => 'Errore nell\'operazione'], 500);
        }
    }

    /**
     * Marca tutte le notifiche come lette
     */
    public function markAllRead()
    {
        try {
            $updated = Auth::user()
                          ->notifiche()
                          ->whereNull('read_at')
                          ->update(['read_at' => now()]);

            Log::info("Marcate come lette $updated notifiche", [
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Marcate come lette $updated notifiche",
                'count' => $updated
            ]);

        } catch (\Exception $e) {
            Log::error('Errore nel marcare tutte le notifiche come lette: ' . $e->getMessage());
            return response()->json(['error' => 'Errore nell\'operazione'], 500);
        }
    }

    /**
     * Remove the specified notifica
     */
    public function destroy(Notifica $notifica)
    {
        try {
            // Verifica che l'utente possa eliminare questa notifica
            if ($notifica->user_id !== Auth::id() && !Auth::user()->hasPermission('notifiche.delete_all')) {
                abort(403, 'Non autorizzato a eliminare questa notifica');
            }

            $notifica->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notifica eliminata con successo'
            ]);

        } catch (\Exception $e) {
            Log::error('Errore nell\'eliminazione notifica: ' . $e->getMessage());
            return response()->json(['error' => 'Errore nell\'eliminazione'], 500);
        }
    }

    /**
     * Elimina tutte le notifiche lette
     */
    public function clearRead()
    {
        try {
            $deleted = Auth::user()
                          ->notifiche()
                          ->whereNotNull('read_at')
                          ->delete();

            Log::info("Eliminate $deleted notifiche lette", [
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Eliminate $deleted notifiche lette",
                'count' => $deleted
            ]);

        } catch (\Exception $e) {
            Log::error('Errore nell\'eliminazione notifiche lette: ' . $e->getMessage());
            return response()->json(['error' => 'Errore nell\'operazione'], 500);
        }
    }

    /**
     * API: Conta notifiche non lette per l'utente corrente
     */
    public function unreadCount()
    {
        try {
            $count = Auth::user()->notifiche()->whereNull('read_at')->count();
            
            return response()->json([
                'count' => $count,
                'hasUnread' => $count > 0
            ]);

        } catch (\Exception $e) {
            Log::error('Errore nel conteggio notifiche non lette: ' . $e->getMessage());
            return response()->json(['count' => 0, 'hasUnread' => false]);
        }
    }

    /**
     * API: Ultime notifiche per dropdown
     */
    public function recent()
    {
        try {
            $notifiche = Auth::user()
                           ->notifiche()
                           ->orderBy('created_at', 'desc')
                           ->limit(10)
                           ->get()
                           ->map(function($notifica) {
                               return [
                                   'id' => $notifica->id,
                                   'titolo' => $notifica->titolo,
                                   'messaggio' => \Str::limit($notifica->messaggio, 100),
                                   'tipo' => $notifica->tipo,
                                   'priorita' => $notifica->priorita,
                                   'read_at' => $notifica->read_at,
                                   'created_at' => $notifica->created_at->diffForHumans(),
                                   'url' => $notifica->url_azione ?? route('notifiche.show', $notifica->id),
                                   'icon' => $this->getIconByTipo($notifica->tipo),
                                   'color' => $this->getColorByPriorita($notifica->priorita)
                               ];
                           });

            return response()->json($notifiche);

        } catch (\Exception $e) {
            Log::error('Errore nel caricamento notifiche recenti: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Crea notifiche automatiche di sistema
     */
    public static function createSystemNotification($users, $tipo, $titolo, $messaggio, $options = [])
    {
        try {
            if (!is_array($users)) {
                $users = [$users];
            }

            $defaultOptions = [
                'priorita' => 'normale',
                'url_azione' => null,
                'testo_azione' => null,
                'scade_il' => null,
                'metadati' => []
            ];

            $options = array_merge($defaultOptions, $options);

            foreach ($users as $user) {
                $userId = is_object($user) ? $user->id : $user;
                
                Notifica::create([
                    'user_id' => $userId,
                    'tipo' => $tipo,
                    'titolo' => $titolo,
                    'messaggio' => $messaggio,
                    'priorita' => $options['priorita'],
                    'url_azione' => $options['url_azione'],
                    'testo_azione' => $options['testo_azione'],
                    'scade_il' => $options['scade_il'],
                    'metadati' => json_encode(array_merge([
                        'created_by_system' => true,
                        'created_at' => now()->toISOString()
                    ], $options['metadati']))
                ]);
            }

            Log::info("Notifiche di sistema create", [
                'tipo' => $tipo,
                'users_count' => count($users),
                'titolo' => $titolo
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Errore nella creazione notifiche di sistema: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notifiche per scadenze documenti
     */
    public function checkScadenzeDocumenti()
    {
        try {
            $oggi = now();
            $traUnMese = $oggi->copy()->addMonth();

            // Volontari con visite mediche o documenti in scadenza
            $volontariConScadenze = Volontario::where('attivo', true)
                ->where(function ($query) use ($oggi, $traUnMese) {
                    $query->whereDate('scadenza_visita_medica', '>=', $oggi)
                          ->whereDate('scadenza_visita_medica', '<=', $traUnMese);
                })
                ->orWhereHas('documenti', function ($query) use ($oggi, $traUnMese) {
                    $query->whereNotNull('data_scadenza')
                          ->whereDate('data_scadenza', '>=', $oggi)
                          ->whereDate('data_scadenza', '<=', $traUnMese);
                })
                ->get();

            foreach ($volontariConScadenze as $volontario) {
                $scadenze = [];
                
                if ($volontario->scadenza_visita_medica && $volontario->scadenza_visita_medica <= $traUnMese) {
                    $scadenze[] = 'Visita medica (scade il ' . Carbon::parse($volontario->scadenza_visita_medica)->format('d/m/Y') . ')';
                }

                $documentiInScadenza = $volontario->documenti()
                    ->whereNotNull('data_scadenza')
                    ->whereDate('data_scadenza', '>=', $oggi)
                    ->whereDate('data_scadenza', '<=', $traUnMese)
                    ->get();

                foreach ($documentiInScadenza as $doc) {
                    $scadenze[] = $doc->nome_documento . ' (scade il ' . $doc->data_scadenza->format('d/m/Y') . ')';
                }

                if (!empty($scadenze)) {
                    // Notifica al volontario se ha un account utente
                    if ($volontario->user_id) {
                        self::createSystemNotification(
                            [$volontario->user_id],
                            'scadenza_documento',
                            'Documenti in scadenza',
                            "I tuoi documenti stanno per scadere: " . implode(', ', $scadenze),
                            [
                                'priorita' => 'alta',
                                'url_azione' => route('volontari.show', $volontario->id),
                                'testo_azione' => 'Visualizza dettagli'
                            ]
                        );
                    }

                    // Notifica ai responsabili
                    $responsabili = User::whereHas('roles', function($q) {
                        $q->whereIn('name', ['admin', 'responsabile']);
                    })->get();

                    self::createSystemNotification(
                        $responsabili,
                        'scadenza_documento',
                        'Scadenze documenti volontario',
                        "Documenti in scadenza per {$volontario->nome} {$volontario->cognome}: " . implode(', ', $scadenze),
                        [
                            'priorita' => 'normale',
                            'url_azione' => route('volontari.show', $volontario->id),
                            'testo_azione' => 'Visualizza volontario'
                        ]
                    );
                }
            }

            return response()->json(['success' => true, 'checked' => $volontariConScadenze->count()]);

        } catch (\Exception $e) {
            Log::error('Errore nel controllo scadenze documenti: ' . $e->getMessage());
            return response()->json(['error' => 'Errore nel controllo scadenze'], 500);
        }
    }

    /**
     * Helper: Icona per tipo notifica
     */
    private function getIconByTipo($tipo)
    {
        $icons = [
            'evento_assegnato' => 'calendar-plus',
            'evento_modificato' => 'calendar-edit',
            'evento_cancellato' => 'calendar-x',
            'scadenza_documento' => 'file-clock',
            'scadenza_dpi' => 'shield-alert',
            'manutenzione_mezzo' => 'truck',
            'ticket_assegnato' => 'ticket',
            'ticket_aggiornato' => 'message-square',
            'scorte_minime' => 'package-x',
            'sistema' => 'settings',
            'comunicazione' => 'megaphone',
            'altro' => 'bell'
        ];

        return $icons[$tipo] ?? 'bell';
    }

    /**
     * Helper: Colore per priorità
     */
    private function getColorByPriorita($priorita)
    {
        $colors = [
            'bassa' => 'text-gray-500',
            'normale' => 'text-blue-500',
            'alta' => 'text-orange-500',
            'urgente' => 'text-red-500'
        ];

        return $colors[$priorita] ?? 'text-gray-500';
    }
}
