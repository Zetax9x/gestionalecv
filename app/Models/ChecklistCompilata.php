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
        'risultati',
        'conforme',
        'note_generali',
        'data_compilazione',
        'km_mezzo',
        'turno',
        'destinazione_servizio',
        'supervisore_id',
        'data_approvazione',
        'note_supervisore',
        'foto_anomalie'
    ];

    protected $casts = [
        'data_compilazione' => 'datetime',
        'data_approvazione' => 'datetime',
        'risultati' => 'array',
        'foto_anomalie' => 'array',
        'conforme' => 'boolean'
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
        
        return $this->conforme ? 'Conforme' : 'Non conforme';
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
        
        return $this->conforme ? 'success' : 'danger';
    }

    public function getPercentualeCompletamentoAttribute()
    {
        if (!$this->risultati) return 0;

        $totaleControlli = count($this->risultati);
        $controlliCompletati = count(array_filter($this->risultati, function($controllo) {
            return isset($controllo['eseguito']) && $controllo['eseguito'];
        }));
        
        return $totaleControlli > 0 ? round(($controlliCompletati / $totaleControlli) * 100) : 0;
    }

    public function getNumeroAnomalieAttribute()
    {
        return $this->foto_anomalie ? count($this->foto_anomalie) : 0;
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
        return $query->whereNotNull('data_approvazione');
    }

    public function scopeConAnomalie($query)
    {
        return $query->whereNotNull('foto_anomalie')
                    ->whereJsonLength('foto_anomalie', '>', 0);
    }

    public function scopePerPeriodo($query, $dataInizio, $dataFine)
    {
        return $query->whereBetween('data_compilazione', [$dataInizio, $dataFine]);
    }

    // ===================================
    // METODI UTILITY
    // ===================================

    public function hasAnomalie()
    {
        return $this->numero_anomalie > 0;
    }

    public function isCompletata()
    {
        return !is_null($this->data_approvazione);
    }

    public function aggiungiAnomalia($descrizione, $gravita = 'media', $note = null)
    {
        $anomalie = $this->foto_anomalie ?? [];
        
        $anomalie[] = [
            'descrizione' => $descrizione,
            'gravita' => $gravita,
            'note' => $note,
            'data_rilevazione' => now()->toDateTimeString()
        ];
        
        $this->update(['foto_anomalie' => $anomalie]);
    }

    public function completa()
    {
        $this->update([
            'data_approvazione' => now(),
            'supervisore_id' => auth()->id()
        ]);
    }
}