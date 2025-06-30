<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Volontario;
use App\Models\Mezzo;
use App\Models\Magazzino;
use App\Models\Dpi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EventiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('check.permission:eventi.view')->only(['index', 'show']);
        $this->middleware('check.permission:eventi.create')->only(['create', 'store']);
        $this->middleware('check.permission:eventi.edit')->only(['edit', 'update']);
        $this->middleware('check.permission:eventi.delete')->only(['destroy']);
    }

    /**
     * Display a listing of eventi
     */
    public function index(Request $request)
    {
        try {
            $query = Evento::with(['volontari', 'mezzi', 'createdBy']);

            // Filtri di ricerca
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('titolo', 'like', "%{$search}%")
                      ->orWhere('descrizione', 'like', "%{$search}%")
                      ->orWhere('luogo', 'like', "%{$search}%");
                });
            }

            // Filtro per tipo evento
            if ($request->filled('tipo')) {
                $query->where('tipo_evento', $request->tipo);
            }

            // Filtro per stato
            if ($request->filled('stato')) {
                $query->where('stato', $request->stato);
            }

            // Filtro per periodo
            if ($request->filled('data_da')) {
                $query->where('data_inizio', '>=', $request->data_da);
            }
            if ($request->filled('data_a')) {
                $query->where('data_fine', '<=', $request->data_a);
            }

            // Ordinamento
            $sortField = $request->get('sort', 'data_inizio');
            $sortDirection = $request->get('direction', 'asc');
            $query->orderBy($sortField, $sortDirection);

            $eventi = $query->paginate(15)->withQueryString();

            // Statistiche per dashboard
            $stats = [
                'totali' => Evento::count(),
                'programmati' => Evento::where('stato', 'programmato')->count(),
                'in_corso' => Evento::where('stato', 'in_corso')->count(),
                'completati' => Evento::where('stato', 'completato')->count(),
                'questo_mese' => Evento::whereMonth('data_inizio', now()->month)->count()
            ];

            return view('eventi.index', compact('eventi', 'stats'));

        } catch (\Exception $e) {
            Log::error('Errore nel caricamento eventi: ' . $e->getMessage());
            return back()->with('error', 'Errore nel caricamento degli eventi');
        }
    }

    /**
     * Show the form for creating a new evento
     */
    public function create()
    {
        try {
            $volontari = Volontario::where('stato', 'attivo')
                                 ->orderBy('cognome')
                                 ->get();

            $mezzi = Mezzo::where('stato', 'disponibile')
                         ->orderBy('targa')
                         ->get();

            $materiali = Magazzino::where('quantita', '>', 0)
                                 ->orderBy('nome')
                                 ->get();

            $dpi = Dpi::where('quantita_disponibile', '>', 0)
                     ->orderBy('nome')
                     ->get();

            $tipi_evento = [
                'formazione' => 'Formazione',
                'emergenza' => 'Emergenza',
                'esercitazione' => 'Esercitazione',
                'evento_pubblico' => 'Evento Pubblico',
                'servizio' => 'Servizio',
                'altro' => 'Altro'
            ];

            return view('eventi.create', compact('volontari', 'mezzi', 'materiali', 'dpi', 'tipi_evento'));

        } catch (\Exception $e) {
            Log::error('Errore nel caricamento form creazione evento: ' . $e->getMessage());
            return back()->with('error', 'Errore nel caricamento del modulo');
        }
    }

    /**
     * Store a newly created evento
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titolo' => 'required|string|max:255',
            'descrizione' => 'required|string',
            'tipo_evento' => 'required|in:formazione,emergenza,esercitazione,evento_pubblico,servizio,altro',
            'data_inizio' => 'required|date|after_or_equal:today',
            'data_fine' => 'required|date|after_or_equal:data_inizio',
            'luogo' => 'required|string|max:255',
            'indirizzo' => 'nullable|string|max:500',
            'coordinate_lat' => 'nullable|numeric|between:-90,90',
            'coordinate_lng' => 'nullable|numeric|between:-180,180',
            'max_partecipanti' => 'nullable|integer|min:1',
            'note' => 'nullable|string',
            'volontari' => 'nullable|array',
            'volontari.*' => 'exists:volontari,id',
            'mezzi' => 'nullable|array',
            'mezzi.*' => 'exists:mezzi,id',
            'materiali' => 'nullable|array',
            'materiali.*.id' => 'exists:magazzino,id',
            'materiali.*.quantita' => 'integer|min:1',
            'dpi' => 'nullable|array',
            'dpi.*.id' => 'exists:dpi,id',
            'dpi.*.quantita' => 'integer|min:1'
        ]);

        DB::beginTransaction();

        try {
            // Verifica disponibilità volontari
            if ($request->filled('volontari')) {
                $volontariOccupati = $this->checkVolontariDisponibilita(
                    $request->volontari,
                    $request->data_inizio,
                    $request->data_fine
                );

                if (!empty($volontariOccupati)) {
                    $nomi = Volontario::whereIn('id', $volontariOccupati)
                                    ->pluck('nome', 'cognome')
                                    ->map(fn($nome, $cognome) => "$cognome $nome")
                                    ->join(', ');
                    
                    return back()->withInput()
                                ->with('error', "I seguenti volontari non sono disponibili nel periodo selezionato: $nomi");
                }
            }

            // Verifica disponibilità mezzi
            if ($request->filled('mezzi')) {
                $mezziOccupati = $this->checkMezziDisponibilita(
                    $request->mezzi,
                    $request->data_inizio,
                    $request->data_fine
                );

                if (!empty($mezziOccupati)) {
                    $targhe = Mezzo::whereIn('id', $mezziOccupati)->pluck('targa')->join(', ');
                    return back()->withInput()
                                ->with('error', "I seguenti mezzi non sono disponibili nel periodo selezionato: $targhe");
                }
            }

            // Verifica disponibilità materiali
            if ($request->filled('materiali')) {
                foreach ($request->materiali as $materiale) {
                    $item = Magazzino::find($materiale['id']);
                    if ($item->quantita < $materiale['quantita']) {
                        return back()->withInput()
                                    ->with('error', "Quantità insufficiente per: {$item->nome}. Disponibili: {$item->quantita}");
                    }
                }
            }

            // Verifica disponibilità DPI
            if ($request->filled('dpi')) {
                foreach ($request->dpi as $dpi) {
                    $item = Dpi::find($dpi['id']);
                    if ($item->quantita_disponibile < $dpi['quantita']) {
                        return back()->withInput()
                                    ->with('error', "Quantità insufficiente per DPI: {$item->nome}. Disponibili: {$item->quantita_disponibile}");
                    }
                }
            }

            // Crea l'evento
            $evento = Evento::create([
                'titolo' => $validated['titolo'],
                'descrizione' => $validated['descrizione'],
                'tipo_evento' => $validated['tipo_evento'],
                'data_inizio' => $validated['data_inizio'],
                'data_fine' => $validated['data_fine'],
                'luogo' => $validated['luogo'],
                'indirizzo' => $validated['indirizzo'],
                'coordinate_lat' => $validated['coordinate_lat'],
                'coordinate_lng' => $validated['coordinate_lng'],
                'max_partecipanti' => $validated['max_partecipanti'],
                'note' => $validated['note'],
                'stato' => 'programmato',
                'created_by' => Auth::id()
            ]);

            // Assegna volontari
            if ($request->filled('volontari')) {
                $evento->volontari()->attach($request->volontari, [
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // Assegna mezzi
            if ($request->filled('mezzi')) {
                $evento->mezzi()->attach($request->mezzi, [
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // Gestione materiali dal magazzino
            if ($request->filled('materiali')) {
                foreach ($request->materiali as $materiale) {
                    $item = Magazzino::find($materiale['id']);
                    
                    // Registra l'utilizzo
                    $evento->materiali()->attach($materiale['id'], [
                        'quantita_utilizzata' => $materiale['quantita'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    // Aggiorna disponibilità (opzionale - dipende dalla logica di business)
                    // $item->decrement('quantita', $materiale['quantita']);
                }
            }

            // Gestione DPI
            if ($request->filled('dpi')) {
                foreach ($request->dpi as $dpi) {
                    $item = Dpi::find($dpi['id']);
                    
                    // Registra l'assegnazione
                    $evento->dpi()->attach($dpi['id'], [
                        'quantita_assegnata' => $dpi['quantita'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    // Aggiorna disponibilità
                    $item->decrement('quantita_disponibile', $dpi['quantita']);
                }
            }

            DB::commit();

            // Log dell'attività
            Log::info("Evento creato: {$evento->titolo}", [
                'evento_id' => $evento->id,
                'user_id' => Auth::id(),
                'volontari_count' => count($request->volontari ?? []),
                'mezzi_count' => count($request->mezzi ?? [])
            ]);

            return redirect()->route('eventi.show', $evento)
                           ->with('success', 'Evento creato con successo!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore nella creazione evento: ' . $e->getMessage());
            return back()->withInput()
                        ->with('error', 'Errore nella creazione dell\'evento: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified evento
     */
    public function show(Evento $evento)
    {
        try {
            $evento->load([
                'volontari' => function($query) {
                    $query->orderBy('cognome');
                },
                'mezzi' => function($query) {
                    $query->orderBy('targa');
                },
                'materiali',
                'dpi',
                'createdBy'
            ]);

            // Calcola statistiche evento
            $stats = [
                'volontari_totali' => $evento->volontari->count(),
                'mezzi_totali' => $evento->mezzi->count(),
                'durata_ore' => Carbon::parse($evento->data_inizio)->diffInHours(Carbon::parse($evento->data_fine)),
                'giorni_mancanti' => now()->diffInDays(Carbon::parse($evento->data_inizio), false)
            ];

            return view('eventi.show', compact('evento', 'stats'));

        } catch (\Exception $e) {
            Log::error('Errore nel caricamento evento: ' . $e->getMessage());
            return back()->with('error', 'Errore nel caricamento dell\'evento');
        }
    }

    /**
     * Show the form for editing the specified evento
     */
    public function edit(Evento $evento)
    {
        try {
            $evento->load(['volontari', 'mezzi', 'materiali', 'dpi']);

            $volontari = Volontario::where('stato', 'attivo')
                                 ->orderBy('cognome')
                                 ->get();

            $mezzi = Mezzo::where('stato', 'disponibile')
                         ->orderBy('targa')
                         ->get();

            $materiali = Magazzino::where('quantita', '>', 0)
                                 ->orderBy('nome')
                                 ->get();

            $dpi = Dpi::where('quantita_disponibile', '>', 0)
                     ->orderBy('nome')
                     ->get();

            $tipi_evento = [
                'formazione' => 'Formazione',
                'emergenza' => 'Emergenza',
                'esercitazione' => 'Esercitazione',
                'evento_pubblico' => 'Evento Pubblico',
                'servizio' => 'Servizio',
                'altro' => 'Altro'
            ];

            return view('eventi.edit', compact('evento', 'volontari', 'mezzi', 'materiali', 'dpi', 'tipi_evento'));

        } catch (\Exception $e) {
            Log::error('Errore nel caricamento form modifica evento: ' . $e->getMessage());
            return back()->with('error', 'Errore nel caricamento del modulo di modifica');
        }
    }

    /**
     * Update the specified evento
     */
    public function update(Request $request, Evento $evento)
    {
        $validated = $request->validate([
            'titolo' => 'required|string|max:255',
            'descrizione' => 'required|string',
            'tipo_evento' => 'required|in:formazione,emergenza,esercitazione,evento_pubblico,servizio,altro',
            'data_inizio' => 'required|date',
            'data_fine' => 'required|date|after_or_equal:data_inizio',
            'luogo' => 'required|string|max:255',
            'indirizzo' => 'nullable|string|max:500',
            'coordinate_lat' => 'nullable|numeric|between:-90,90',
            'coordinate_lng' => 'nullable|numeric|between:-180,180',
            'max_partecipanti' => 'nullable|integer|min:1',
            'note' => 'nullable|string',
            'stato' => 'required|in:programmato,confermato,in_corso,completato,cancellato',
            'volontari' => 'nullable|array',
            'volontari.*' => 'exists:volontari,id',
            'mezzi' => 'nullable|array',
            'mezzi.*' => 'exists:mezzi,id'
        ]);

        DB::beginTransaction();

        try {
            // Verifica disponibilità solo se le date sono cambiate
            $dateChanged = $evento->data_inizio != $validated['data_inizio'] || 
                          $evento->data_fine != $validated['data_fine'];

            if ($dateChanged && $request->filled('volontari')) {
                $volontariOccupati = $this->checkVolontariDisponibilita(
                    $request->volontari,
                    $request->data_inizio,
                    $request->data_fine,
                    $evento->id
                );

                if (!empty($volontariOccupati)) {
                    $nomi = Volontario::whereIn('id', $volontariOccupati)
                                    ->pluck('nome', 'cognome')
                                    ->map(fn($nome, $cognome) => "$cognome $nome")
                                    ->join(', ');
                    
                    return back()->withInput()
                                ->with('error', "I seguenti volontari non sono disponibili nel nuovo periodo: $nomi");
                }
            }

            if ($dateChanged && $request->filled('mezzi')) {
                $mezziOccupati = $this->checkMezziDisponibilita(
                    $request->mezzi,
                    $request->data_inizio,
                    $request->data_fine,
                    $evento->id
                );

                if (!empty($mezziOccupati)) {
                    $targhe = Mezzo::whereIn('id', $mezziOccupati)->pluck('targa')->join(', ');
                    return back()->withInput()
                                ->with('error', "I seguenti mezzi non sono disponibili nel nuovo periodo: $targhe");
                }
            }

            // Aggiorna l'evento
            $evento->update($validated);

            // Aggiorna associazioni volontari
            if ($request->has('volontari')) {
                $evento->volontari()->sync($request->volontari);
            }

            // Aggiorna associazioni mezzi
            if ($request->has('mezzi')) {
                $evento->mezzi()->sync($request->mezzi);
            }

            DB::commit();

            Log::info("Evento aggiornato: {$evento->titolo}", [
                'evento_id' => $evento->id,
                'user_id' => Auth::id(),
                'changes' => $evento->getChanges()
            ]);

            return redirect()->route('eventi.show', $evento)
                           ->with('success', 'Evento aggiornato con successo!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore nell\'aggiornamento evento: ' . $e->getMessage());
            return back()->withInput()
                        ->with('error', 'Errore nell\'aggiornamento dell\'evento: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified evento
     */
    public function destroy(Evento $evento)
    {
        try {
            // Verifica che l'evento possa essere eliminato
            if ($evento->stato === 'in_corso') {
                return back()->with('error', 'Non è possibile eliminare un evento in corso');
            }

            DB::beginTransaction();

            // Ripristina disponibilità DPI se necessario
            foreach ($evento->dpi as $dpi) {
                $dpiItem = Dpi::find($dpi->id);
                $dpiItem->increment('quantita_disponibile', $dpi->pivot->quantita_assegnata);
            }

            // Rimuovi tutte le associazioni
            $evento->volontari()->detach();
            $evento->mezzi()->detach();
            $evento->materiali()->detach();
            $evento->dpi()->detach();

            $titoloEvento = $evento->titolo;
            $evento->delete();

            DB::commit();

            Log::info("Evento eliminato: $titoloEvento", [
                'evento_id' => $evento->id,
                'user_id' => Auth::id()
            ]);

            return redirect()->route('eventi.index')
                           ->with('success', 'Evento eliminato con successo');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore nell\'eliminazione evento: ' . $e->getMessage());
            return back()->with('error', 'Errore nell\'eliminazione dell\'evento');
        }
    }

    /**
     * Verifica disponibilità volontari nel periodo specificato
     */
    private function checkVolontariDisponibilita($volontariIds, $dataInizio, $dataFine, $eventoEscluso = null)
    {
        $query = DB::table('evento_volontario')
            ->join('eventi', 'evento_volontario.evento_id', '=', 'eventi.id')
            ->whereIn('evento_volontario.volontario_id', $volontariIds)
            ->where('eventi.stato', '!=', 'cancellato')
            ->where(function($q) use ($dataInizio, $dataFine) {
                $q->whereBetween('eventi.data_inizio', [$dataInizio, $dataFine])
                  ->orWhereBetween('eventi.data_fine', [$dataInizio, $dataFine])
                  ->orWhere(function($subQ) use ($dataInizio, $dataFine) {
                      $subQ->where('eventi.data_inizio', '<=', $dataInizio)
                           ->where('eventi.data_fine', '>=', $dataFine);
                  });
            });

        if ($eventoEscluso) {
            $query->where('eventi.id', '!=', $eventoEscluso);
        }

        return $query->pluck('evento_volontario.volontario_id')->toArray();
    }

    /**
     * Verifica disponibilità mezzi nel periodo specificato
     */
    private function checkMezziDisponibilita($mezziIds, $dataInizio, $dataFine, $eventoEscluso = null)
    {
        $query = DB::table('evento_mezzo')
            ->join('eventi', 'evento_mezzo.evento_id', '=', 'eventi.id')
            ->whereIn('evento_mezzo.mezzo_id', $mezziIds)
            ->where('eventi.stato', '!=', 'cancellato')
            ->where(function($q) use ($dataInizio, $dataFine) {
                $q->whereBetween('eventi.data_inizio', [$dataInizio, $dataFine])
                  ->orWhereBetween('eventi.data_fine', [$dataInizio, $dataFine])
                  ->orWhere(function($subQ) use ($dataInizio, $dataFine) {
                      $subQ->where('eventi.data_inizio', '<=', $dataInizio)
                           ->where('eventi.data_fine', '>=', $dataFine);
                  });
            });

        if ($eventoEscluso) {
            $query->where('eventi.id', '!=', $eventoEscluso);
        }

        return $query->pluck('evento_mezzo.mezzo_id')->toArray();
    }

    /**
     * Cambia lo stato di un evento
     */
    public function changeStatus(Request $request, Evento $evento)
    {
        $validated = $request->validate([
            'stato' => 'required|in:programmato,confermato,in_corso,completato,cancellato',
            'note_stato' => 'nullable|string|max:500'
        ]);

        try {
            $vecchioStato = $evento->stato;
            
            $evento->update([
                'stato' => $validated['stato'],
                'note_stato' => $validated['note_stato'] ?? null
            ]);

            Log::info("Stato evento cambiato da $vecchioStato a {$validated['stato']}", [
                'evento_id' => $evento->id,
                'user_id' => Auth::id()
            ]);

            return back()->with('success', 'Stato evento aggiornato con successo');

        } catch (\Exception $e) {
            Log::error('Errore nel cambio stato evento: ' . $e->getMessage());
            return back()->with('error', 'Errore nell\'aggiornamento dello stato');
        }
    }

    /**
     * Duplica un evento esistente
     */
    public function duplicate(Evento $evento)
    {
        try {
            DB::beginTransaction();

            $nuovoEvento = $evento->replicate();
            $nuovoEvento->titolo = $evento->titolo . ' (Copia)';
            $nuovoEvento->stato = 'programmato';
            $nuovoEvento->data_inizio = null;
            $nuovoEvento->data_fine = null;
            $nuovoEvento->created_by = Auth::id();
            $nuovoEvento->save();

            // Copia le relazioni (senza le date - dovranno essere reimpostate)
            // Non copiamo volontari e mezzi per evitare conflitti di disponibilità

            DB::commit();

            return redirect()->route('eventi.edit', $nuovoEvento)
                           ->with('success', 'Evento duplicato! Imposta le nuove date e assegna le risorse.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore nella duplicazione evento: ' . $e->getMessage());
            return back()->with('error', 'Errore nella duplicazione dell\'evento');
        }
    }

    /**
     * Mostra il calendario degli eventi
     */
    public function calendario(Request $request)
    {
        try {
            $eventi = Evento::select('id', 'titolo', 'data_inizio', 'data_fine', 'stato', 'tipo_evento')
                           ->where('stato', '!=', 'cancellato')
                           ->get()
                           ->map(function($evento) {
                               return [
                                   'id' => $evento->id,
                                   'title' => $evento->titolo,
                                   'start' => $evento->data_inizio,
                                   'end' => $evento->data_fine,
                                   'url' => route('eventi.show', $evento->id),
                                   'backgroundColor' => $this->getColorByTipo($evento->tipo_evento),
                                   'borderColor' => $this->getColorByTipo($evento->tipo_evento),
                                   'textColor' => '#fff'
                               ];
                           });

            if ($request->wantsJson()) {
                return response()->json($eventi);
            }

            return view('eventi.calendario', compact('eventi'));

        } catch (\Exception $e) {
            Log::error('Errore nel caricamento calendario: ' . $e->getMessage());
            return back()->with('error', 'Errore nel caricamento del calendario');
        }
    }

    /**
     * Restituisce il colore per tipo evento
     */
    private function getColorByTipo($tipo)
    {
        $colors = [
            'formazione' => '#3498db',
            'emergenza' => '#e74c3c',
            'esercitazione' => '#f39c12',
            'evento_pubblico' => '#9b59b6',
            'servizio' => '#2ecc71',
            'altro' => '#95a5a6'
        ];

        return $colors[$tipo] ?? '#95a5a6';
    }

    /**
     * Export eventi in PDF
     */
    public function exportPdf(Request $request)
    {
        try {
            $eventi = Evento::with(['volontari', 'mezzi'])
                           ->when($request->data_da, function($q) use ($request) {
                               $q->where('data_inizio', '>=', $request->data_da);
                           })
                           ->when($request->data_a, function($q) use ($request) {
                               $q->where('data_fine', '<=', $request->data_a);
                           })
                           ->orderBy('data_inizio')
                           ->get();

            $pdf = app('dompdf.wrapper');
            $pdf->loadView('eventi.exports.pdf', compact('eventi'));

            return $pdf->download('eventi_' . now()->format('Y_m_d') . '.pdf');

        } catch (\Exception $e) {
            Log::error('Errore nell\'export PDF eventi: ' . $e->getMessage());
            return back()->with('error', 'Errore nell\'export PDF');
        }
    }

    /**
     * Export eventi in Excel
     */
    public function exportExcel(Request $request)
    {
        try {
            return (new \App\Exports\EventiExport($request->all()))
                   ->download('eventi_' . now()->format('Y_m_d') . '.xlsx');

        } catch (\Exception $e) {
            Log::error('Errore nell\'export Excel eventi: ' . $e->getMessage());
            return back()->with('error', 'Errore nell\'export Excel');
        }
    }
}