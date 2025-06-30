<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistCompilata extends Model
{
    use HasFactory;

    protected $table = 'checklist_compilate';

    protected $fillable = [
        'mezzo_id',
        'user_id',
        'template_id',
        'data_controllo',
        'km_mezzo',
        'risultati_controlli',
        'anomalie_riscontrate',
        'note_aggiuntive',
        'stato_generale',
        'firma_digitale',
        'completata'
    ];

    protected $casts = [
        'data_controllo' => 'datetime',
        'risultati_controlli' => 'array',
        'anomalie_riscontrate' => 'array',
        'completata' => 'boolean'
    ];

    // ===================================
    // RELAZIONI
    // ===================================

    public function mezzo()
    {
        return $this->belongsTo(Mezzo::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function template()
    {
        return $this->belongsTo(ChecklistTemplate::class, 'template_id');
    }

    // ===================================
    // ATTRIBUTI COMPUTATI
    // ===================================

    public function getStatoGeneraleLabelAttribute()
    {
        $labels = [
            'ottimo' => 'Ottimo',
            'buono' => 'Buono',
            'discreto' => 'Discreto',
            'problematico' => 'Problematico',
            'critico' => 'Critico'
        ];
        
        return $labels[$this->stato_generale] ?? 'Non definito';
    }

    public function getColoreStatoAttribute()
    {
        $colori = [
            'ottimo' => 'success',
            'buono' => 'info',
            'discreto' => 'warning',
            'problematico' => 'warning',
            'critico' => 'danger'
        ];
        
        return $colori[$this->stato_generale] ?? 'secondary';
    }

    public function getPercentualeCompletamentoAttribute()
    {
        if (!$this->risultati_controlli) return 0;
        
        $totaleControlli = count($this->risultati_controlli);
        $controlliCompletati = count(array_filter($this->risultati_controlli, function($controllo) {
            return isset($controllo['eseguito']) && $controllo['eseguito'];
        }));
        
        return $totaleControlli > 0 ? round(($controlliCompletati / $totaleControlli) * 100) : 0;
    }

    public function getNumeroAnomalieAttribute()
    {
        return $this->anomalie_riscontrate ? count($this->anomalie_riscontrate) : 0;
    }

    // ===================================
    // SCOPE QUERIES
    // ===================================

    public function scopePerMezzo($query, $mezzoId)
    {
        return $query->where('mezzo_id', $mezzoId);
    }

    public function scopeCompletate($query)
    {
        return $query->where('completata', true);
    }

    public function scopeConAномalie($query)
    {
        return $query->whereNotNull('anomalie_riscontrate')
                    ->whereJsonLength('anomalie_riscontrate', '>', 0);
    }

    public function scopePerPeriodo($query, $dataInizio, $dataFine)
    {
        return $query->whereBetween('data_controllo', [$dataInizio, $dataFine]);
    }

    // ===================================
    // METODI UTILITY
    // ===================================

    public function hasAnomaliae()
    {
        return $this->numero_anomalie > 0;
    }

    public function isCompletata()
    {
        return $this->completata;
    }

    public function aggiungiAnomalia($descrizione, $gravita = 'media', $note = null)
    {
        $anomalie = $this->anomalie_riscontrate ?? [];
        
        $anomalie[] = [
            'descrizione' => $descrizione,
            'gravita' => $gravita,
            'note' => $note,
            'data_rilevazione' => now()->toDateTimeString()
        ];
        
        $this->update(['anomalie_riscontrate' => $anomalie]);
    }

    public function completa()
    {
        $this->update([
            'completata' => true,
            'firma_digitale' => auth()->user()->nome_completo . ' - ' . now()->format('d/m/Y H:i')
        ]);
    }
}