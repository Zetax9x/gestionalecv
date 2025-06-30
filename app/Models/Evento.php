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
        'organizzatore_id',
        'titolo',
        'descrizione',
        'tipo',
        'categoria',
        'data_inizio',
        'data_fine',
        'evento_multiplo',
        'date_aggiuntive',
        'durata_ore',
        'luogo',
        'indirizzo_completo',
        'aula_sala',
        'latitudine',
        'longitudine',
        'max_partecipanti',
        'min_partecipanti',
        'richiede_conferma',
        'lista_attesa',
        'scadenza_iscrizioni',
        'costo_partecipazione',
        'rilascia_attestato',
        'tipo_attestato',
        'crediti_ecm',
        'provider_ecm',
        'docenti',
        'staff',
        'materiali_necessari',
        'prerequisiti',
        'stato',
        'motivo_annullamento',
        'data_annullamento',
        'invia_promemoria',
        'giorni_promemoria',
        'ultimo_promemoria',
        'abilita_feedback',
        'valutazione_media',
        'numero_valutazioni',
        'note',
        'locandina',
        'allegati'
    ];

    protected $casts = [
        'data_inizio' => 'datetime',
        'data_fine' => 'datetime',
        'scadenza_iscrizioni' => 'datetime',
        'data_annullamento' => 'datetime',
        'ultimo_promemoria' => 'datetime',
        'costo_partecipazione' => 'decimal:2',
        'evento_multiplo' => 'boolean',
        'richiede_conferma' => 'boolean',
        'lista_attesa' => 'boolean',
        'rilascia_attestato' => 'boolean',
        'docenti' => 'array',
        'staff' => 'array',
        'giorni_promemoria' => 'array',
        'abilita_feedback' => 'boolean'
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
        return $this->belongsToMany(Volontario::class, 'partecipazioni_eventi')
                    ->withPivot(['stato', 'data_iscrizione', 'note_presenza'])
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
                            ->where('stato', 'confermato')
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
        return $query->where('tipo', $tipo);
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
        if ($this->scadenza_iscrizioni && $this->scadenza_iscrizioni < now()) {
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