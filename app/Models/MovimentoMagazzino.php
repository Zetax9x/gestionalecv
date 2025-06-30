<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimentoMagazzino extends Model
{
    use HasFactory;

    protected $table = 'movimenti_magazzino';

    protected $fillable = [
        'magazzino_id',
        'user_id',
        'tipo_movimento',
        'quantita',
        'quantita_precedente',
        'quantita_attuale',
        'prezzo_unitario',
        'valore_totale',
        'data_movimento',
        'causale',
        'numero_documento',
        'fornitore',
        'note',
        'approvato',
        'approvato_da',
        'data_approvazione'
    ];

    protected $casts = [
        'data_movimento' => 'datetime',
        'data_approvazione' => 'datetime',
        'quantita' => 'decimal:2',
        'quantita_precedente' => 'decimal:2',
        'quantita_attuale' => 'decimal:2',
        'prezzo_unitario' => 'decimal:2',
        'valore_totale' => 'decimal:2',
        'approvato' => 'boolean'
    ];

    // ===================================
    // RELAZIONI
    // ===================================

    public function articolo()
    {
        return $this->belongsTo(Magazzino::class, 'magazzino_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvatoDa()
    {
        return $this->belongsTo(User::class, 'approvato_da');
    }

    // ===================================
    // ATTRIBUTI COMPUTATI
    // ===================================

    public function getTipoMovimentoLabelAttribute()
    {
        $labels = [
            'carico' => 'Carico',
            'scarico' => 'Scarico',
            'inventario' => 'Inventario',
            'rettifica' => 'Rettifica',
            'trasferimento' => 'Trasferimento',
            'perdita' => 'Perdita',
            'donazione' => 'Donazione'
        ];
        
        return $labels[$this->tipo_movimento] ?? 'Movimento';
    }

    public function getColoreTipoAttribute()
    {
        $colori = [
            'carico' => 'success',
            'scarico' => 'danger',
            'inventario' => 'info',
            'rettifica' => 'warning',
            'trasferimento' => 'primary',
            'perdita' => 'dark',
            'donazione' => 'secondary'
        ];
        
        return $colori[$this->tipo_movimento] ?? 'secondary';
    }

    public function getIconaTipoAttribute()
    {
        $icone = [
            'carico' => 'box-arrow-in-down',
            'scarico' => 'box-arrow-up',
            'inventario' => 'clipboard-check',
            'rettifica' => 'pencil-square',
            'trasferimento' => 'arrow-left-right',
            'perdita' => 'exclamation-triangle',
            'donazione' => 'gift'
        ];
        
        return $icone[$this->tipo_movimento] ?? 'box';
    }

    public function getVariazioneQuantitaAttribute()
    {
        if (in_array($this->tipo_movimento, ['carico', 'inventario'])) {
            return "+{$this->quantita}";
        } else {
            return "-{$this->quantita}";
        }
    }

    // ===================================
    // SCOPE QUERIES
    // ===================================

    public function scopeCarichi($query)
    {
        return $query->whereIn('tipo_movimento', ['carico', 'inventario']);
    }

    public function scopeScarichi($query)
    {
        return $query->whereIn('tipo_movimento', ['scarico', 'perdita', 'donazione']);
    }

    public function scopePerArticolo($query, $articoloId)
    {
        return $query->where('magazzino_id', $articoloId);
    }

    public function scopePerPeriodo($query, $dataInizio, $dataFine)
    {
        return $query->whereBetween('data_movimento', [$dataInizio, $dataFine]);
    }

    public function scopeApprovati($query)
    {
        return $query->where('approvato', true);
    }

    public function scopeInAttesaApprovazione($query)
    {
        return $query->where('approvato', false);
    }

    // ===================================
    // METODI UTILITY
    // ===================================

    public function isCarico()
    {
        return in_array($this->tipo_movimento, ['carico', 'inventario']);
    }

    public function isScarico()
    {
        return in_array($this->tipo_movimento, ['scarico', 'perdita', 'donazione']);
    }

    public function approva($userId = null)
    {
        $this->update([
            'approvato' => true,
            'approvato_da' => $userId ?? auth()->id(),
            'data_approvazione' => now()
        ]);
    }

    public function calcolaValoreTotale()
    {
        if ($this->prezzo_unitario && $this->quantita) {
            $this->valore_totale = $this->prezzo_unitario * $this->quantita;
            $this->save();
        }
    }

    // ===================================
    // EVENTI MODEL
    // ===================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($movimento) {
            // Calcola automaticamente il valore totale
            if ($movimento->prezzo_unitario && $movimento->quantita) {
                $movimento->valore_totale = $movimento->prezzo_unitario * $movimento->quantita;
            }

            // Imposta la data movimento se non specificata
            if (!$movimento->data_movimento) {
                $movimento->data_movimento = now();
            }
        });

        static::created(function ($movimento) {
            // Aggiorna la quantità dell'articolo in magazzino
            if ($movimento->approvato) {
                $movimento->aggiornaQuantitaArticolo();
            }
        });
    }

    public function aggiornaQuantitaArticolo()
    {
        $articolo = $this->articolo;
        if (!$articolo) return;

        if ($this->isCarico()) {
            $articolo->increment('quantita_attuale', $this->quantita);
        } else {
            $articolo->decrement('quantita_attuale', $this->quantita);
        }
    }
}