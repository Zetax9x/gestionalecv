<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Evento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'eventi';

    protected $fillable = [
        'titolo',
        'descrizione',
        'data_inizio',
        'data_fine',
        'luogo',
        'indirizzo_completo',
        'tipo_evento',
        'stato',
        'max_partecipanti',
        'ore_formative',
        'costo_partecipazione',
        'materiali_necessari',
        'note_organizzatore',
        'organizzatore_id',
        'data_scadenza_iscrizioni',
        'certificato_rilasciato',
        'codice_evento',
        'link_materiali',
        'feedback_richiesto',
        'visibile_pubblico'
    ];

    protected $casts = [
        'data_inizio' => 'datetime',
        'data_fine' => 'datetime',
        'data_scadenza_iscrizioni' => 'datetime',
        'costo_partecipazione' => 'decimal:2',
        'certificato_rilasciato' => 'boolean',
        'feedback_richiesto' => 'boolean',
        'visibile_pubblico' => 'boolean',
        'materiali_necessari' => 'array'
    ];

    // ===================================
    // RELAZIONI
    // ===================================

    public function organizzatore()
    {
        return $this->belongsTo(User::class, 'organizzatore_id');
    }

    public function partecipazioni()
    {
        return $this->hasMany(PartecipazioneEvento::class);
    }

    public function volontari()
    {
        return $this->belongsToMany(Volontario::class, 'partecipazione_eventi')
                    ->withPivot(['stato_partecipazione', 'data_iscrizione', 'note'])
                    ->withTimestamps();
    }

    // ===================================
    // ATTRIBUTI COMPUTATI
    // ===================================

    public function getStatoLabelAttribute()
    {
        $labels = [
            'bozza' => 'Bozza',
            'pubblicato' => 'Pubblicato',
            'in_corso' => 'In Corso',
            'completato' => 'Completato',
            'cancellato' => 'Cancellato'
        ];
        
        return $labels[$this->stato] ?? 'Sconosciuto';
    }

    public function getColoreStatoAttribute()
    {
        $colori = [
            'bozza' => 'secondary',
            'pubblicato' => 'primary',
            'in_corso' => 'warning',
            'completato' => 'success',
            'cancellato' => 'danger'
        ];
        
        return $colori[$this->stato] ?? 'secondary';
    }

    public function getDurataOreAttribute()
    {
        if (!$this->data_inizio || !$this->data_fine) {
            return 0;
        }
        
        return $this->data_inizio->diffInHours($this->data_fine);
    }

    public function getPostiDisponibiliAttribute()
    {
        if (!$this->max_partecipanti) {
            return null;
        }
        
        $partecipanti = $this->partecipazioni()
                            ->where('stato_partecipazione', 'confermato')
                            ->count();
                            
        return $this->max_partecipanti - $partecipanti;
    }

    // ===================================
    // SCOPE QUERIES
    // ===================================

    public function scopePubblicati($query)
    {
        return $query->where('stato', 'pubblicato');
    }

    public function scopeFuturi($query)
    {
        return $query->where('data_inizio', '>', now());
    }

    public function scopePassati($query)
    {
        return $query->where('data_fine', '<', now());
    }

    public function scopeInCorso($query)
    {
        return $query->where('data_inizio', '<=', now())
                    ->where('data_fine', '>=', now());
    }

    public function scopePerTipo($query, $tipo)
    {
        return $query->where('tipo_evento', $tipo);
    }

    // ===================================
    // METODI UTILITY
    // ===================================

    public function isFuturo()
    {
        return $this->data_inizio > now();
    }

    public function isPassato()
    {
        return $this->data_fine < now();
    }

    public function isInCorso()
    {
        return $this->data_inizio <= now() && $this->data_fine >= now();
    }

    public function postiEsauriti()
    {
        return $this->max_partecipanti && $this->posti_disponibili <= 0;
    }

    public function iscrizioniAperte()
    {
        if ($this->data_scadenza_iscrizioni && $this->data_scadenza_iscrizioni < now()) {
            return false;
        }
        
        return $this->stato === 'pubblicato' && !$this->postiEsauriti();
    }

    public function generateCodiceEvento()
    {
        $anno = $this->data_inizio ? $this->data_inizio->format('Y') : date('Y');
        $mese = $this->data_inizio ? $this->data_inizio->format('m') : date('m');
        $progressivo = str_pad($this->id, 3, '0', STR_PAD_LEFT);
        
        return "EVT{$anno}{$mese}{$progressivo}";
    }
}