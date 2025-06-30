<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartecipazioneEvento extends Model
{
    use HasFactory;

    protected $table = 'partecipazione_eventi';

    protected $fillable = [
        'evento_id',
        'volontario_id',
        'user_id',
        'stato_partecipazione',
        'data_iscrizione',
        'data_conferma',
        'note',
        'valutazione_evento',
        'feedback',
        'ore_effettive',
        'certificato_inviato'
    ];

    protected $casts = [
        'data_iscrizione' => 'datetime',
        'data_conferma' => 'datetime',
        'valutazione_evento' => 'integer',
        'ore_effettive' => 'decimal:2',
        'certificato_inviato' => 'boolean'
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
        
        return $labels[$this->stato_partecipazione] ?? 'Sconosciuto';
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
        
        return $colori[$this->stato_partecipazione] ?? 'secondary';
    }

    // ===================================
    // SCOPE QUERIES
    // ===================================

    public function scopeConfermati($query)
    {
        return $query->where('stato_partecipazione', 'confermato');
    }

    public function scopePresenti($query)
    {
        return $query->where('stato_partecipazione', 'presente');
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
            'stato_partecipazione' => 'confermato',
            'data_conferma' => now()
        ]);
    }

    public function marcaPresente($oreEffettive = null)
    {
        $this->update([
            'stato_partecipazione' => 'presente',
            'ore_effettive' => $oreEffettive ?? $this->evento->durata_ore
        ]);
    }

    public function marcaAssente()
    {
        $this->update([
            'stato_partecipazione' => 'assente'
        ]);
    }

    public function cancella()
    {
        $this->update([
            'stato_partecipazione' => 'cancellato'
        ]);
    }
}