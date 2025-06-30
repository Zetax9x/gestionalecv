<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Volontario extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'volontari';

    protected $fillable = [
        'user_id',
        'tessera_numero',
        'data_iscrizione',
        'data_visita_medica',
        'scadenza_visita_medica',
        'medico_competente',
        'stato_formazione',
        'ultimo_corso',
        'corsi_completati',
        'competenze',
        'disponibilita',
        'note_disponibilita',
        'allergie_patologie',
        'contatto_emergenza_nome',
        'contatto_emergenza_telefono',
        'gruppo_sanguigno',
        'ore_servizio_anno',
        'note',
        'attivo',
        'data_sospensione',
        'motivo_sospensione'
    ];

    protected $casts = [
        'data_iscrizione' => 'date',
        'data_visita_medica' => 'date',
        'scadenza_visita_medica' => 'date',
        'ultimo_corso' => 'date',
        'corsi_completati' => 'array',
        'competenze' => 'array',
        'ore_servizio_anno' => 'decimal:2',
        'attivo' => 'boolean',
        'data_sospensione' => 'datetime',
    ];

    // ===================================
    // RELAZIONI
    // ===================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documenti()
    {
        return $this->hasMany(Documento::class);
    }

    public function assegnazioniDpi()
    {
        return $this->hasMany(AssegnazioneDpi::class);
    }

    public function dpiAssegnati()
    {
        return $this->hasMany(AssegnazioneDpi::class)->where('restituito', false);
    }

    public function partecipazioniEventi()
    {
        return $this->hasMany(PartecipazioneEvento::class, 'user_id', 'user_id');
    }

    // ===================================
    // ATTRIBUTI COMPUTATI
    // ===================================

    public function getAnniServizioAttribute()
    {
        return $this->data_iscrizione ? $this->data_iscrizione->diffInYears(now()) : 0;
    }

    public function getEtaAttribute()
    {
        return $this->user && $this->user->data_nascita ? $this->user->data_nascita->age : null;
    }

    public function getScadenzeVicineAttribute()
    {
        $scadenze = collect();
        
        if ($this->scadenza_visita_medica) {
            $giorni = now()->diffInDays($this->scadenza_visita_medica, false);
            if ($giorni <= 30 && $giorni >= 0) {
                $scadenze->push([
                    'tipo' => 'Visita Medica',
                    'data' => $this->scadenza_visita_medica,
                    'giorni' => $giorni,
                    'urgente' => $giorni <= 7
                ]);
            }
        }
        
        return $scadenze->sortBy('giorni');
    }

    // ===================================
    // METODI UTILITY
    // ===================================

    public static function generaNumeraTessera()
    {
        $anno = now()->year;
        $ultimoNumero = self::where('tessera_numero', 'like', $anno . '%')->max('tessera_numero');
        
        if ($ultimoNumero) {
            $numero = intval(substr($ultimoNumero, -4)) + 1;
        } else {
            $numero = 1;
        }
        
        return $anno . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    public function sospendi($motivo = null)
    {
        $this->update([
            'attivo' => false,
            'data_sospensione' => now(),
            'motivo_sospensione' => $motivo
        ]);
    }

    public function riattiva()
    {
        $this->update([
            'attivo' => true,
            'data_sospensione' => null,
            'motivo_sospensione' => null
        ]);
    }

    // ===================================
    // SCOPE QUERIES
    // ===================================

    public function scopeAttivi($query)
    {
        return $query->where('attivo', true);
    }

    public function scopeRicerca($query, $termine)
    {
        return $query->whereHas('user', function($q) use ($termine) {
            $q->where('nome', 'like', "%{$termine}%")
              ->orWhere('cognome', 'like', "%{$termine}%")
              ->orWhere('email', 'like', "%{$termine}%");
        })->orWhere('tessera_numero', 'like', "%{$termine}%");
    }
}