<?php

namespace App\Http\Controllers;

use App\Models\Mezzo;
use App\Models\Manutenzione;
use App\Models\ChecklistTemplate;
use App\Models\ChecklistCompilata;
use App\Models\User;
use App\Models\LogAttivita;
use App\Models\Notifica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class MezziController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:access_mezzi')->except(['checklistCreate', 'checklistStore']);
        $this->middleware('permission:mezzi,visualizza')->only(['index', 'show']);
        $this->middleware('permission:mezzi,crea')->only(['create', 'store']);
        $this->middleware('permission:mezzi,modifica')->only(['edit', 'update']);
        $this->middleware('permission:mezzi,elimina')->only(['destroy']);
    }

    // ===================================
    // INDEX - Lista mezzi
    // ===================================
    public function index(Request $request)
    {
        $query = Mezzo::with(['ultimaManutenzione', 'ultimaChecklist', 'ultimoUtente', 'ticketsAperti']);

        // Filtri
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('stato')) {
            switch ($request->stato) {
                case 'operativi':
                    $query->where('attivo', true)->where('in_servizio', true);
                    break;
                case 'manutenzione':
                    $query->where('attivo', true)->where('in_servizio', false);
                    break;
                case 'dismessi':
                    $query->where('attivo', false);
                    break;
                case 'scadenze':
                    $query->where('attivo', true)
                          ->where(function($q) {
                              $q->whereDate('scadenza_revisione', '<=', now()->addDays(30))
                                ->orWhereDate('scadenza_assicurazione', '<=', now()->addDays(30))
                                ->orWhereDate('scadenza_bollo', '<=', now()->addDays(30));
                          });
                    break;
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('targa', 'like', "%{$search}%")
                  ->orWhere('marca', 'like', "%{$search}%")
                  ->orWhere('modello', 'like', "%{$search}%")
                  ->orWhere('tipo', 'like', "%{$search}%");
            });
        }

        $sortField = $request->get('sort', 'targa');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $mezzi = $query->paginate(15);

        $statistiche = [
            'totale' => Mezzo::count(),
            'operativi' => Mezzo::where('attivo', true)->where('in_servizio', true)->count(),
            'in_manutenzione' => Mezzo::where('attivo', true)->where('in_servizio', false)->count(),
            'con_scadenze' => Mezzo::where('attivo', true)
                                  ->where(function($q) {
                                      $q->whereDate('scadenza_revisione', '<=', now()->addDays(30))
                                        ->orWhereDate('scadenza_assicurazione', '<=', now()->addDays(30));
                                  })->count(),
            'tickets_aperti' => Mezzo::whereHas('ticketsAperti')->count(),
            'km_totali' => Mezzo::where('attivo', true)->sum('km_attuali'),
            'costo_manutenzioni_anno' => Manutenzione::whereYear('data_manutenzione', now()->year)->sum('costo')
        ];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('mezzi.partials.table', compact('mezzi'))->render(),
                'pagination' => $mezzi->links()->render()
            ]);
        }

        return view('mezzi.index', compact('mezzi', 'statistiche'));
    }

    // ===================================
    // CREATE - Form creazione mezzo
    // ===================================
    public function create()
    {
        return view('mezzi.create');
    }

    // ===================================
    // STORE - Salva nuovo mezzo
    // ===================================
    public function store(Request $request)
    {
        $validated = $request->validate([
            'targa' => 'required|string|max:10|unique:mezzi,targa',
            'tipo' => 'required|in:ambulanza_a,ambulanza_b,auto_medica,auto_servizio,furgone,altro',
            'marca' => 'required|string|max:255',
            'modello' => 'required|string|max:255',
            'anno' => 'required|integer|min:1990|max:' . (now()->year + 1),
            'numero_telaio' => 'nullable|string|max:255',
            'colore' => 'required|string|max:50',
            'alimentazione' => 'required|in:benzina,diesel,gpl,metano,elettrico,ibrido',
            'scadenza_revisione' => 'required|date|after:today',
            'scadenza_assicurazione' => 'required|date|after:today',
            'compagnia_assicurazione' => 'nullable|string|max:255',
            'numero_polizza' => 'nullable|string|max:255',
            'scadenza_bollo' => 'nullable|date',
            'scadenza_collaudo' => 'nullable|date',
            'km_attuali' => 'required|integer|min:0',
            'km_ultimo_tagliando' => 'nullable|integer|min:0',
            'intervallo_tagliando' => 'required|integer|min:1000|max:50000',
            'data_ultimo_tagliando' => 'nullable|date',
            'dotazioni_sanitarie' => 'nullable|array',
            'dotazioni_tecniche' => 'nullable|array',
            'aria_condizionata' => 'boolean',
            'gps' => 'boolean',
            'radio_ponte' => 'boolean',
            'frequenza_radio' => 'nullable|string|max:20',
            'costo_acquisto' => 'nullable|numeric|min:0',
            'data_acquisto' => 'nullable|date',
            'fornitore' => 'nullable|string|max:255',
            'note' => 'nullable|string'
        ]);

        DB::beginTransaction();
        
        try {
            $kmProssimoTagliando = null;
            if ($validated['km_ultimo_tagliando']) {
                $kmProssimoTagliando = $validated['km_ultimo_tagliando'] + $validated['intervallo_tagliando'];
            } else {
                $kmProssimoTagliando = $validated['km_attuali'] + $validated['intervallo_tagliando'];
            }

            $mezzo = Mezzo::create([
                ...$validated,
                'km_prossimo_tagliando' => $kmProssimoTagliando,
                'posizione_attuale' => 'sede',
                'attivo' => true,
                'in_servizio' => true
            ]);

            LogAttivita::create([
                'user_id' => auth()->id(),
                'azione' => 'creazione_mezzo',
                'modulo' => 'mezzi',
                'risorsa_id' => $mezzo->id,
                'descrizione' => "Creato nuovo mezzo: {$mezzo->targa} - {$mezzo->tipo_descrizione}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'data_ora' => now()
            ]);

            $responsabiliMezzi = User::where('ruolo', 'mezzi')->where('attivo', true)->pluck('id');
            
            if ($responsabiliMezzi->isNotEmpty()) {
                Notifica::crea([
                    'destinatari' => $responsabiliMezzi->toArray(),
                    'titolo' => 'Nuovo Mezzo Aggiunto',
                    'messaggio' => "È stato aggiunto un nuovo mezzo: {$mezzo->targa} ({$mezzo->tipo_descrizione})",
                    'tipo' => 'generale'
                ]);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mezzo creato con successo',
                    'redirect' => route('mezzi.show', $mezzo->id)
                ]);
            }

            return redirect()->route('mezzi.show', $mezzo->id)
                           ->with('success', 'Mezzo creato con successo');

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
    // SHOW - Dettagli mezzo
    // ===================================
    public function show(Mezzo $mezzo)
    {
        $mezzo->load([
            'manutenzioni' => function($query) {
                $query->orderBy('data_manutenzione', 'desc')->limit(10);
            },
            'checklistCompilate' => function($query) {
                $query->with('user')->orderBy('data_compilazione', 'desc')->limit(5);
            },
            'ultimoUtente',
            'tickets' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(5);
            }
        ]);

        $statistiche = [
            'manutenzioni_anno' => $mezzo->manutenzioni()
                                        ->whereYear('data_manutenzione', now()->year)
                                        ->count(),
            'costo_manutenzioni_anno' => $mezzo->costo_manutenzioni_anno,
            'km_medio_mensile' => $mezzo->km_medio_mensile,
            'efficienza' => $mezzo->efficienza,
            'checklist_ultimo_mese' => $mezzo->checklistCompilate()
                                            ->where('data_compilazione', '>=', now()->subMonth())
                                            ->count(),
            'checklist_non_conformi' => $mezzo->checklistNonConformi()
                                             ->where('data_compilazione', '>=', now()->subMonth())
                                             ->count(),
            'tickets_aperti' => $mezzo->ticketsAperti->count()
        ];

        $scadenzeVicine = $mezzo->scadenze_vicine;

        $prossimiInterventi = $mezzo->manutenzioni()
                                   ->where('stato', 'programmata')
                                   ->orderBy('data_manutenzione')
                                   ->get();

        $checklistTemplates = ChecklistTemplate::where('attivo', true)
                                              ->where(function($query) use ($mezzo) {
                                                  $query->where('tipo_mezzo', $mezzo->tipo)
                                                        ->orWhere('tipo_mezzo', 'tutti');
                                              })
                                              ->orderBy('ordine')
                                              ->get();

        return view('mezzi.show', compact(
            'mezzo', 
            'statistiche', 
            'scadenzeVicine', 
            'prossimiInterventi', 
            'checklistTemplates'
        ));
    }

    // ===================================
    // EDIT - Form modifica mezzo
    // ===================================
    public function edit(Mezzo $mezzo)
    {
        return view('mezzi.edit', compact('mezzo'));
    }

    // ===================================
    // UPDATE - Aggiorna mezzo
    // ===================================
    public function update(Request $request, Mezzo $mezzo)
    {
        $validated = $request->validate([
            'targa' => [
                'required',
                'string',
                'max:10',
                Rule::unique('mezzi')->ignore($mezzo->id)
            ],
            'tipo' => 'required|in:ambulanza_a,ambulanza_b,auto_medica,auto_servizio,furgone,altro',
            'marca' => 'required|string|max:255',
            'modello' => 'required|string|max:255',
            'anno' => 'required|integer|min:1990|max:' . (now()->year + 1),
            'numero_telaio' => 'nullable|string|max:255',
            'colore' => 'required|string|max:50',
            'alimentazione' => 'required|in:benzina,diesel,gpl,metano,elettrico,ibrido',
            'scadenza_revisione' => 'required|date',
            'scadenza_assicurazione' => 'required|date',
            'compagnia_assicurazione' => 'nullable|string|max:255',
            'numero_polizza' => 'nullable|string|max:255',
            'scadenza_bollo' => 'nullable|date',
            'scadenza_collaudo' => 'nullable|date',
            'km_attuali' => 'required|integer|min:' . $mezzo->km_attuali,
            'km_ultimo_tagliando' => 'nullable|integer|min:0',
            'intervallo_tagliando' => 'required|integer|min:1000|max:50000',
            'data_ultimo_tagliando' => 'nullable|date',
            'dotazioni_sanitarie' => 'nullable|array',
            'dotazioni_tecniche' => 'nullable|array',
            'aria_condizionata' => 'boolean',
            'gps' => 'boolean',
            'radio_ponte' => 'boolean',
            'frequenza_radio' => 'nullable|string|max:20',
            'costo_acquisto' => 'nullable|numeric|min:0',
            'data_acquisto' => 'nullable|date',
            'fornitore' => 'nullable|string|max:255',
            'posizione_attuale' => 'nullable|string|max:255',
            'note' => 'nullable|string'
        ]);

        DB::beginTransaction();
        
        try {
            $kmPrecedenti = $mezzo->km_attuali;
            
            if ($validated['km_ultimo_tagliando'] && 
                $validated['km_ultimo_tagliando'] != $mezzo->km_ultimo_tagliando) {
                $validated['km_prossimo_tagliando'] = $validated['km_ultimo_tagliando'] + $validated['intervallo_tagliando'];
            }

            $mezzo->update($validated);

            if ($validated['km_attuali'] > $kmPrecedenti) {
                $mezzo->aggiornaKm($validated['km_attuali'], auth()->id());
            }

            LogAttivita::create([
                'user_id' => auth()->id(),
                'azione' => 'modifica_mezzo',
                'modulo' => 'mezzi',
                'risorsa_id' => $mezzo->id,
                'descrizione' => "Modificato mezzo: {$mezzo->targa}",
                'valori_precedenti' => ['km_attuali' => $kmPrecedenti],
                'valori_nuovi' => ['km_attuali' => $validated['km_attuali']],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'data_ora' => now()
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mezzo aggiornato con successo'
                ]);
            }

            return redirect()->route('mezzi.show', $mezzo->id)
                           ->with('success', 'Mezzo aggiornato con successo');

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
    // DESTROY - Elimina mezzo
    // ===================================
    public function destroy(Mezzo $mezzo)
    {
        if ($mezzo->ticketsAperti->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossibile eliminare il mezzo: sono presenti ticket aperti'
            ], 422);
        }

        if ($mezzo->checklistCompilate()->where('created_at', '>=', now()->subDays(30))->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossibile eliminare il mezzo: sono presenti checklist recenti'
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $targa = $mezzo->targa;
            
            $mezzo->delete();

            LogAttivita::create([
                'user_id' => auth()->id(),
                'azione' => 'eliminazione_mezzo',
                'modulo' => 'mezzi',
                'risorsa_id' => $mezzo->id,
                'descrizione' => "Eliminato mezzo: {$targa}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'data_ora' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mezzo eliminato con successo'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'eliminazione: ' . $e->getMessage()
            ], 500);
        }
    }
}