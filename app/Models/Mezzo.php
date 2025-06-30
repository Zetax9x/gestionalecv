<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Mezzo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mezzi';

    protected $fillable = [
        'targa',
        'tipo',
        'marca',
        'modello',
        'anno',
        'numero_telaio',
        'colore',
        'alimentazione',
        'scadenza_revisione',
        'scadenza_assicurazione',
        'compagnia_assicurazione',
        'numero_polizza',
        'scadenza_bollo',
        'scadenza_collaudo',
        'km_attuali',
        'km_ultimo_tagliando',
        'km_prossimo_tagliando',
        'intervallo_tagliando',
        'data_ultimo_tagliando',
        'dotazioni_sanitarie',
        'dotazioni_tecniche',
        'aria_condizionata',
        'gps',
        'radio_ponte',
        'frequenza_radio',
        'note',
        'attivo',
        'in_servizio',
        'data_dismissione',
        'motivo_dismissione',
        'costo_acquisto',
        'data_acquisto',
        'fornitore',
        'posizione_attuale',
        'ultimo_user_id',
        'ultimo_utilizzo'
    ];

    protected $casts = [
        'scadenza_revisione' => 'date',
        'scadenza_assicurazione' => 'date',
        'scadenza_bollo' => 'date',
        'scadenza_collaudo' => 'date',
        'data_ultimo_tagliando' => 'date',
        'dotazioni_sanitarie' => 'array',
        'dotazioni_tecniche' => 'array',
        'aria_condizionata' => 'boolean',
        'gps' => 'boolean',
        'radio_ponte' => 'boolean',
        'attivo' => 'boolean',
        'in_servizio' => 'boolean',
        'data_dismissione' => 'datetime',
        'costo_acquisto' => 'decimal:2',
        'data_acquisto' => 'date',
        'ultimo_utilizzo' => 'datetime'
    ];

    // ===================================
    // RELAZIONI
    // ===================================

    public function manutenzioni()
    {
        return $this->hasMany(Manutenzione::class);
    }

    public function ultimaManutenzione()
    {
        return $this->hasOne(Manutenzione::class)->latest('data_manutenzione');
    }

    public function checklistCompilate()
    {
        return $this->hasMany(ChecklistCompilata::class);
    }

    public function ultimaChecklist()
    {
        return $this->hasOne(ChecklistCompilata::class)->latest('data_compilazione');
    }

    public function checklistNonConformi()
    {
        return $this->hasMany(ChecklistCompilata::class)->where('conforme', false);
    }

    public function ultimoUtente()
    {
        return $this->belongsTo(User::class, 'ultimo_user_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function ticketsAperti()
    {
        return $this->hasMany(Ticket::class)->whereIn('stato', ['aperto', 'assegnato', 'in_corso']);
    }

    // ===================================
    // ATTRIBUTI COMPUTATI
    // ===================================

    public function getTipoDescrizioneAttribute()
    {
        $tipi = [
            'ambulanza_a' => 'Ambulanza Tipo A',
            'ambulanza_b' => 'Ambulanza Tipo B',
            'auto_medica' => 'Auto Medica',
            'auto_servizio' => 'Auto di Servizio',
            'furgone' => 'Furgone',
            'altro' => 'Altro Veicolo'
        ];
        
        return $tipi[$this->tipo] ?? 'Non Specificato';
    }

    public function getEtaVeicoloAttribute()
    {
        return now()->year - $this->anno;
    }

    public function getColoreStatoAttribute()
    {
        if (!$this->attivo) return 'danger';
        if (!$this->in_servizio) return 'warning';
        
        $scadenzeVicine = $this->scadenze_vicine;
        if ($scadenzeVicine->where('urgente', true)->isNotEmpty()) return 'danger';
        if ($scadenzeVicine->isNotEmpty()) return 'warning';
        
        return 'success';
    }

    public function getStatoDescrizioneAttribute()
    {
        if (!$this->attivo) return 'Fuori Servizio';
        if (!$this->in_servizio) return 'Manutenzione';
        
        $scadenzeVicine = $this->scadenze_vicine;
        if ($scadenzeVicine->where('urgente', true)->isNotEmpty()) return 'Scadenze Urgenti';
        if ($scadenzeVicine->isNotEmpty()) return 'Scadenze Vicine';
        
        return 'Operativo';
    }

    public function getScadenzeVicineAttribute()
    {
        $scadenze = collect();
        
        $campiScadenza = [
            'scadenza_revisione' => 'Revisione',
            'scadenza_assicurazione' => 'Assicurazione',
            'scadenza_bollo' => 'Bollo Auto',
            'scadenza_collaudo' => 'Collaudo'
        ];
        
        foreach ($campiScadenza as $campo => $tipo) {
            if ($this->$campo) {
                $giorni = now()->diffInDays($this->$campo, false);
                if ($giorni <= 30 && $giorni >= 0) {
                    $scadenze->push([
                        'tipo' => $tipo,
                        'data' => $this->$campo,
                        'giorni' => $giorni,
                        'urgente' => $giorni <= 7,
                        'url' => route('mezzi.edit', $this->id)
                    ]);
                }
            }
        }
        
        // Prossimo tagliando
        if ($this->km_prossimo_tagliando) {
            $kmMancanti = $this->km_prossimo_tagliando - $this->km_attuali;
            if ($kmMancanti <= 1000 && $kmMancanti >= 0) {
                $scadenze->push([
                    'tipo' => 'Tagliando',
                    'data' => null,
                    'km_mancanti' => $kmMancanti,
                    'urgente' => $kmMancanti <= 200,
                    'url' => route('mezzi.edit', $this->id)
                ]);
            }
        }
        
        return $scadenze->sortBy('giorni');
    }

    public function getCostoManutenzioniAnnoAttribute()
    {
        return $this->manutenzioni()
                   ->whereYear('data_manutenzione', now()->year)
                   ->sum('costo') ?? 0;
    }

    public function getKmMedioMensileAttribute()
    {
        if (!$this->data_acquisto) return 0;
        
        $mesiPossesso = $this->data_acquisto->diffInMonths(now());
        if ($mesiPossesso == 0) return 0;
        
        return round($this->km_attuali / $mesiPossesso);
    }

    public function getEfficienzaAttribute()
    {
        $costoTotale = $this->manutenzioni()->sum('costo');
        if ($costoTotale == 0) return null;
        
        return round($this->km_attuali / $costoTotale, 2);
    }

    // ===================================
    // METODI UTILITY
    // ===================================

    public function necessitaManutenzione()
    {
        // Verifica km per tagliando
        if ($this->km_prossimo_tagliando && $this->km_attuali >= $this->km_prossimo_tagliando) {
            return true;
        }
        
        // Verifica scadenze documenti
        $scadenzeScadute = $this->scadenze_vicine->where('giorni', '<=', 0);
        if ($scadenzeScadute->isNotEmpty()) {
            return true;
        }
        
        // Verifica checklist non conformi recenti
        $checklistNonConformi = $this->checklistNonConformi()
                                    ->where('data_compilazione', '>=', now()->subDays(7))
                                    ->count();
        
        return $checklistNonConformi > 0;
    }

    public function aggiornaKm($nuoviKm, $userId = null)
    {
        if ($nuoviKm > $this->km_attuali) {
            $this->update([
                'km_attuali' => $nuoviKm,
                'ultimo_user_id' => $userId ?? auth()->id(),
                'ultimo_utilizzo' => now()
            ]);
            
            // Verifica se necessita tagliando
            if ($this->km_prossimo_tagliando && $nuoviKm >= $this->km_prossimo_tagliando) {
                $this->creaNotificaTagliando();
            }
        }
    }

    private function creaNotificaTagliando()
    {
        $utentiMezzi = User::where('ruolo', 'mezzi')->where('attivo', true)->pluck('id');
        
        Notifica::create([
            'destinatari' => $utentiMezzi->toArray(),
            'titolo' => 'Tagliando Necessario',
            'messaggio' => "Il mezzo {$this->targa} ({$this->tipo_descrizione}) ha raggiunto i km per il tagliando programmato.",
            'tipo' => 'manutenzione'
        ]);
    }

    public function registraManutenzione($dati)
    {
        $manutenzione = $this->manutenzioni()->create($dati);
        
        // Aggiorna dati mezzo se è un tagliando
        if ($dati['tipo'] === 'tagliando') {
            $this->update([
                'km_ultimo_tagliando' => $dati['km_effettuati'],
                'km_prossimo_tagliando' => $dati['km_effettuati'] + $this->intervallo_tagliando,
                'data_ultimo_tagliando' => $dati['data_manutenzione']
            ]);
        }
        
        return $manutenzione;
    }

    public function mettiFuoriServizio($motivo = null)
    {
        $this->update([
            'in_servizio' => false,
            'posizione_attuale' => 'officina'
        ]);
        
        LogAttivita::create([
            'user_id' => auth()->id(),
            'azione' => 'fuori_servizio',
            'modulo' => 'mezzi',
            'risorsa_id' => $this->id,
            'descrizione' => "Mezzo {$this->targa} messo fuori servizio",
            'note' => $motivo,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data_ora' => now()
        ]);
    }

    public function rimettiInServizio()
    {
        $this->update([
            'in_servizio' => true,
            'posizione_attuale' => 'sede'
        ]);
        
        LogAttivita::create([
            'user_id' => auth()->id(),
            'azione' => 'in_servizio',
            'modulo' => 'mezzi',
            'risorsa_id' => $this->id,
            'descrizione' => "Mezzo {$this->targa} rimesso in servizio",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data_ora' => now()
        ]);
    }

    // ===================================
    // SCOPE QUERIES
    // ===================================

    public function scopeAttivi($query)
    {
        return $query->where('attivo', true);
    }

    public function scopeInServizio($query)
    {
        return $query->where('in_servizio', true);
    }

    public function scopePerTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeConScadenzeVicine($query, $giorni = 30)
    {
        return $query->where(function($q) use ($giorni) {
            $q->whereDate('scadenza_revisione', '<=', now()->addDays($giorni))
              ->whereDate('scadenza_revisione', '>=', now())
              ->orWhereDate('scadenza_assicurazione', '<=', now()->addDays($giorni))
              ->whereDate('scadenza_assicurazione', '>=', now())
              ->orWhereDate('scadenza_bollo', '<=', now()->addDays($giorni))
              ->whereDate('scadenza_bollo', '>=', now());
        });
    }

    public function scopeRicerca($query, $termine)
    {
        return $query->where(function($q) use ($termine) {
            $q->where('targa', 'like', "%{$termine}%")
              ->orWhere('marca', 'like', "%{$termine}%")
              ->orWhere('modello', 'like', "%{$termine}%")
              ->orWhere('tipo', 'like', "%{$termine}%");
        });
    }
}