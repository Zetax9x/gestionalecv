<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Dpi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dpi';

    protected $fillable = [
        'nome',
        'descrizione',
        'codice_dpi',
        'categoria',
        'taglia',
        'colore',
        'materiale',
        'marca',
        'modello',
        'certificazione_ce',
        'normative_riferimento',
        'classe_protezione',
        'data_certificazione',
        'scadenza_certificazione',
        'data_acquisto',
        'scadenza',
        'durata_mesi',
        'max_utilizzi',
        'utilizzi_effettuati',
        'stato',
        'disponibile',
        'in_manutenzione',
        'data_ultima_verifica',
        'prossima_verifica',
        'costo_acquisto',
        'fornitore',
        'numero_fattura',
        'ubicazione',
        'armadio_scaffale',
        'istruzioni_uso',
        'istruzioni_manutenzione',
        'istruzioni_pulizia',
        'note',
        'foto'
    ];

    protected $casts = [
        'normative_riferimento' => 'array',
        'data_certificazione' => 'date',
        'scadenza_certificazione' => 'date',
        'data_acquisto' => 'date',
        'scadenza' => 'date',
        'disponibile' => 'boolean',
        'in_manutenzione' => 'boolean',
        'data_ultima_verifica' => 'date',
        'prossima_verifica' => 'date',
        'costo_acquisto' => 'decimal:2'
    ];

    // ===================================
    // RELAZIONI
    // ===================================

    public function assegnazioni()
    {
        return $this->hasMany(AssegnazioneDpi::class);
    }

    public function assegnazioneAttuale()
    {
        return $this->hasOne(AssegnazioneDpi::class)->where('restituito', false);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function volontarioAttuale()
    {
        return $this->hasOneThrough(
            Volontario::class,
            AssegnazioneDpi::class,
            'dpi_id',
            'id',
            'id',
            'volontario_id'
        )->where('assegnazioni_dpi.restituito', false);
    }

    // ===================================
    // ATTRIBUTI COMPUTATI
    // ===================================

    public function getCategoriaLabelAttribute()
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
        
        return $categorie[$this->categoria] ?? $this->categoria;
    }

    public function getStatoLabelAttribute()
    {
        $stati = [
            'nuovo' => 'Nuovo',
            'buono' => 'Buone Condizioni',
            'usato' => 'Usato',
            'da_controllare' => 'Da Controllare',
            'da_sostituire' => 'Da Sostituire',
            'dismesso' => 'Dismesso'
        ];
        
        return $stati[$this->stato] ?? $this->stato;
    }

    public function getColoreStatoAttribute()
    {
        $colori = [
            'nuovo' => 'success',
            'buono' => 'primary',
            'usato' => 'info',
            'da_controllare' => 'warning',
            'da_sostituire' => 'danger',
            'dismesso' => 'secondary'
        ];
        
        return $colori[$this->stato] ?? 'secondary';
    }

    public function getInScadenzaAttribute()
    {
        return $this->scadenza && $this->scadenza->diffInDays(now()) <= 30;
    }

    public function getScadutoAttribute()
    {
        return $this->scadenza && $this->scadenza->isPast();
    }

    public function getInScadenzaCertificazioneAttribute()
    {
        return $this->scadenza_certificazione && $this->scadenza_certificazione->diffInDays(now()) <= 30;
    }

    public function getEtaDpiAttribute()
    {
        return $this->data_acquisto ? $this->data_acquisto->diffInMonths(now()) : null;
    }

    public function getPercentualeUtilizzoAttribute()
    {
        if (!$this->max_utilizzi) return null;
        
        return round(($this->utilizzi_effettuati / $this->max_utilizzi) * 100, 1);
    }

    public function getDisponibilePerAssegnazioneAttribute()
    {
        return $this->disponibile && 
               !$this->in_manutenzione && 
               !$this->scaduto && 
               in_array($this->stato, ['nuovo', 'buono']) &&
               !$this->assegnazioneAttuale;
    }

    public function getGiorniResiduiAttribute()
    {
        if (!$this->scadenza) return null;
        
        return now()->diffInDays($this->scadenza, false);
    }

    public function getUtilizziResiduiAttribute()
    {
        if (!$this->max_utilizzi) return null;
        
        return max(0, $this->max_utilizzi - $this->utilizzi_effettuati);
    }

    // ===================================
    // METODI UTILITY
    // ===================================

    public function assegnaA($volontarioId, $assegnatoDa = null, $datiExtra = [])
    {
        if (!$this->disponibile_per_assegnazione) {
            throw new \Exception("Il DPI non è disponibile per l'assegnazione");
        }

        $assegnazione = $this->assegnazioni()->create([
            'volontario_id' => $volontarioId,
            'assegnato_da' => $assegnatoDa ?? auth()->id(),
            'data_assegnazione' => now()->toDateString(),
            'stato_consegna' => $this->stato,
            ...$datiExtra
        ]);

        $this->update(['disponibile' => false]);

        $volontario = Volontario::find($volontarioId);
        LogAttivita::create([
            'user_id' => auth()->id(),
            'azione' => 'assegnazione_dpi',
            'modulo' => 'dpi',
            'risorsa_id' => $this->id,
            'descrizione' => "DPI '{$this->nome}' assegnato a {$volontario->user->nome_completo}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data_ora' => now()
        ]);

        return $assegnazione;
    }

    public function registraRestituzione($statoRestituzione, $motivo = null, $datiExtra = [])
    {
        $assegnazione = $this->assegnazioneAttuale;
        
        if (!$assegnazione) {
            throw new \Exception("Nessuna assegnazione attiva trovata per questo DPI");
        }

        $assegnazione->update([
            'data_restituzione' => now()->toDateString(),
            'stato_restituzione' => $statoRestituzione,
            'motivo_restituzione' => $motivo,
            'restituito' => true,
            ...$datiExtra
        ]);

        $nuovoStato = $this->stato;
        $disponibile = true;

        switch ($statoRestituzione) {
            case 'danneggiato':
                $nuovoStato = 'da_sostituire';
                $disponibile = false;
                break;
            case 'usato':
                $nuovoStato = 'da_controllare';
                break;
            case 'perso':
            case 'non_restituito':
                $nuovoStato = 'dismesso';
                $disponibile = false;
                break;
        }

        $this->update([
            'stato' => $nuovoStato,
            'disponibile' => $disponibile
        ]);

        if ($this->max_utilizzi) {
            $this->increment('utilizzi_effettuati');
        }

        LogAttivita::create([
            'user_id' => auth()->id(),
            'azione' => 'restituzione_dpi',
            'modulo' => 'dpi',
            'risorsa_id' => $this->id,
            'descrizione' => "DPI '{$this->nome}' restituito da {$assegnazione->volontario->user->nome_completo}",
            'note' => "Stato restituzione: {$statoRestituzione}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data_ora' => now()
        ]);

        return $assegnazione;
    }

    public function registraVerifica($esito, $note = null, $prossimaDaTesto = null)
    {
        $prossimaVerifica = null;
        
        if ($prossimaDaTesto) {
            try {
                $prossimaVerifica = Carbon::parse($prossimaDaTesto)->toDateString();
            } catch (\Exception $e) {
                $prossimaVerifica = now()->addMonths(6)->toDateString();
            }
        } else {
            $intervalliVerifica = [
                'protezione_testa' => 12,
                'protezione_cadute' => 6,
                'protezione_respiratoria' => 3,
                'default' => 6
            ];
            
            $mesi = $intervalliVerifica[$this->categoria] ?? $intervalliVerifica['default'];
            $prossimaVerifica = now()->addMonths($mesi)->toDateString();
        }

        $this->update([
            'data_ultima_verifica' => now()->toDateString(),
            'prossima_verifica' => $prossimaVerifica,
            'stato' => $esito ? 'buono' : 'da_controllare',
            'note' => $this->note . "\n[" . now()->format('d/m/Y') . "] Verifica: " . ($esito ? 'OK' : 'NON CONFORME') . ($note ? " - {$note}" : "")
        ]);

        LogAttivita::create([
            'user_id' => auth()->id(),
            'azione' => 'verifica_dpi',
            'modulo' => 'dpi',
            'risorsa_id' => $this->id,
            'descrizione' => "Verifica DPI '{$this->nome}': " . ($esito ? 'CONFORME' : 'NON CONFORME'),
            'note' => $note,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data_ora' => now()
        ]);

        return $this;
    }

    public function generaCodiceDpi()
    {
        $prefisso = strtoupper(substr($this->categoria, 0, 3));
        $ultimoCodice = self::where('codice_dpi', 'like', $prefisso . '%')->max('codice_dpi');

        if ($ultimoCodice) {
            $numero = intval(substr($ultimoCodice, -4)) + 1;
        } else {
            $numero = 1;
        }

        return $prefisso . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    public function calcolaScadenzaAutomatica()
    {
        if ($this->durata_mesi && $this->data_acquisto) {
            return $this->data_acquisto->addMonths($this->durata_mesi);
        }
        
        return null;
    }

    // ===================================
    // STATISTICHE E REPORT
    // ===================================

    public static function getStatisticheDpi()
    {
        return [
            'totale_dpi' => self::count(),
            'dpi_disponibili' => self::where('disponibile', true)->count(),
            'dpi_assegnati' => self::whereHas('assegnazioneAttuale')->count(),
            'dpi_in_manutenzione' => self::where('in_manutenzione', true)->count(),
            'dpi_in_scadenza' => self::whereDate('scadenza', '<=', now()->addDays(30))
                                    ->whereDate('scadenza', '>=', now())
                                    ->count(),
            'dpi_scaduti' => self::whereDate('scadenza', '<', now())->count(),
            'dpi_da_verificare' => self::whereDate('prossima_verifica', '<=', now())->count(),
            'valore_totale_dpi' => self::sum('costo_acquisto') ?? 0
        ];
    }

    public static function getDpiInScadenza($giorni = 30)
    {
        return self::whereDate('scadenza', '<=', now()->addDays($giorni))
                   ->whereDate('scadenza', '>=', now())
                   ->orderBy('scadenza')
                   ->get();
    }

    public static function getDpiDaVerificare()
    {
        return self::whereDate('prossima_verifica', '<=', now())
                   ->where('disponibile', true)
                   ->orderBy('prossima_verifica')
                   ->get();
    }

    // ===================================
    // SCOPE QUERIES
    // ===================================

    public function scopeDisponibili($query)
    {
        return $query->where('disponibile', true)
                     ->where('in_manutenzione', false)
                     ->whereIn('stato', ['nuovo', 'buono']);
    }

    public function scopeAssegnati($query)
    {
        return $query->whereHas('assegnazioneAttuale');
    }

    public function scopeInScadenza($query, $giorni = 30)
    {
        return $query->whereDate('scadenza', '<=', now()->addDays($giorni))
                     ->whereDate('scadenza', '>=', now());
    }

    public function scopeDaVerificare($query)
    {
        return $query->whereDate('prossima_verifica', '<=', now());
    }

    public function scopeCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function scopeTaglia($query, $taglia)
    {
        return $query->where('taglia', $taglia);
    }

    public function scopeRicerca($query, $termine)
    {
        return $query->where(function($q) use ($termine) {
            $q->where('nome', 'like', "%{$termine}%")
              ->orWhere('descrizione', 'like', "%{$termine}%")
              ->orWhere('codice_dpi', 'like', "%{$termine}%")
              ->orWhere('marca', 'like', "%{$termine}%")
              ->orWhere('modello', 'like', "%{$termine}%");
        });
    }
}