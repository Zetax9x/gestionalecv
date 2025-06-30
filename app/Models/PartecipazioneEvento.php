<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartecipazioneEvento extends Model
{
    use HasFactory;

    // Tabella associata al modello
    protected $table = 'partecipazioni_eventi';

    protected $fillable = [
        'evento_id',
        'user_id',
        'stato',
        'data_iscrizione',
        'data_conferma',
        'data_annullamento',
        'presente',
        'ora_arrivo',
        'ora_uscita',
        'note_presenza',
        'superato',
        'voto',
        'numero_attestato',
        'data_rilascio_attestato',
        'file_attestato',
        'valutazione_evento',
        'feedback_evento',
        'valutazione_docenti',
        'feedback_docenti',
        'suggerimenti',
        'consiglia_evento',
        'motivo_rifiuto',
        'motivo_annullamento',
        'note'
    ];

    protected $casts = [
        'data_iscrizione' => 'datetime',
        'data_conferma' => 'datetime',
        'data_annullamento' => 'datetime',
        'presente' => 'boolean',
        'superato' => 'boolean',
        'data_rilascio_attestato' => 'date',
        'valutazione_evento' => 'integer',
        'valutazione_docenti' => 'integer',
        'consiglia_evento' => 'boolean'
    ];

    // ===================================
    // RELAZIONI
    // ===================================

    public function evento()
    {
        return $this->belongsTo(Evento::class);
    }

    public function volontario()
    {
        return $this->belongsTo(Volontario::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ===================================
    // ATTRIBUTI COMPUTATI
    // ===================================

    public function getStatoLabelAttribute()
    {
        $labels = [
            'iscritto' => 'Iscritto',
            'confermato' => 'Confermato',
            'presente' => 'Presente',
            'assente' => 'Assente',
            'cancellato' => 'Cancellato'
        ];
        
        return $labels[$this->stato] ?? 'Sconosciuto';
    }

    public function getColoreStatoAttribute()
    {
        $colori = [
            'iscritto' => 'info',
            'confermato' => 'primary',
            'presente' => 'success',
            'assente' => 'warning',
            'cancellato' => 'danger'
        ];
        
        return $colori[$this->stato] ?? 'secondary';
    }

    // ===================================
    // SCOPE QUERIES
    // ===================================

    public function scopeConfermati($query)
    {
        return $query->where('stato', 'confermato');
    }

    public function scopePresenti($query)
    {
        return $query->where('stato', 'presente');
    }

    public function scopePerEvento($query, $eventoId)
    {
        return $query->where('evento_id', $eventoId);
    }

    // ===================================
    // METODI UTILITY
    // ===================================

    public function conferma()
    {
        $this->update([
            'stato' => 'confermato',
            'data_conferma' => now()
        ]);
    }

    public function marcaPresente($oreEffettive = null)
    {
        $this->update([
            'stato' => 'presente'
        ]);
    }

    public function marcaAssente()
    {
        $this->update([
            'stato' => 'assente'
        ]);
    }

    public function cancella()
    {
        $this->update([
            'stato' => 'cancellato'
        ]);
    }
}