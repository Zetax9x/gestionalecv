<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Manutenzione extends Model
{
    use HasFactory;

    protected $table = 'manutenzioni';

    protected $fillable = [
        'mezzo_id',
        'tipo_manutenzione',
        'descrizione',
        'data_manutenzione',
        'km_effettuati',
        'costo',
        'fornitore',
        'numero_fattura',
        'prossima_manutenzione',
        'km_prossima_manutenzione',
        'stato',
        'note',
        'tecnico_responsabile',
        'allegati'
    ];

    protected $casts = [
        'data_manutenzione' => 'date',
        'prossima_manutenzione' => 'date',
        'costo' => 'decimal:2',
        'allegati' => 'array'
    ];

    // Relazioni
    public function mezzo()
    {
        return $this->belongsTo(Mezzo::class);
    }

    // Attributi computati
    public function getTipoLabelAttribute()
    {
        $tipi = [
            'tagliando' => 'Tagliando',
            'revisione' => 'Revisione',
            'riparazione' => 'Riparazione',
            'controllo' => 'Controllo',
            'altro' => 'Altro'
        ];
        
        return $tipi[$this->tipo_manutenzione] ?? 'Non specificato';
    }
}