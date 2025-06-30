<?php

namespace App\Http\Controllers;

use App\Models\Magazzino;
use App\Models\MovimentoMagazzino;
use App\Models\User;
use App\Models\LogAttivita;
use App\Models\Notifica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class MagazzinoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:magazzino,visualizza')->only(['index', 'show']);
        $this->middleware('permission:magazzino,crea')->only(['create', 'store']);
        $this->middleware('permission:magazzino,modifica')->only(['edit', 'update', 'carico', 'scarico']);
        $this->middleware('permission:magazzino,elimina')->only(['destroy']);
    }

    // ===================================
    // INDEX - Lista articoli magazzino
    // ===================================
    public function index(Request $request)
    {
        $query = Magazzino::with(['ultimoMovimento', 'responsabile']);

        // Filtri
        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('stato')) {
            switch ($request->stato) {
                case 'disponibili':
                    $query->where('attivo', true)->where('quantita_attuale', '>', 0);
                    break;
                case 'sottoscorta':
                    $query->whereRaw('quantita_attuale <= quantita_minima');
                    break;
                case 'esauriti':
                    $query->where('quantita_attuale', '<=', 0);
                    break;
                case 'in_scadenza':
                    $query->whereDate('scadenza', '<=', now()->addDays(30))
                          ->whereDate('scadenza', '>=', now());
                    break;
                case 'scaduti':
                    $query->whereDate('scadenza', '<', now());
                    break;
                case 'non_attivi':
                    $query->where('attivo', false);
                    break;
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nome_articolo', 'like', "%{$search}%")
                  ->orWhere('descrizione', 'like', "%{$search}%")
                  ->orWhere('codice_articolo', 'like', "%{$search}%")
                  ->orWhere('codice_interno', 'like', "%{$search}%")
                  ->orWhere('lotto', 'like', "%{$search}%");
            });
        }

        $sortField = $request->get('sort', 'nome_articolo');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $articoli = $query->paginate(20);

        $statistiche = [
            'totale_articoli' => Magazzino::count(),
            'articoli_attivi' => Magazzino::where('attivo', true)->count(),
            'articoli_sottoscorta' => Magazzino::whereRaw('quantita_attuale <= quantita_minima')->count(),
            'articoli_in_scadenza' => Magazzino::whereDate('scadenza', '<=', now()->addDays(30))
                                             ->whereDate('scadenza', '>=', now())
                                             ->count(),
            'articoli_scaduti' => Magazzino::whereDate('scadenza', '<', now())->count(),
            'valore_totale_stock' => Magazzino::selectRaw('SUM(quantita_attuale * COALESCE(prezzo_unitario, 0)) as totale')
                                             ->value('totale') ?? 0
        ];

        $categorie = Magazzino::select('categoria')
                             ->distinct()
                             ->orderBy('categoria')
                             ->pluck('categoria', 'categoria');

        if ($request->ajax()) {
            return response()->json([
                'html' => view('magazzino.partials.table', compact('articoli'))->render(),
                'pagination' => $articoli->links()->render()
            ]);
        }

        return view('magazzino.index', compact('articoli', 'statistiche', 'categorie'));
    }

    // ===================================
    // CREATE - Form creazione articolo
    // ===================================
    public function create()
    {
        $categorie = [
            'farmaci' => 'Farmaci',
            'dispositivi_medici' => 'Dispositivi Medici',
            'consumabili' => 'Materiale Consumabile',
            'dpi' => 'Dispositivi di Protezione',
            'pulizia' => 'Materiale Pulizia',
            'ufficio' => 'Materiale Ufficio',
            'altro' => 'Altro'
        ];

        $responsabili = User::whereIn('ruolo', ['admin', 'direttivo', 'mezzi'])
                           ->where('attivo', true)
                           ->orderBy('nome')
                           ->get();

        return view('magazzino.create', compact('categorie', 'responsabili'));
    }

    // ===================================
    // STORE - Salva nuovo articolo
    // ===================================
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome_articolo' => 'required|string|max:255',
            'descrizione' => 'nullable|string',
            'codice_articolo' => 'nullable|string|max:255|unique:magazzino,codice_articolo',
            'codice_interno' => 'nullable|string|max:255|unique:magazzino,codice_interno',
            'quantita_attuale' => 'required|integer|min:0',
            'quantita_minima' => 'required|integer|min:0',
            'unita_misura' => 'required|string|max:50',
            'categoria' => 'required|string|max:100',
            'scadenza' => 'nullable|date|after:today',
            'lotto' => 'nullable|string|max:255',
            'prezzo_unitario' => 'nullable|numeric|min:0',
            'fornitore_principale' => 'nullable|string|max:255',
            'ubicazione' => 'nullable|string|max:255',
            'zona_magazzino' => 'required|string|max:100',
            'farmaco' => 'boolean',
            'dispositivo_medico' => 'boolean',
            'responsabile_id' => 'nullable|exists:users,id',
            'note' => 'nullable|string',
            'foto' => 'nullable|image|max:5120'
        ]);

        DB::beginTransaction();
        
        try {
            if (empty($validated['codice_interno'])) {
                $validated['codice_interno'] = $this->generaCodiceInterno($validated['categoria']);
            }

            if ($request->hasFile('foto')) {
                $validated['foto'] = $request->file('foto')->store('magazzino/foto', 'public');
            }

            $articolo = Magazzino::create([
                ...$validated,
                'attivo' => true
            ]);

            if ($validated['quantita_attuale'] > 0) {
                $articolo->registraCarico(
                    $validated['quantita_attuale'],
                    'Carico iniziale alla creazione articolo',
                    auth()->id()
                );
            }

            LogAttivita::create([
                'user_id' => auth()->id(),
                'azione' => 'creazione_articolo_magazzino',
                'modulo' => 'magazzino',
                'risorsa_id' => $articolo->id,
                'descrizione' => "Creato articolo magazzino: {$articolo->nome_articolo}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'data_ora' => now()
            ]);

            if ($validated['responsabile_id']) {
                Notifica::crea([
                    'destinatari' => [$validated['responsabile_id']],
                    'titolo' => 'Nuovo Articolo Assegnato',
                    'messaggio' => "Ti è stato assegnato un nuovo articolo da gestire: {$articolo->nome_articolo}",
                    'tipo' => 'generale'
                ]);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Articolo creato con successo',
                    'redirect' => route('magazzino.show', $articolo->id)
                ]);
            }

            return redirect()->route('magazzino.show', $articolo->id)
                           ->with('success', 'Articolo creato con successo');

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
    // SHOW - Dettagli articolo
    // ===================================
    public function show(Magazzino $magazzino)
    {
        $magazzino->load([
            'movimenti' => function($query) {
                $query->with('user')->orderBy('created_at', 'desc')->limit(20);
            },
            'responsabile',
            'tickets'
        ]);

        $statistiche = [
            'movimenti_totali' => $magazzino->movimenti->count(),
            'carichi_mese' => $magazzino->movimenti()
                                      ->where('tipo', 'carico')
                                      ->whereMonth('created_at', now()->month)
                                      ->sum('quantita'),
            'scarichi_mese' => $magazzino->movimenti()
                                       ->where('tipo', 'scarico')
                                       ->whereMonth('created_at', now()->month)
                                       ->sum('quantita'),
            'valore_stock' => $magazzino->valore_stock,
            'ultimo_movimento' => $magazzino->ultimoMovimento?->created_at?->diffForHumans()
        ];

        $movimentiRecenti = $magazzino->movimenti()
                                    ->with('user')
                                    ->orderBy('created_at', 'desc')
                                    ->limit(10)
                                    ->get();

        return view('magazzino.show', compact('magazzino', 'statistiche', 'movimentiRecenti'));
    }

    // ===================================
    // EDIT - Form modifica articolo
    // ===================================
    public function edit(Magazzino $magazzino)
    {
        $categorie = [
            'farmaci' => 'Farmaci',
            'dispositivi_medici' => 'Dispositivi Medici',
            'consumabili' => 'Materiale Consumabile',
            'dpi' => 'Dispositivi di Protezione',
            'pulizia' => 'Materiale Pulizia',
            'ufficio' => 'Materiale Ufficio',
            'altro' => 'Altro'
        ];

        $responsabili = User::whereIn('ruolo', ['admin', 'direttivo', 'mezzi'])
                           ->where('attivo', true)
                           ->orderBy('nome')
                           ->get();

        return view('magazzino.edit', compact('magazzino', 'categorie', 'responsabili'));
    }

    // ===================================
    // UPDATE - Aggiorna articolo
    // ===================================
    public function update(Request $request, Magazzino $magazzino)
    {
        $validated = $request->validate([
            'nome_articolo' => 'required|string|max:255',
            'descrizione' => 'nullable|string',
            'codice_articolo' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('magazzino')->ignore($magazzino->id)
            ],
            'quantita_minima' => 'required|integer|min:0',
            'unita_misura' => 'required|string|max:50',
            'categoria' => 'required|string|max:100',
            'scadenza' => 'nullable|date',
            'lotto' => 'nullable|string|max:255',
            'prezzo_unitario' => 'nullable|numeric|min:0',
            'fornitore_principale' => 'nullable|string|max:255',
            'ubicazione' => 'nullable|string|max:255',
            'zona_magazzino' => 'required|string|max:100',
            'farmaco' => 'boolean',
            'dispositivo_medico' => 'boolean',
            'responsabile_id' => 'nullable|exists:users,id',
            'note' => 'nullable|string',
            'foto' => 'nullable|image|max:5120'
        ]);

        DB::beginTransaction();
        
        try {
            $responsabilePrecedente = $magazzino->responsabile_id;

            if ($request->hasFile('foto')) {
                if ($magazzino->foto) {
                    Storage::disk('public')->delete($magazzino->foto);
                }
                $validated['foto'] = $request->file('foto')->store('magazzino/foto', 'public');
            }

            $magazzino->update($validated);

            if ($validated['responsabile_id'] && $validated['responsabile_id'] != $responsabilePrecedente) {
                Notifica::crea([
                    'destinatari' => [$validated['responsabile_id']],
                    'titolo' => 'Articolo Assegnato',
                    'messaggio' => "Ti è stato assegnato l'articolo: {$magazzino->nome_articolo}",
                    'tipo' => 'generale'
                ]);
            }

            LogAttivita::create([
                'user_id' => auth()->id(),
                'azione' => 'modifica_articolo_magazzino',
                'modulo' => 'magazzino',
                'risorsa_id' => $magazzino->id,
                'descrizione' => "Modificato articolo magazzino: {$magazzino->nome_articolo}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'data_ora' => now()
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Articolo aggiornato con successo'
                ]);
            }

            return redirect()->route('magazzino.show', $magazzino->id)
                           ->with('success', 'Articolo aggiornato con successo');

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
    // CARICO - Registra carico
    // ===================================
    public function carico(Request $request, Magazzino $magazzino)
    {
        $validated = $request->validate([
            'quantita' => 'required|integer|min:1',
            'motivo' => 'required|string|max:255',
            'prezzo_unitario' => 'nullable|numeric|min:0',
            'data_movimento' => 'required|date|before_or_equal:today'
        ]);

        DB::beginTransaction();
        
        try {
            $movimento = $magazzino->registraCarico(
                $validated['quantita'],
                $validated['motivo'],
                auth()->id(),
                [
                    'prezzo_unitario' => $validated['prezzo_unitario'],
                    'data_movimento' => $validated['data_movimento']
                ]
            );

            if ($validated['prezzo_unitario']) {
                $magazzino->update(['costo_ultimo_acquisto' => $validated['prezzo_unitario']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Carico registrato con successo',
                'nuova_quantita' => $magazzino->fresh()->quantita_attuale,
                'movimento' => [
                    'quantita' => $movimento->quantita,
                    'motivo' => $movimento->motivo,
                    'user' => auth()->user()->nome_completo,
                    'data' => $movimento->created_at->format('d/m/Y H:i')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il carico: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===================================
    // SCARICO - Registra scarico
    // ===================================
    public function scarico(Request $request, Magazzino $magazzino)
    {
        $validated = $request->validate([
            'quantita' => 'required|integer|min:1|max:' . $magazzino->quantita_attuale,
            'motivo' => 'required|string|max:255',
            'data_movimento' => 'required|date|before_or_equal:today'
        ]);

        DB::beginTransaction();
        
        try {
            $movimento = $magazzino->registraScarico(
                $validated['quantita'],
                $validated['motivo'],
                auth()->id(),
                ['data_movimento' => $validated['data_movimento']]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Scarico registrato con successo',
                'nuova_quantita' => $magazzino->fresh()->quantita_attuale,
                'sottoscorta' => $magazzino->fresh()->sottoscorta,
                'movimento' => [
                    'quantita' => $movimento->quantita,
                    'motivo' => $movimento->motivo,
                    'user' => auth()->user()->nome_completo,
                    'data' => $movimento->created_at->format('d/m/Y H:i')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante lo scarico: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===================================
    // SCADENZE - Vista scadenze
    // ===================================
    public function scadenze()
    {
        $articoliInScadenza = Magazzino::whereDate('scadenza', '<=', now()->addDays(60))
                                     ->whereDate('scadenza', '>=', now())
                                     ->where('attivo', true)
                                     ->orderBy('scadenza')
                                     ->get()
                                     ->groupBy(function($articolo) {
                                         $giorni = now()->diffInDays($articolo->scadenza, false);
                                         if ($giorni <= 7) return 'urgenti';
                                         if ($giorni <= 30) return 'vicine';
                                         return 'future';
                                     });

        $articoliScaduti = Magazzino::whereDate('scadenza', '<', now())
                                  ->where('attivo', true)
                                  ->orderBy('scadenza', 'desc')
                                  ->get();

        $statisticheScadenze = [
            'scadenze_urgenti' => $articoliInScadenza->get('urgenti', collect())->count(),
            'scadenze_vicine' => $articoliInScadenza->get('vicine', collect())->count(),
            'scadenze_future' => $articoliInScadenza->get('future', collect())->count(),
            'gia_scaduti' => $articoliScaduti->count()
        ];

        return view('magazzino.scadenze', compact(
            'articoliInScadenza',
            'articoliScaduti',
            'statisticheScadenze'
        ));
    }

    // ===================================
    // EXPORT - Esporta dati magazzino
    // ===================================
    public function export(Request $request)
    {
        $query = Magazzino::with('responsabile');

        if ($request->categoria) {
            $query->where('categoria', $request->categoria);
        }
        
        if ($request->stato === 'sottoscorta') {
            $query->whereRaw('quantita_attuale <= quantita_minima');
        }

        $articoli = $query->orderBy('nome_articolo')->get();

        $filename = 'magazzino_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\""
        ];

        $callback = function() use ($articoli) {
            $file = fopen('php://output', 'w');
            
            fputs($file, "\xEF\xBB\xBF");
            
            fputcsv($file, [
                'Nome Articolo',
                'Categoria',
                'Codice Interno',
                'Quantità Attuale',
                'Quantità Minima',
                'Unità Misura',
                'Prezzo Unitario',
                'Valore Stock',
                'Ubicazione',
                'Scadenza',
                'Responsabile'
            ], ';');

            foreach ($articoli as $articolo) {
                fputcsv($file, [
                    $articolo->nome_articolo,
                    $articolo->categoria,
                    $articolo->codice_interno,
                    $articolo->quantita_attuale,
                    $articolo->quantita_minima,
                    $articolo->unita_misura,
                    $articolo->prezzo_unitario,
                    $articolo->valore_stock,
                    $articolo->ubicazione,
                    $articolo->scadenza?->format('d/m/Y'),
                    $articolo->responsabile?->nome_completo
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ===================================
    // DESTROY - Elimina articolo
    // ===================================
    public function destroy(Magazzino $magazzino)
    {
        if ($magazzino->quantita_attuale > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossibile eliminare articolo con quantità in magazzino'
            ], 422);
        }

        if ($magazzino->movimenti()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossibile eliminare articolo con movimenti registrati'
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $nomeArticolo = $magazzino->nome_articolo;
            
            if ($magazzino->foto) {
                Storage::disk('public')->delete($magazzino->foto);
            }
            
            $magazzino->delete();

            LogAttivita::create([
                'user_id' => auth()->id(),
                'azione' => 'eliminazione_articolo_magazzino',
                'modulo' => 'magazzino',
                'risorsa_id' => $magazzino->id,
                'descrizione' => "Eliminato articolo magazzino: {$nomeArticolo}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'data_ora' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Articolo eliminato con successo'
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

    private function generaCodiceInterno($categoria)
    {
        $prefisso = strtoupper(substr($categoria, 0, 3));
        $ultimoCodice = Magazzino::where('codice_interno', 'like', $prefisso . '%')
                                ->max('codice_interno');

        if ($ultimoCodice) {
            $numero = intval(substr($ultimoCodice, -4)) + 1;
        } else {
            $numero = 1;
        }

        return $prefisso . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }
}