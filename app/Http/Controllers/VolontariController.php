<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Volontario;
use App\Models\Documento;
use App\Models\AssegnazioneDpi;
use App\Models\LogAttivita;
use App\Models\Notifica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class VolontariController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:volontari,visualizza')->only(['index', 'show']);
        $this->middleware('permission:volontari,crea')->only(['create', 'store']);
        $this->middleware('permission:volontari,modifica')->only(['edit', 'update']);
        $this->middleware('permission:volontari,elimina')->only(['destroy']);
    }

    // ===================================
    // INDEX - Lista volontari
    // ===================================
    public function index(Request $request)
    {
        $query = Volontario::with(['user', 'documenti', 'dpiAssegnati.dpi'])
                          ->join('users', 'volontari.user_id', '=', 'users.id');

        // Filtri
        if ($request->filled('stato')) {
            if ($request->stato === 'attivi') {
                $query->where('volontari.attivo', true);
            } elseif ($request->stato === 'sospesi') {
                $query->where('volontari.attivo', false);
            }
        }

        if ($request->filled('formazione')) {
            $query->where('stato_formazione', $request->formazione);
        }

        if ($request->filled('disponibilita')) {
            $query->where('disponibilita', $request->disponibilita);
        }

        if ($request->filled('scadenze')) {
            $query->where(function($q) {
                $q->whereDate('scadenza_visita_medica', '<=', now()->addDays(30))
                  ->whereDate('scadenza_visita_medica', '>=', now());
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('users.nome', 'like', "%{$search}%")
                  ->orWhere('users.cognome', 'like', "%{$search}%")
                  ->orWhere('users.email', 'like', "%{$search}%")
                  ->orWhere('tessera_numero', 'like', "%{$search}%")
                  ->orWhere('users.telefono', 'like', "%{$search}%");
            });
        }

        // Ordinamento
        $sortField = $request->get('sort', 'users.cognome');
        $sortDirection = $request->get('direction', 'asc');
        
        if ($sortField === 'nome_completo') {
            $query->orderBy('users.cognome', $sortDirection)
                  ->orderBy('users.nome', $sortDirection);
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        $volontari = $query->select('volontari.*')->paginate(20);

        // Statistiche per dashboard
        $statistiche = [
            'totale' => Volontario::count(),
            'attivi' => Volontario::where('attivo', true)->count(),
            'con_scadenze' => Volontario::whereDate('scadenza_visita_medica', '<=', now()->addDays(30))
                                      ->whereDate('scadenza_visita_medica', '>=', now())
                                      ->count(),
            'formazione_base' => Volontario::where('stato_formazione', 'base')->count(),
            'formazione_avanzata' => Volontario::where('stato_formazione', 'avanzato')->count(),
            'istruttori' => Volontario::where('stato_formazione', 'istruttore')->count()
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('volontari.partials.table', compact('volontari'))->render(),
                'pagination' => $volontari->links()->render()
            ]);
        }

        return view('volontari.index', compact('volontari', 'statistiche'));
    }

    // ===================================
    // CREATE - Form creazione volontario
    // ===================================
    public function create()
    {
        $numeroTessera = Volontario::generaNumeroTessera();
        
        return view('volontari.create', compact('numeroTessera'));
    }

    // ===================================
    // STORE - Salva nuovo volontario
    // ===================================
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Dati utente
            'nome' => 'required|string|max:255',
            'cognome' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'telefono' => 'nullable|string|max:20',
            'data_nascita' => 'required|date|before:today',
            'codice_fiscale' => 'nullable|string|size:16|unique:users,codice_fiscale',
            'indirizzo' => 'nullable|string',
            'citta' => 'nullable|string|max:100',
            'cap' => 'nullable|string|size:5',
            'provincia' => 'nullable|string|size:2',
            'password' => 'required|string|min:8|confirmed',
            
            // Dati volontario
            'tessera_numero' => 'nullable|string|unique:volontari,tessera_numero',
            'data_iscrizione' => 'required|date',
            'data_visita_medica' => 'nullable|date',
            'scadenza_visita_medica' => 'nullable|date|after:data_visita_medica',
            'medico_competente' => 'nullable|string|max:255',
            'stato_formazione' => 'required|in:base,avanzato,istruttore,in_corso',
            'ultimo_corso' => 'nullable|date',
            'corsi_completati' => 'nullable|array',
            'competenze' => 'nullable|array',
            'disponibilita' => 'required|in:sempre,weekdays,weekend,sera,limitata',
            'note_disponibilita' => 'nullable|string',
            'allergie_patologie' => 'nullable|string',
            'contatto_emergenza_nome' => 'nullable|string|max:255',
            'contatto_emergenza_telefono' => 'nullable|string|max:20',
            'gruppo_sanguigno' => 'nullable|string|max:3',
            'note' => 'nullable|string'
        ]);

        DB::beginTransaction();
        
        try {
            // Crea utente
            $user = User::create([
                'nome' => $validated['nome'],
                'cognome' => $validated['cognome'],
                'email' => $validated['email'],
                'telefono' => $validated['telefono'],
                'data_nascita' => $validated['data_nascita'],
                'codice_fiscale' => $validated['codice_fiscale'],
                'indirizzo' => $validated['indirizzo'],
                'citta' => $validated['citta'],
                'cap' => $validated['cap'],
                'provincia' => $validated['provincia'],
                'ruolo' => 'volontario',
                'password' => Hash::make($validated['password']),
                'attivo' => true
            ]);

            // Crea volontario
            $volontario = Volontario::create([
                'user_id' => $user->id,
                'tessera_numero' => $validated['tessera_numero'] ?: Volontario::generaNumeroTessera(),
                'data_iscrizione' => $validated['data_iscrizione'],
                'data_visita_medica' => $validated['data_visita_medica'],
                'scadenza_visita_medica' => $validated['scadenza_visita_medica'],
                'medico_competente' => $validated['medico_competente'],
                'stato_formazione' => $validated['stato_formazione'],
                'ultimo_corso' => $validated['ultimo_corso'],
                'corsi_completati' => $validated['corsi_completati'] ?? [],
                'competenze' => $validated['competenze'] ?? [],
                'disponibilita' => $validated['disponibilita'],
                'note_disponibilita' => $validated['note_disponibilita'],
                'allergie_patologie' => $validated['allergie_patologie'],
                'contatto_emergenza_nome' => $validated['contatto_emergenza_nome'],
                'contatto_emergenza_telefono' => $validated['contatto_emergenza_telefono'],
                'gruppo_sanguigno' => $validated['gruppo_sanguigno'],
                'note' => $validated['note'],
                'attivo' => true
            ]);

            // Log attività
            LogAttivita::create([
                'user_id' => auth()->id(),
                'azione' => 'creazione_volontario',
                'modulo' => 'volontari',
                'risorsa_id' => $volontario->id,
                'descrizione' => "Creato nuovo volontario: {$user->nome_completo}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'data_ora' => now()
            ]);

            // Notifica di benvenuto al volontario
            Notifica::crea([
                'destinatari' => [$user->id],
                'titolo' => 'Benvenuto in Croce Verde Ascoli Piceno',
                'messaggio' => "Benvenuto nella famiglia della Croce Verde! Il tuo numero tessera è: {$volontario->tessera_numero}",
                'tipo' => 'generale'
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Volontario creato con successo',
                    'redirect' => route('volontari.show', $volontario->id)
                ]);
            }

            return redirect()->route('volontari.show', $volontario->id)
                           ->with('success', 'Volontario creato con successo');

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
    // SHOW - Dettagli volontario
    // ===================================
    public function show(Volontario $volontario)
    {
        $volontario->load([
            'user',
            'documenti' => function($query) {
                $query->orderBy('created_at', 'desc');
            },
            'dpiAssegnati.dpi',
            'partecipazioniEventi.evento'
        ]);

        // Statistiche volontario
        $statistiche = [
            'documenti_totali' => $volontario->documenti->count(),
            'documenti_scaduti' => $volontario->documenti->where('data_scadenza', '<', now())->count(),
            'documenti_in_scadenza' => $volontario->documenti->filter(function($doc) {
                return $doc->data_scadenza && $doc->data_scadenza->diffInDays(now()) <= 30;
            })->count(),
            'dpi_assegnati' => $volontario->dpiAssegnati->where('restituito', false)->count(),
            'eventi_partecipati' => $volontario->partecipazioniEventi->where('stato', 'completato')->count(),
            'ore_servizio_anno' => $volontario->ore_servizio_anno
        ];

        // Scadenze vicine
        $scadenzeVicine = $volontario->scadenze_vicine;

        // Ultimi documenti caricati
        $ultimiDocumenti = $volontario->documenti->take(5);

        // DPI attualmente assegnati
        $dpiAssegnati = $volontario->dpiAssegnati()
                                  ->where('restituito', false)
                                  ->with('dpi')
                                  ->orderBy('data_assegnazione', 'desc')
                                  ->get();

        return view('volontari.show', compact(
            'volontario', 
            'statistiche', 
            'scadenzeVicine', 
            'ultimiDocumenti', 
            'dpiAssegnati'
        ));
    }

    // ===================================
    // EDIT - Form modifica volontario
    // ===================================
    public function edit(Volontario $volontario)
    {
        $volontario->load('user');
        
        return view('volontari.edit', compact('volontario'));
    }

    // ===================================
    // UPDATE - Aggiorna volontario
    // ===================================
    public function update(Request $request, Volontario $volontario)
    {
        $validated = $request->validate([
            // Dati utente
            'nome' => 'required|string|max:255',
            'cognome' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($volontario->user_id)
            ],
            'telefono' => 'nullable|string|max:20',
            'data_nascita' => 'required|date|before:today',
            'codice_fiscale' => [
                'nullable',
                'string',
                'size:16',
                Rule::unique('users')->ignore($volontario->user_id)
            ],
            'indirizzo' => 'nullable|string',
            'citta' => 'nullable|string|max:100',
            'cap' => 'nullable|string|size:5',
            'provincia' => 'nullable|string|size:2',
            'password' => 'nullable|string|min:8|confirmed',
            
            // Dati volontario
            'tessera_numero' => [
                'nullable',
                'string',
                Rule::unique('volontari')->ignore($volontario->id)
            ],
            'data_iscrizione' => 'required|date',
            'data_visita_medica' => 'nullable|date',
            'scadenza_visita_medica' => 'nullable|date|after:data_visita_medica',
            'medico_competente' => 'nullable|string|max:255',
            'stato_formazione' => 'required|in:base,avanzato,istruttore,in_corso',
            'ultimo_corso' => 'nullable|date',
            'corsi_completati' => 'nullable|array',
            'competenze' => 'nullable|array',
            'disponibilita' => 'required|in:sempre,weekdays,weekend,sera,limitata',
            'note_disponibilita' => 'nullable|string',
            'allergie_patologie' => 'nullable|string',
            'contatto_emergenza_nome' => 'nullable|string|max:255',
            'contatto_emergenza_telefono' => 'nullable|string|max:20',
            'gruppo_sanguigno' => 'nullable|string|max:3',
            'ore_servizio_anno' => 'nullable|numeric|min:0',
            'note' => 'nullable|string'
        ]);

        DB::beginTransaction();
        
        try {
            // Aggiorna utente
            $userData = [
                'nome' => $validated['nome'],
                'cognome' => $validated['cognome'],
                'email' => $validated['email'],
                'telefono' => $validated['telefono'],
                'data_nascita' => $validated['data_nascita'],
                'codice_fiscale' => $validated['codice_fiscale'],
                'indirizzo' => $validated['indirizzo'],
                'citta' => $validated['citta'],
                'cap' => $validated['cap'],
                'provincia' => $validated['provincia']
            ];

            if (!empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            $volontario->user->update($userData);

            // Aggiorna volontario
            $volontario->update([
                'tessera_numero' => $validated['tessera_numero'],
                'data_iscrizione' => $validated['data_iscrizione'],
                'data_visita_medica' => $validated['data_visita_medica'],
                'scadenza_visita_medica' => $validated['scadenza_visita_medica'],
                'medico_competente' => $validated['medico_competente'],
                'stato_formazione' => $validated['stato_formazione'],
                'ultimo_corso' => $validated['ultimo_corso'],
                'corsi_completati' => $validated['corsi_completati'] ?? [],
                'competenze' => $validated['competenze'] ?? [],
                'disponibilita' => $validated['disponibilita'],
                'note_disponibilita' => $validated['note_disponibilita'],
                'allergie_patologie' => $validated['allergie_patologie'],
                'contatto_emergenza_nome' => $validated['contatto_emergenza_nome'],
                'contatto_emergenza_telefono' => $validated['contatto_emergenza_telefono'],
                'gruppo_sanguigno' => $validated['gruppo_sanguigno'],
                'ore_servizio_anno' => $validated['ore_servizio_anno'] ?? $volontario->ore_servizio_anno,
                'note' => $validated['note']
            ]);

            // Log attività
            LogAttivita::create([
                'user_id' => auth()->id(),
                'azione' => 'modifica_volontario',
                'modulo' => 'volontari',
                'risorsa_id' => $volontario->id,
                'descrizione' => "Modificato volontario: {$volontario->user->nome_completo}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'data_ora' => now()
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Volontario aggiornato con successo'
                ]);
            }

            return redirect()->route('volontari.show', $volontario->id)
                           ->with('success', 'Volontario aggiornato con successo');

        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                        ->withErrors(['error' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()]);
        }
    }

    // ===================================
    // SOSPENDI - Sospende volontario
    // ===================================
    public function sospendi(Request $request, Volontario $volontario)
    {
        $request->validate([
            'motivo' => 'required|string|max:500'
        ]);

        DB::beginTransaction();
        
        try {
            $volontario->sospendi($request->motivo);

            // Notifica al volontario
            Notifica::crea([
                'destinatari' => [$volontario->user_id],
                'titolo' => 'Sospensione Temporanea',
                'messaggio' => "La tua partecipazione è stata temporaneamente sospesa. Motivo: {$request->motivo}",
                'tipo' => 'segnalazione'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Volontario sospeso con successo'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la sospensione: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===================================
    // RIATTIVA - Riattiva volontario
    // ===================================
    public function riattiva(Volontario $volontario)
    {
        DB::beginTransaction();
        
        try {
            $volontario->riattiva();

            // Notifica al volontario
            Notifica::crea([
                'destinatari' => [$volontario->user_id],
                'titolo' => 'Riattivazione',
                'messaggio' => 'La tua partecipazione è stata riattivata. Benvenuto di nuovo!',
                'tipo' => 'generale'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Volontario riattivato con successo'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la riattivazione: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===================================
    // DESTROY - Elimina volontario
    // ===================================
    public function destroy(Volontario $volontario)
    {
        DB::beginTransaction();
        
        try {
            $nomeCompleto = $volontario->user->nome_completo;
            
            // Soft delete del volontario e dell'utente
            $volontario->delete();
            $volontario->user->delete();

            // Log attività
            LogAttivita::create([
                'user_id' => auth()->id(),
                'azione' => 'eliminazione_volontario',
                'modulo' => 'volontari',
                'risorsa_id' => $volontario->id,
                'descrizione' => "Eliminato volontario: {$nomeCompleto}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'data_ora' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Volontario eliminato con successo'
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
    // EXPORT - Esporta lista volontari
    // ===================================
    public function export(Request $request)
    {
        // Verifica permessi
        if (!auth()->user()->hasPermission('volontari', 'visualizza')) {
            abort(403);
        }

        $volontari = Volontario::with('user')
                              ->when($request->stato === 'attivi', function($q) {
                                  $q->where('attivo', true);
                              })
                              ->when($request->stato === 'sospesi', function($q) {
                                  $q->where('attivo', false);
                              })
                              ->get();

        $filename = 'volontari_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\""
        ];

        $callback = function() use ($volontari) {
            $file = fopen('php://output', 'w');
            
            // BOM per Excel
            fputs($file, "\xEF\xBB\xBF");
            
            // Header CSV
            fputcsv($file, [
                'Tessera',
                'Nome',
                'Cognome', 
                'Email',
                'Telefono',
                'Data Nascita',
                'Data Iscrizione',
                'Stato Formazione',
                'Disponibilità',
                'Ore Servizio Anno',
                'Stato',
                'Scadenza Visita Medica'
            ], ';');

            // Dati
            foreach ($volontari as $volontario) {
                fputcsv($file, [
                    $volontario->tessera_numero,
                    $volontario->user->nome,
                    $volontario->user->cognome,
                    $volontario->user->email,
                    $volontario->user->telefono,
                    $volontario->user->data_nascita?->format('d/m/Y'),
                    $volontario->data_iscrizione->format('d/m/Y'),
                    $volontario->stato_formazione,
                    $volontario->disponibilita,
                    $volontario->ore_servizio_anno,
                    $volontario->attivo ? 'Attivo' : 'Sospeso',
                    $volontario->scadenza_visita_medica?->format('d/m/Y')
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ===================================
    // SCADENZE - Vista scadenze
    // ===================================
    public function scadenze()
    {
        $volontariConScadenze = Volontario::with('user')
                                         ->where('attivo', true)
                                         ->where(function($query) {
                                             $query->whereDate('scadenza_visita_medica', '<=', now()->addDays(60))
                                                   ->whereDate('scadenza_visita_medica', '>=', now());
                                         })
                                         ->orWhereHas('documenti', function($query) {
                                             $query->whereDate('data_scadenza', '<=', now()->addDays(60))
                                                   ->whereDate('data_scadenza', '>=', now());
                                         })
                                         ->orderBy('scadenza_visita_medica')
                                         ->get();

        return view('volontari.scadenze', compact('volontariConScadenze'));
    }
}