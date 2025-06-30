<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\AllegatoTicket;
use App\Models\User;
use App\Models\Mezzo;
use App\Models\Dpi;
use App\Models\Magazzino;
use App\Models\LogAttivita;
use App\Models\Notifica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class TicketsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:tickets,visualizza')->only(['index', 'show']);
        $this->middleware('permission:tickets,crea')->only(['create', 'store']);
        $this->middleware('permission:tickets,modifica')->only(['edit', 'update', 'assegna', 'cambiaStato']);
        $this->middleware('permission:tickets,elimina')->only(['destroy']);
    }

    // ===================================
    // INDEX - Lista tickets
    // ===================================
    public function index(Request $request)
    {
        $query = Ticket::with(['user', 'assegnatario', 'mezzo', 'dpi', 'articoloMagazzino', 'allegati']);

        // Filtri
        if ($request->filled('stato')) {
            if ($request->stato === 'aperti') {
                $query->whereIn('stato', ['aperto', 'assegnato', 'in_corso']);
            } else {
                $query->where('stato', $request->stato);
            }
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('priorita')) {
            $query->where('priorita', $request->priorita);
        }

        if ($request->filled('assegnato_a')) {
            if ($request->assegnato_a === 'non_assegnati') {
                $query->whereNull('assegnato_a');
            } else {
                $query->where('assegnato_a', $request->assegnato_a);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_ticket', 'like', "%{$search}%")
                  ->orWhere('titolo', 'like', "%{$search}%")
                  ->orWhere('descrizione', 'like', "%{$search}%");
            });
        }

        // Filtro per ruolo utente
        $user = auth()->user();
        if (!$user->isAdmin() && !in_array($user->ruolo, ['direttivo', 'segreteria'])) {
            $query->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('assegnato_a', $user->id);
            });
        }

        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $tickets = $query->paginate(15);

        $statistiche = [
            'totale' => Ticket::count(),
            'aperti' => Ticket::whereIn('stato', ['aperto', 'assegnato', 'in_corso'])->count(),
            'critici' => Ticket::where('priorita', 'critica')
                              ->whereIn('stato', ['aperto', 'assegnato', 'in_corso'])
                              ->count(),
            'non_assegnati' => Ticket::whereNull('assegnato_a')
                                   ->whereIn('stato', ['aperto'])
                                   ->count(),
            'bloccano_operativita' => Ticket::where('blocca_operativita', true)
                                          ->whereIn('stato', ['aperto', 'assegnato', 'in_corso'])
                                          ->count(),
            'risolti_mese' => Ticket::where('stato', 'risolto')
                                   ->whereMonth('data_risoluzione', now()->month)
                                   ->count()
        ];

        $utentiAssegnazione = User::whereIn('ruolo', ['admin', 'direttivo', 'mezzi', 'dipendente'])
                                 ->where('attivo', true)
                                 ->orderBy('nome')
                                 ->get();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('tickets.partials.table', compact('tickets'))->render(),
                'pagination' => $tickets->links()->render()
            ]);
        }

        return view('tickets.index', compact('tickets', 'statistiche', 'utentiAssegnazione'));
    }

    // ===================================
    // CREATE - Form creazione ticket
    // ===================================
    public function create(Request $request)
    {
        $precompilazione = [
            'categoria' => $request->get('categoria'),
            'mezzo_id' => $request->get('mezzo_id'),
            'dpi_id' => $request->get('dpi_id'),
            'articolo_magazzino_id' => $request->get('articolo_magazzino_id')
        ];

        $mezzi = Mezzo::where('attivo', true)->orderBy('targa')->get();
        $dpi = Dpi::where('disponibile', true)->orderBy('nome')->get();
        $articoliMagazzino = Magazzino::where('attivo', true)->orderBy('nome_articolo')->get();

        return view('tickets.create', compact('precompilazione', 'mezzi', 'dpi', 'articoliMagazzino'));
    }

    // ===================================
    // STORE - Salva nuovo ticket
    // ===================================
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titolo' => 'required|string|max:255',
            'descrizione' => 'required|string',
            'categoria' => 'required|in:mezzi,dpi,magazzino,strutture,informatica,formazione,amministrativo,sicurezza,altro',
            'sottocategoria' => 'nullable|string|max:255',
            'priorita' => 'required|in:bassa,media,alta,critica',
            'urgenza' => 'required|in:non_urgente,normale,urgente,critica',
            'blocca_operativita' => 'boolean',
            'mezzo_id' => 'nullable|exists:mezzi,id',
            'dpi_id' => 'nullable|exists:dpi,id',
            'articolo_magazzino_id' => 'nullable|exists:magazzino,id',
            'ubicazione_problema' => 'nullable|string|max:255',
            'richiede_approvazione' => 'boolean',
            'allegati' => 'nullable|array|max:5',
            'allegati.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx'
        ]);

        DB::beginTransaction();
        
        try {
            $numeroTicket = Ticket::generaNumeroTicket();

            $ticket = Ticket::create([
                'numero_ticket' => $numeroTicket,
                'user_id' => auth()->id(),
                'titolo' => $validated['titolo'],
                'descrizione' => $validated['descrizione'],
                'categoria' => $validated['categoria'],
                'sottocategoria' => $validated['sottocategoria'],
                'priorita' => $validated['priorita'],
                'urgenza' => $validated['urgenza'],
                'blocca_operativita' => $validated['blocca_operativita'] ?? false,
                'stato' => 'aperto',
                'data_apertura' => now(),
                'mezzo_id' => $validated['mezzo_id'],
                'dpi_id' => $validated['dpi_id'],
                'articolo_magazzino_id' => $validated['articolo_magazzino_id'],
                'ubicazione_problema' => $validated['ubicazione_problema'],
                'richiede_approvazione' => $validated['richiede_approvazione'] ?? false
            ]);

            // Carica allegati se presenti
            if (!empty($validated['allegati'])) {
                foreach ($validated['allegati'] as $file) {
                    $path = $file->store('tickets/allegati/' . $ticket->id, 'public');
                    
                    AllegatoTicket::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => auth()->id(),
                        'nome_file' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_originale' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'tipo' => 'foto_problema'
                    ]);
                }
            }

            // Auto-assegnazione basata su categoria
            $assegnatario = $this->determinaAssegnatarioAutomatico($validated['categoria']);
            if ($assegnatario) {
                $ticket->assegnaA($assegnatario->id, 'Assegnazione automatica');
            }

            // Notifiche
            $this->inviaNotificheNuovoTicket($ticket);

            if ($validated['blocca_operativita']) {
                $this->notificaBloccaOperativita($ticket);
            }

            LogAttivita::create([
                'user_id' => auth()->id(),
                'azione' => 'creazione_ticket',
                'modulo' => 'tickets',
                'risorsa_id' => $ticket->id,
                'descrizione' => "Creato ticket #{$ticket->numero_ticket}: {$ticket->titolo}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'data_ora' => now()
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Ticket #{$ticket->numero_ticket} creato con successo",
                    'redirect' => route('tickets.show', $ticket->id)
                ]);
            }

            return redirect()->route('tickets.show', $ticket->id)
                           ->with('success', "Ticket #{$ticket->numero_ticket} creato con successo");

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errore durante la creazione: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                        ->withErrors(['error' => 'Errore durante la creazione: ' . $e->getMessage()]);
        }
    }

    // ===================================
    // SHOW - Dettagli ticket
    // ===================================
    public function show(Ticket $ticket)
    {
        if (!$this->puoAccedereTicket($ticket)) {
            abort(403, 'Non hai i permessi per visualizzare questo ticket');
        }

        $ticket->load([
            'user',
            'assegnatario', 
            'approvatore',
            'mezzo',
            'dpi',
            'articoloMagazzino',
            'allegati' => function($query) {
                $query->orderBy('created_at', 'desc');
            }
        ]);

        $statistiche = [
            'tempo_apertura' => $ticket->data_apertura->diffForHumans(),
            'tempo_risposta' => $ticket->tempo_risposta,
            'tempo_risoluzione' => $ticket->tempo_risoluzione_totale,
            'ore_residue_sla' => $ticket->ore_residue_sla,
            'progresso' => $ticket->progresso,
            'in_ritardo' => $ticket->in_ritardo
        ];

        $possibiliAssegnatari = $this->getPossibiliAssegnatari($ticket->categoria);

        return view('tickets.show', compact('ticket', 'statistiche', 'possibiliAssegnatari'));
    }

    // ===================================
    // ASSEGNA - Assegna ticket a utente
    // ===================================
    public function assegna(Request $request, Ticket $ticket)
    {
        $request->validate([
            'assegnato_a' => 'required|exists:users,id',
            'note' => 'nullable|string|max:500'
        ]);

        if (!$this->puoAssegnareTicket($ticket)) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai i permessi per assegnare questo ticket'
            ], 403);
        }

        DB::beginTransaction();
        
        try {
            $ticket->assegnaA($request->assegnato_a, $request->note);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ticket assegnato con successo',
                'assegnatario' => User::find($request->assegnato_a)->nome_completo,
                'nuovo_stato' => $ticket->fresh()->stato_label
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'assegnazione: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===================================
    // CAMBIO STATO - Gestione workflow
    // ===================================
    public function cambiaStato(Request $request, Ticket $ticket)
    {
        $request->validate([
            'azione' => 'required|in:inizia_lavori,risolvi,chiudi,annulla,richiedi_approvazione,approva',
            'note' => 'nullable|string',
            'soluzione' => 'required_if:azione,risolvi|string',
            'costo' => 'nullable|numeric|min:0',
            'fornitore' => 'nullable|string|max:255',
            'richiede_follow_up' => 'boolean',
            'data_follow_up' => 'nullable|date|after:today',
            'valutazione' => 'nullable|integer|min:1|max:5',
            'feedback' => 'nullable|string',
            'motivo_annullamento' => 'required_if:azione,annulla|string'
        ]);

        if (!$this->puoModificareTicket($ticket)) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai i permessi per modificare questo ticket'
            ], 403);
        }

        DB::beginTransaction();
        
        try {
            $messaggio = '';
            
            switch ($request->azione) {
                case 'inizia_lavori':
                    $ticket->iniziaLavori($request->note);
                    $messaggio = 'Lavori avviati con successo';
                    break;
                    
                case 'risolvi':
                    $ticket->risolvi(
                        $request->soluzione,
                        $request->costo,
                        $request->fornitore,
                        $request->richiede_follow_up ?? false,
                        $request->data_follow_up
                    );
                    $messaggio = 'Ticket risolto con successo';
                    break;
                    
                case 'chiudi':
                    $ticket->chiudi($request->valutazione, $request->feedback);
                    $messaggio = 'Ticket chiuso con successo';
                    break;
                    
                case 'annulla':
                    $ticket->annulla($request->motivo_annullamento);
                    $messaggio = 'Ticket annullato';
                    break;
                    
                case 'richiedi_approvazione':
                    $ticket->richiedeApprovazione($request->note);
                    $messaggio = 'Richiesta approvazione inviata';
                    break;
                    
                case 'approva':
                    $ticket->approva(auth()->id(), $request->note);
                    $messaggio = 'Ticket approvato e risolto';
                    break;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $messaggio,
                'nuovo_stato' => $ticket->fresh()->stato_label,
                'colore_stato' => $ticket->fresh()->colore_stato,
                'progresso' => $ticket->fresh()->progresso
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'operazione: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===================================
    // DESTROY - Elimina ticket
    // ===================================
    public function destroy(Ticket $ticket)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo gli amministratori possono eliminare i ticket'
            ], 403);
        }

        if (!in_array($ticket->stato, ['aperto', 'annullato'])) {
            return response()->json([
                'success' => false,
                'message' => 'Impossibile eliminare ticket in stato: ' . $ticket->stato_label
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $numeroTicket = $ticket->numero_ticket;
            
            // Elimina allegati fisici
            foreach ($ticket->allegati as $allegato) {
                Storage::disk('public')->delete($allegato->file_path);
            }
            
            $ticket->delete();

            LogAttivita::create([
                'user_id' => auth()->id(),
                'azione' => 'eliminazione_ticket',
                'modulo' => 'tickets',
                'risorsa_id' => $ticket->id,
                'descrizione' => "Eliminato ticket #{$numeroTicket}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'data_ora' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ticket eliminato con successo'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'eliminazione: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===================================
    // METODI HELPER PRIVATI
    // ===================================
    
    private function puoAccedereTicket($ticket)
    {
        $user = auth()->user();
        
        if ($user->isAdmin() || in_array($user->ruolo, ['direttivo', 'segreteria'])) {
            return true;
        }
        
        return $ticket->user_id === $user->id || $ticket->assegnato_a === $user->id;
    }

    private function puoAssegnareTicket($ticket)
    {
        $user = auth()->user();
        return $user->isAdmin() || in_array($user->ruolo, ['direttivo', 'segreteria', 'mezzi']);
    }

    private function puoModificareTicket($ticket)
    {
        $user = auth()->user();
        
        if ($user->isAdmin() || in_array($user->ruolo, ['direttivo', 'segreteria'])) {
            return true;
        }
        
        return $ticket->assegnato_a === $user->id;
    }

    private function determinaAssegnatarioAutomatico($categoria)
    {
        switch ($categoria) {
            case 'mezzi':
                return User::where('ruolo', 'mezzi')->where('attivo', true)->first();
            case 'dpi':
            case 'magazzino':
                return User::where('ruolo', 'mezzi')->where('attivo', true)->first();
            case 'informatica':
                return User::where('ruolo', 'admin')->where('attivo', true)->first();
            default:
                return null;
        }
    }

    private function getPossibiliAssegnatari($categoria)
    {
        $baseQuery = User::where('attivo', true);
        
        switch ($categoria) {
            case 'mezzi':
            case 'dpi':
            case 'magazzino':
                return $baseQuery->whereIn('ruolo', ['admin', 'direttivo', 'mezzi'])->orderBy('nome')->get();
            case 'informatica':
                return $baseQuery->whereIn('ruolo', ['admin'])->orderBy('nome')->get();
            default:
                return $baseQuery->whereIn('ruolo', ['admin', 'direttivo', 'dipendente'])->orderBy('nome')->get();
        }
    }

    private function inviaNotificheNuovoTicket($ticket)
    {
        $destinatari = [];
        
        // Notifica sempre agli admin
        $admins = User::where('ruolo', 'admin')->where('attivo', true)->pluck('id');
        $destinatari = array_merge($destinatari, $admins->toArray());
        
        // Notifica in base alla categoria
        switch ($ticket->categoria) {
            case 'mezzi':
            case 'dpi':
            case 'magazzino':
                $responsabiliMezzi = User::where('ruolo', 'mezzi')->where('attivo', true)->pluck('id');
                $destinatari = array_merge($destinatari, $responsabiliMezzi->toArray());
                break;
        }
        
        if (!empty($destinatari)) {
            Notifica::crea([
                'destinatari' => array_unique($destinatari),
                'titolo' => 'Nuovo Ticket Creato',
                'messaggio' => "È stato creato un nuovo ticket: #{$ticket->numero_ticket} - {$ticket->titolo}",
                'tipo' => 'ticket'
            ]);
        }
    }

    private function notificaBloccaOperativita($ticket)
    {
        $utentiCritici = User::whereIn('ruolo', ['admin', 'direttivo', 'mezzi'])
                            ->where('attivo', true)
                            ->pluck('id');

        Notifica::crea([
            'destinatari' => $utentiCritici->toArray(),
            'titolo' => 'URGENTE: Problema Blocca Operatività',
            'messaggio' => "Il ticket #{$ticket->numero_ticket} segnala un problema che blocca l'operatività: {$ticket->titolo}",
            'tipo' => 'risoluzione_critica'
        ]);
    }
}