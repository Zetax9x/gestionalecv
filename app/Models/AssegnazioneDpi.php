<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssegnazioneDpi extends Model
{
    use HasFactory;

    protected $table = 'assegnazioni_dpi';

    protected $fillable = [
        'dpi_id',
        'volontario_id',
        'assegnato_da',
        'data_assegnazione',
        'data_restituzione',
        'restituito',
        'stato_consegna',
        'stato_restituzione',
        'motivo_assegnazione',
        'motivo_restituzione',
        'ricevuta_firmata',
        'documento_ricevuta',
        'formazione_effettuata',
        'data_formazione',
        'formatore_id',
        'ultima_verifica',
        'prossima_verifica',
        'note_verifica',
        'ore_utilizzo',
        'giorni_utilizzo',
        'note'
    ];

    protected $casts = [
        'data_assegnazione' => 'date',
        'data_restituzione' => 'date',
        'restituito' => 'boolean',
        'ricevuta_firmata' => 'boolean',
        'formazione_effettuata' => 'boolean',
        'data_formazione' => 'date',
        'ultima_verifica' => 'date',
        'prossima_verifica' => 'date',
        'ore_utilizzo' => 'integer',
        'giorni_utilizzo' => 'integer'
    ];

    // ===================================
    // RELAZIONI
    // ===================================

    public function dpi()
    {
        return $this->belongsTo(Dpi::class);
    }

    public function volontario()
    {
        return $this->belongsTo(Volontario::class);
    }

    public function assegnante()
    {
        return $this->belongsTo(User::class, 'assegnato_da');
    }

    public function formatore()
    {
        return $this->belongsTo(User::class, 'formatore_id');
    }
}
