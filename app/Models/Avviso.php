<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Avviso extends Model
{
    use HasFactory;

    protected $table = 'avvisi';

    protected $fillable = [
        'titolo',
        'contenuto',
        'tipo',
        'priorita',
        'data_pubblicazione',
        'data_scadenza',
        'destinatari',
        'autore_id',
        'pubblicato',
        'pin_in_alto',
        'allegati'
    ];

    protected $casts = [
        'data_pubblicazione' => 'datetime',
        'data_scadenza' => 'datetime',
        'destinatari' => 'array',
        'pubblicato' => 'boolean',
        'pin_in_alto' => 'boolean',
        'allegati' => 'array'
    ];

    // ===================================
    // RELAZIONI
    // ===================================

    public function autore()
    {
        return $this->belongsTo(User::class, 'autore_id');
    }

    // ===================================
    // ATTRIBUTI COMPUTATI
    // ===================================

    public function getTipoLabelAttribute()
    {
        $labels = [
            'generale' => 'Generale',
            'urgente' => 'Urgente',
            'formazione' => 'Formazione',
            'evento' => 'Evento',
            'manutenzione' => 'Manutenzione'
        ];
        
        return $labels[$this->tipo] ?? 'Generale';
    }

    public function getColoreTipoAttribute()
    {
        $colori = [
            'generale' => 'primary',
            'urgente' => 'danger',
            'formazione' => 'info',
            'evento' => 'success',
            'manutenzione' => 'warning'
        ];
        
        return $colori[$this->tipo] ?? 'primary';
    }

    public function getPrioritaLabelAttribute()
    {
        $labels = [
            'bassa' => 'Bassa',
            'normale' => 'Normale',
            'alta' => 'Alta',
            'critica' => 'Critica'
        ];
        
        return $labels[$this->priorita] ?? 'Normale';
    }

    // ===================================
    // SCOPE QUERIES
    // ===================================

    public function scopePubblicati($query)
    {
        return $query->where('pubblicato', true);
    }

    public function scopeAttivi($query)
    {
        return $query->where('pubblicato', true)
                    ->where(function($q) {
                        $q->whereNull('data_scadenza')
                          ->orWhere('data_scadenza', '>=', now());
                    });
    }

    public function scopePinInAlto($query)
    {
        return $query->where('pin_in_alto', true);
    }

    public function scopePerTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // ===================================
    // METODI UTILITY
    // ===================================

    public function isAttivo()
    {
        return $this->pubblicato && 
               (!$this->data_scadenza || $this->data_scadenza >= now());
    }

    public function isScaduto()
    {
        return $this->data_scadenza && $this->data_scadenza < now();
    }
}