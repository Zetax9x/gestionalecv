<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Magazzino extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'magazzino';

    protected $fillable = [
        'nome_articolo',
        'descrizione',
        'codice_articolo',
        'codice_interno',
        'codice_fornitore',
        'quantita_attuale',
        'quantita_minima',
        'quantita_massima',
        'punto_riordino',
        'unita_misura',
        'categoria',
        'sottocategoria',
        'tags',
        'scadenza',
        'lotto',
        'gestione_lotti',
        'gestione_scadenze',
        'prezzo_unitario',
        'costo_ultimo_acquisto',
        'fornitore_principale',
        'fornitori_alternativi',
        'ubicazione',
        'zona_magazzino',
        'temperatura_conservazione_min',
        'temperatura_conservazione_max',
        'condizioni_conservazione',
        'farmaco',
        'stupefacente',
        'dispositivo_medico',
        'classe_dispositivo',
        'monouso',
        'attivo',
        'note',
        'foto',
        'responsabile_id'
    ];

    protected $casts = [
        'tags' => 'array',
        'scadenza' => 'date',
        'gestione_lotti' => 'boolean',
        'gestione_scadenze' => 'boolean',
        'prezzo_unitario' => 'decimal:2',
        'costo_ultimo_acquisto' => 'decimal:2',
        'fornitori_alternativi' => 'array',
        'temperatura_conservazione_min' => 'decimal:2',
        'temperatura_conservazione_max' => 'decimal:2',
        'farmaco' => 'boolean',
        'stupefacente' => 'boolean',
        'dispositivo_medico' => 'boolean',
        'monouso' => 'boolean',
        'attivo' => 'boolean'
    ];

    // ===================================
    // RELAZIONI
    // ===================================

    public function movimenti()
    {
        return $this->hasMany(MovimentoMagazzino::class, 'articolo_id');
    }

    public function ultimoMovimento()
    {
        return $this->hasOne(MovimentoMagazzino::class, 'articolo_id')->latest('created_at');
    }

    public function responsabile()
    {
        return $this->belongsTo(User::class, 'responsabile_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'articolo_magazzino_id');
    }

    // ===================================
    // ATTRIBUTI COMPUTATI
    // ===================================

    public function getSottoscortaAttribute()
    {
        return $this->quantita_attuale <= $this->quantita_minima;
    }

    public function getInScadenzaAttribute()
    {
        return $this->scadenza && $this->scadenza->diffInDays(now()) <= 30;
    }

    public function getScadutoAttribute()
    {
        return $this->scadenza && $this->scadenza->isPast();
    }

    public function getValoreStockAttribute()
    {
        return $this->quantita_attuale * ($this->prezzo_unitario ?? 0);
    }

    public function getColoreStatoAttribute()
    {
        if (!$this->attivo) return 'secondary';
        if ($this->scaduto) return 'danger';
        if ($this->sottoscorta) return 'warning';
        if ($this->in_scadenza) return 'info';
        return 'success';
    }

    public function getStatoDescrizioneAttribute()
    {
        if (!$this->attivo) return 'Non Attivo';
        if ($this->scaduto) return 'Scaduto';
        if ($this->sottoscorta) return 'Sottoscorta';
        if ($this->in_scadenza) return 'In Scadenza';
        return 'Disponibile';
    }

    // ===================================
    // METODI UTILITY
    // ===================================

    public function registraCarico($quantita, $motivo, $userId = null, $datiExtra = [])
    {
        $movimento = $this->movimenti()->create([
            'user_id' => $userId ?? auth()->id(),
            'tipo' => 'carico',
            'quantita' => $quantita,
            'motivo' => $motivo,
            'data_movimento' => now()->toDateString(),
            ...$datiExtra
        ]);

        $this->increment('quantita_attuale', $quantita);

        if (isset($datiExtra['prezzo_unitario'])) {
            $this->update(['costo_ultimo_acquisto' => $datiExtra['prezzo_unitario']]);
        }

        return $movimento;
    }

    public function registraScarico($quantita, $motivo, $userId = null, $datiExtra = [])
    {
        if ($this->quantita_attuale < $quantita) {
            throw new \Exception("Quantità insufficiente in magazzino. Disponibili: {$this->quantita_attuale} {$this->unita_misura}");
        }

        $movimento = $this->movimenti()->create([
            'user_id' => $userId ?? auth()->id(),
            'tipo' => 'scarico',
            'quantita' => $quantita,
            'motivo' => $motivo,
            'data_movimento' => now()->toDateString(),
            ...$datiExtra
        ]);

        $this->decrement('quantita_attuale', $quantita);

        if ($this->sottoscorta) {
            $this->creaNotificaSottoscorta();
        }

        return $movimento;
    }

    private function creaNotificaSottoscorta()
    {
        $utentiNotifica = collect();
        
        if ($this->responsabile_id) {
            $utentiNotifica->push($this->responsabile_id);
        }
        
        $utentiMezzi = User::where('ruolo', 'mezzi')->where('attivo', true)->pluck('id');
        $utentiNotifica = $utentiNotifica->merge($utentiMezzi)->unique();

        Notifica::create([
            'destinatari' => $utentiNotifica->toArray(),
            'titolo' => 'Articolo Sottoscorta',
            'messaggio' => "L'articolo '{$this->nome_articolo}' è sotto la quantità minima. Disponibili: {$this->quantita_attuale} {$this->unita_misura}",
            'tipo' => 'sottoscorta'
        ]);
    }

    // ===================================
    // SCOPE QUERIES
    // ===================================

    public function scopeAttivi($query)
    {
        return $query->where('attivo', true);
    }

    public function scopeSottoscorta($query)
    {
        return $query->whereRaw('quantita_attuale <= quantita_minima');
    }

    public function scopeInScadenza($query, $giorni = 30)
    {
        return $query->whereDate('scadenza', '<=', now()->addDays($giorni))
                     ->whereDate('scadenza', '>=', now());
    }

    public function scopeRicerca($query, $termine)
    {
        return $query->where(function($q) use ($termine) {
            $q->where('nome_articolo', 'like', "%{$termine}%")
              ->orWhere('descrizione', 'like', "%{$termine}%")
              ->orWhere('codice_articolo', 'like', "%{$termine}%")
              ->orWhere('codice_interno', 'like', "%{$termine}%");
        });
    }
}
