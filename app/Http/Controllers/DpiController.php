<?php

namespace App\Http\Controllers;

use App\Models\Dpi;
use App\Models\AssegnazioneDpi;
use App\Models\Volontario;
use App\Models\User;
use App\Models\LogAttivita;
use App\Models\Notifica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class DpiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:dpi,visualizza')->only(['index', 'show']);
        $this->middleware('permission:dpi,crea')->only(['create', 'store']);
        $this->middleware('permission:dpi,modifica')->only(['edit', 'update', 'assegna', 'restituzione', 'verifica']);
        $this->middleware('permission:dpi,elimina')->only(['destroy']);
    }

    // ===================================
    // INDEX - Lista DPI
    // ===================================
    public function index(Request $request)
    {
        $query = Dpi::with(['assegnazioneAttuale.volontario.user']);

        // Filtri
        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('stato')) {
            $query->where('stato', $request->stato);
        }

        if ($request->filled('disponibilita')) {
            switch ($request->disponibilita) {
                case 'disponibili':
                    $query->where('disponibile', true)
                          ->where('in_manutenzione', false)
                          ->whereIn('stato', ['nuovo', 'buono']);
                    break;
                case 'assegnati':
                    $query->whereHas('assegnazioneAttuale');
                    break;
                case 'manutenzione':
                    $query->where('in_manutenzione', true);
                    break;
                case 'da_verificare':
                    $query->whereDate('prossima_verifica', '<=', now());
                    break;
            }
        }

        if ($request->filled('taglia')) {
            $query->where('taglia', $request->taglia);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                  ->orWhere('descrizione', 'like', "%{$search}%")
                  ->orWhere('codice_dpi', 'like', "%{$search}%")
                  ->orWhere('marca', 'like', "%{$search}%")
                  ->orWhere('modello', 'like', "%{$search}%");
            });
        }

        $sortField = $request->get('sort', 'nome');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $dpi = $query->paginate(20);

        $statistiche = [
            'totale_dpi' => Dpi::count(),
            'dpi_disponibili' => Dpi::where('disponibile', true)->count(),
            'dpi_assegnati' => Dpi::whereHas('assegnazioneAttuale')->count(),
            'dpi_in_manutenzione' => Dpi::where('in_manutenzione', true)->count(),
            'dpi_in_scadenza' => Dpi::whereDate('scadenza', '<=', now()->addDays(30))
                                   ->whereDate('scadenza', '>=', now())
                                   ->count(),
            'dpi_scaduti' => Dpi::whereDate('scadenza', '<', now())->count(),
            'dpi_da_verificare' => Dpi::whereDate('prossima_verifica', '<=', now())->count()
        ];

        $categorie = Dpi::select('categoria')
                        ->distinct()
                        ->orderBy('categoria')
                        ->pluck('categoria', 'categoria');

        $taglie = Dpi::select('taglia')
                     ->whereNotNull('taglia')
                     ->distinct()
                     ->orderBy('taglia')
                     ->pluck('taglia', 'taglia');

        if ($request->ajax()) {
            return response()->json([
                'html' => view('dpi.partials.table', compact('dpi'))->render(),
                'pagination' => $dpi->links()->render()
            ]);
        }

        return view('dpi.index', compact('dpi', 'statistiche', 'categorie', 'taglie'));
    }

    // ===================================
    // CREATE - Form creazione DPI
    // ===================================
    public function create()
    {
        $categorie = [
            'protezione_testa' => 'Protezione Testa',
            'protezione_occhi' => 'Protezione Occhi',
            'protezione_respiratoria' => 'Protezione Respiratoria',
            'protezione_mani' => 'Protezione Mani',
            'protezione_piedi' => 'Protezione Piedi',
            'protezione_corpo' => 'Protezione Corpo',
            'protezione_cadute' => 'Protezione Cadute',
            'divise' => 'Divise e Abbigliamento',
            'altro' => 'Altro'
        ];

        return view('dpi.create', compact('categorie'));
    }

    // ===================================
    // STORE - Salva nuovo DPI
    // ===================================
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'descrizione' => 'nullable|string',
            'codice_dpi' => 'nullable|string|max:255|unique:dpi,codice_dpi',
            'categoria' => 'required|in:protezione_testa,protezione_occhi,protezione_respiratoria,protezione_mani,protezione_piedi,protezione_corpo,protezione_cadute,divise,altro',
            'taglia' => 'nullable|string|max:10',
            'colore' => 'nullable|string|max:50',
            'materiale' => 'nullable|string|max:100',
            'marca' => 'nullable|string|max:100',
            'modello' => 'nullable|string|max:100',
            'certificazione_ce' => 'nullable|string|max:100',
            'normative_riferimento' => 'nullable|array',
            'classe_protezione' => 'nullable|string|max:50',
            'data_certificazione' => 'nullable|date',
            'scadenza_certificazione' => 'nullable|date|after:data_certificazione',
            'data_acquisto' => 'nullable|date|before_or_equal:today',
            'scadenza' => 'nullable|date|after:today',
            'durata_mesi' => 'nullable|integer|min:1|max:120',
            'max_utilizzi' => 'nullable|integer|min:1',
            'costo_acquisto' => 'nullable|numeric|min:0',
            'fornitore' => 'nullable|string|max:255',
            'numero_fattura' => 'nullable|string|max:100',
            'ubicazione' => 'nullable|string|max:255',
            'armadio_scaffale' => 'nullable|string|max:100',
            'istruzioni_uso' => 'nullable|string',
            'istruzioni_manutenzione' => 'nullable|string',
            'istruzioni_pulizia' => 'nullable|string',
            'note' => 'nullable|string',
            'foto' => 'nullable|image|max:5120'
        ]);

        DB::beginTransaction();
        
        try {
            if (empty($validated['codice_dpi'])) {
                $validated['codice_dpi'] = $this->generaCodiceDpi($validated['categoria']);
            }

            if (!$validated['scadenza'] && $validated['durata_mesi'] && $validated['data_acquisto']) {
                $validated['scadenza'] = Carbon::parse($validated['data_acquisto'])
                                               ->addMonths($validated['durata_mesi']);
            }

            if ($request->hasFile('foto')) {
                $validated['foto'] = $request->file('foto')->store('dpi/foto', 'public');
            }

            $dpi = Dpi::create([
                ...$validated,
                'stato' => 'nuovo',
                'disponibile' => true,
                'in_manutenzione' => false,
                'utilizzi_effettuati' => 0
            ]);

            LogAttivita::create([
                'user_id' => auth()->id(),
                'azione' => 'creazione_dpi',
                'modulo' => 'dpi',
                'risorsa_id' => $dpi->id,
                'descrizione' => "Creato DPI: {$dpi->nome} ({$dpi->codice_dpi})",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'data_ora' => now()
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'DPI creato con successo',
                    'redirect' => route('dpi.show', $dpi->id)
                ]);
            }

            return redirect()->route('dpi.show', $dpi->id)
                           ->with('success', 'DPI creato con successo');

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
    // SHOW - Dettagli DPI
    // ===================================
    public function show(Dpi $dpi)
    {
        $dpi->load([
            'assegnazioni' => function($query) {
                $query->with(['volontario.user'])
                      ->orderBy('data_assegnazione', 'desc');
            },
            'assegnazioneAttuale.volontario.user',
            'tickets'
        ]);

        $statistiche = [
            'utilizzi_effettuati' => $dpi->utilizzi_effettuati,
            'utilizzi_residui' => $dpi->utilizzi_residui,
            'percentuale_utilizzo' => $dpi->percentuale_utilizzo,
            'eta_dpi' => $dpi->eta_dpi,
            'giorni_residui' => $dpi->giorni_residui,
            'assegnazioni_totali' => $dpi->assegnazioni->count(),
            'disponibile_per_assegnazione' => $dpi->disponibile_per_assegnazione
        ];

        $storicoAssegnazioni = $dpi->assegnazioni()
                                  ->with(['volontario.user'])
                                  ->orderBy('data_assegnazione', 'desc')
                                  ->limit(10)
                                  ->get();

        $volontariDisponibili = [];
        if ($dpi->disponibile_per_assegnazione) {
            $volontariDisponibili = Volontario::with('user')
                                             ->where('attivo', true)
                                             ->whereHas('user', function($query) {
                                                 $query->where('attivo', true);
                                             })
                                             ->orderBy('user.cognome')
                                             ->get();
        }

        return view('dpi.show', compact(
            'dpi', 
            'statistiche', 
            'storicoAssegnazioni', 
            'volontariDisponibili'
        ));
    }

    // ===================================
    // EDIT - Form modifica DPI
    // ===================================
    public function edit(Dpi $dpi)
    {
        $categorie = [
            'protezione_testa' => 'Protezione Testa',
            'protezione_occhi' => 'Protezione Occhi',
            'protezione_respiratoria' => 'Protezione Respiratoria',
            'protezione_mani' => 'Protezione Mani',
            'protezione_piedi' => 'Protezione Piedi',
            'protezione_corpo' => 'Protezione Corpo',
            'protezione_cadute' => 'Protezione Cadute',
            'divise' => 'Divise e Abbigliamento',
            'altro' => 'Altro'
        ];

        return view('dpi.edit', compact('dpi', 'categorie'));
    }

    // ===================================
    // UPDATE - Aggiorna DPI
    // ===================================
    public function update(Request $request, Dpi $dpi)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'descrizione' => 'nullable|string',
            'categoria' => 'required|in:protezione_testa,protezione_occhi,protezione_respiratoria,protezione_mani,protezione_piedi,protezione_corpo,protezione_cadute,divise,altro',
            'taglia' => 'nullable|string|max:10',
            'colore' => 'nullable|string|max:50',
            'materiale' => 'nullable|string|max:100',
            'marca' => 'nullable|string|max:100',
            'modello' => 'nullable|string|max:100',
            'stato' => 'required|in:nuovo,buono,usato,da_controllare,da_sostituire,dismesso',
            'in_manutenzione' => 'boolean',
            'scadenza' => 'nullable|date',
            'costo_acquisto' => 'nullable|numeric|min:0',
            'ubicazione' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'foto' => 'nullable|image|max:5120'
        ]);

        DB::beginTransaction();
        
        try {
            if ($request->hasFile('foto')) {
                if ($dpi->foto) {
                    Storage::disk('public')->delete($dpi->foto);
                }
                $validated['foto'] = $request->file('foto')->store('dpi/foto', 'public');
            }

            if (in_array($validated['stato'], ['da_sostituire', 'dismesso']) || $validated['in_manutenzione']) {
                $validated['disponibile'] = false;
            } elseif (in_array($validated['stato'], ['nuovo', 'buono']) && !$validated['in_manutenzione']) {
                $validated['disponibile'] = true;
            }

            $dpi->update($validated);

            LogAttivita::create([
                'user_id' => auth()->id(),
                'azione' => 'modifica_dpi',
                'modulo' => 'dpi',
                'risorsa_id' => $dpi->id,
                'descrizione' => "Modificato DPI: {$dpi->nome}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'data_ora' => now()
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'DPI aggiornato con successo'
                ]);
            }

            return redirect()->route('dpi.show', $dpi->id)
                           ->with('success', 'DPI aggiornato con successo');

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
    // ASSEGNA - Assegna DPI a volontario
    // ===================================
    public function assegna(Request $request, Dpi $dpi)
    {
        $validated = $request->validate([
            'volontario_id' => 'required|exists:volontari,id',
            'motivo_assegnazione' => 'nullable|string|max:255',
            'formazione_effettuata' => 'boolean',
            'ricevuta_firmata' => 'boolean',
            'note' => 'nullable|string'
        ]);

        if (!$dpi->disponibile_per_assegnazione) {
            return response()->json([
                'success' => false,
                'message' => 'Il DPI non è disponibile per l\'assegnazione'
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $assegnazione = $dpi->assegnaA(
                $validated['volontario_id'],
                auth()->id(),
                [
                    'motivo_assegnazione' => $validated['motivo_assegnazione'],
                    'formazione_effettuata' => $validated['formazione_effettuata'] ?? false,
                    'ricevuta_firmata' => $validated['ricevuta_firmata'] ?? false,
                    'note' => $validated['note']
                ]
            );

            $volontario = Volontario::find($validated['volontario_id']);

            Notifica::crea([
                'destinatari' => [$volontario->user_id],
                'titolo' => 'DPI Assegnato',
                'messaggio' => "Ti è stato assegnato il DPI: {$dpi->nome} ({$dpi->codice_dpi})",
                'tipo' => 'generale'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'DPI assegnato con successo',
                'assegnazione' => [
                    'volontario' => $volontario->user->nome_completo,
                    'data' => $assegnazione->data_assegnazione->format('d/m/Y')
                ]
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
    // RESTITUZIONE - Gestisce restituzione DPI
    // ===================================
    public function restituzione(Request $request, Dpi $dpi)
    {
        $validated = $request->validate([
            'stato_restituzione' => 'required|in:buono,usato,danneggiato,perso,non_restituito',
            'motivo_restituzione' => 'nullable|string|max:255',
            'note' => 'nullable|string'
        ]);

        $assegnazione = $dpi->assegnazioneAttuale;
        if (!$assegnazione) {
            return response()->json([
                'success' => false,
                'message' => 'Nessuna assegnazione attiva trovata per questo DPI'
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $assegnazione = $dpi->registraRestituzione(
                $validated['stato_restituzione'],
                $validated['motivo_restituzione'],
                ['note' => $validated['note']]
            );

            Notifica::crea([
                'destinatari' => [$assegnazione->volontario->user_id],
                'titolo' => 'DPI Restituito',
                'messaggio' => "La restituzione del DPI {$dpi->nome} è stata registrata",
                'tipo' => 'generale'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Restituzione registrata con successo',
                'nuovo_stato' => $dpi->fresh()->stato_label,
                'disponibile' => $dpi->fresh()->disponibile
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la restituzione: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===================================
    // VERIFICA - Registra verifica DPI
    // ===================================
    public function verifica(Request $request, Dpi $dpi)
    {
        $validated = $request->validate([
            'esito' => 'required|boolean',
            'note_verifica' => 'nullable|string',
            'prossima_verifica' => 'nullable|date|after:today'
        ]);

        DB::beginTransaction();
        
        try {
            $dpi->registraVerifica(
                $validated['esito'],
                $validated['note_verifica'],
                $validated['prossima_verifica']
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Verifica registrata con successo',
                'esito' => $validated['esito'] ? 'CONFORME' : 'NON CONFORME',
                'nuovo_stato' => $dpi->fresh()->stato_label,
                'prossima_verifica' => $dpi->fresh()->prossima_verifica?->format('d/m/Y')
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la verifica: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===================================
    // SCADENZE - Vista scadenze DPI
    // ===================================
    public function scadenze()
    {
        $dpiInScadenza = Dpi::whereDate('scadenza', '<=', now()->addDays(60))
                           ->whereDate('scadenza', '>=', now())
                           ->orderBy('scadenza')
                           ->get()
                           ->groupBy(function($dpi) {
                               $giorni = now()->diffInDays($dpi->scadenza, false);
                               if ($giorni <= 7) return 'urgenti';
                               if ($giorni <= 30) return 'vicine';
                               return 'future';
                           });

        $dpiDaVerificare = Dpi::whereDate('prossima_verifica', '<=', now())
                              ->where('disponibile', true)
                              ->orderBy('prossima_verifica')
                              ->get();

        $dpiScaduti = Dpi::whereDate('scadenza', '<', now())
                         ->orderBy('scadenza', 'desc')
                         ->get();

        $statisticheScadenze = [
            'scadenze_urgenti' => $dpiInScadenza->get('urgenti', collect())->count(),
            'scadenze_vicine' => $dpiInScadenza->get('vicine', collect())->count(),
            'da_verificare' => $dpiDaVerificare->count(),
            'gia_scaduti' => $dpiScaduti->count()
        ];

        return view('dpi.scadenze', compact(
            'dpiInScadenza',
            'dpiDaVerificare',
            'dpiScaduti',
            'statisticheScadenze'
        ));
    }

    // ===================================
    // EXPORT - Esporta dati DPI
    // ===================================
    public function export(Request $request)
    {
        $query = Dpi::with(['assegnazioneAttuale.volontario.user']);

        if ($request->categoria) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->stato) {
            $query->where('stato', $request->stato);
        }

        $dpi = $query->orderBy('nome')->get();

        $filename = 'dpi_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\""
        ];

        $callback = function() use ($dpi) {
            $file = fopen('php://output', 'w');
            
            fputs($file, "\xEF\xBB\xBF");
            
            fputcsv($file, [
                'Codice DPI',
                'Nome',
                'Categoria',
                'Taglia',
                'Marca',
                'Modello',
                'Stato',
                'Assegnato A',
                'Data Acquisto',
                'Scadenza',
                'Prossima Verifica',
                'Utilizzi Effettuati'
            ], ';');

            foreach ($dpi as $item) {
                fputcsv($file, [
                    $item->codice_dpi,
                    $item->nome,
                    $item->categoria_label,
                    $item->taglia,
                    $item->marca,
                    $item->modello,
                    $item->stato_label,
                    $item->assegnazioneAttuale?->volontario->user->nome_completo,
                    $item->data_acquisto?->format('d/m/Y'),
                    $item->scadenza?->format('d/m/Y'),
                    $item->prossima_verifica?->format('d/m/Y'),
                    $item->utilizzi_effettuati
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ===================================
    // DESTROY - Elimina DPI
    // ===================================
    public function destroy(Dpi $dpi)
    {
        if ($dpi->assegnazioneAttuale) {
            return response()->json([
                'success' => false,
                'message' => 'Impossibile eliminare DPI attualmente assegnato'
            ], 422);
        }

        if ($dpi->assegnazioni()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossibile eliminare DPI con storico assegnazioni'
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $nomeDpi = $dpi->nome;
            $codiceDpi = $dpi->codice_dpi;
            
            if ($dpi->foto) {
                Storage::disk('public')->delete($dpi->foto);
            }
            
            $dpi->delete();

            LogAttivita::create([
                'user_id' => auth()->id(),
                'azione' => 'eliminazione_dpi',
                'modulo' => 'dpi',
                'risorsa_id' => $dpi->id,
                'descrizione' => "Eliminato DPI: {$nomeDpi} ({$codiceDpi})",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'data_ora' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'DPI eliminato con successo'
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

    private function generaCodiceDpi($categoria)
    {
        $prefisso = strtoupper(substr($categoria, 0, 3));
        $ultimoCodice = Dpi::where('codice_dpi', 'like', $prefisso . '%')
                           ->max('codice_dpi');

        if ($ultimoCodice) {
            $numero = intval(substr($ultimoCodice, -4)) + 1;
        } else {
            $numero = 1;
        }

        return $prefisso . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }
}