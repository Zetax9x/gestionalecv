<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Notifica extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'notifiche';

    protected $fillable = [
        'destinatari',
        'titolo',
        'messaggio',
        'tipo',
        'letta_da',
        'user_id',
        'priorita',
        'url_azione',
        'testo_azione',
        'scade_il',
        'metadati'
    ];

    protected $casts = [
        'destinatari' => 'array',
        'letta_da' => 'array',
        'metadati' => 'array'
    ];

    // ===================================
    // ATTRIBUTI COMPUTATI
    // ===================================

    public function getTipoLabelAttribute()
    {
        $tipi = [
            'scadenza' => 'Scadenza',
            'sottoscorta' => 'Sottoscorta Magazzino',
            'segnalazione' => 'Segnalazione',
            'ticket' => 'Ticket',
            'manutenzione' => 'Manutenzione',
            'formazione' => 'Formazione',
            'evento' => 'Evento',
            'risoluzione_critica' => 'Risoluzione Critica',
            'sistema' => 'Sistema',
            'generale' => 'Generale'
        ];
        
        return $tipi[$this->tipo] ?? 'Generale';
    }

    public function getIconaAttribute()
    {
        $icone = [
            'scadenza' => 'fas fa-exclamation-triangle',
            'sottoscorta' => 'fas fa-box-open',
            'segnalazione' => 'fas fa-flag',
            'ticket' => 'fas fa-ticket-alt',
            'manutenzione' => 'fas fa-wrench',
            'formazione' => 'fas fa-graduation-cap',
            'evento' => 'fas fa-calendar-alt',
            'risoluzione_critica' => 'fas fa-check-circle',
            'sistema' => 'fas fa-cog',
            'generale' => 'fas fa-bell'
        ];
        
        return $icone[$this->tipo] ?? 'fas fa-bell';
    }

    public function getColoreAttribute()
    {
        $colori = [
            'scadenza' => 'warning',
            'sottoscorta' => 'danger',
            'segnalazione' => 'info',
            'ticket' => 'primary',
            'manutenzione' => 'warning',
            'formazione' => 'success',
            'evento' => 'info',
            'risoluzione_critica' => 'success',
            'sistema' => 'secondary',
            'generale' => 'primary'
        ];
        
        return $colori[$this->tipo] ?? 'primary';
    }

    public function getNumeroDestinatariAttribute()
    {
        return count($this->destinatari ?? []);
    }

    public function getNumeroLetteAttribute()
    {
        return count($this->letta_da ?? []);
    }

    public function getPercentualeLetturaAttribute()
    {
        if ($this->numero_destinatari == 0) return 0;
        
        return round(($this->numero_lette / $this->numero_destinatari) * 100, 1);
    }

    public function getDataFormattataAttribute()
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    public function getTempoTrascorsoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getUrgenzaAttribute()
    {
        // Determina urgenza in base al tipo e contenuto
        $tipiUrgenti = ['risoluzione_critica', 'sottoscorta', 'scadenza'];
        
        if (in_array($this->tipo, $tipiUrgenti)) {
            return 'alta';
        }
        
        // Verifica parole chiave urgenti nel titolo/messaggio
        $paroleCritiche = ['urgente', 'critico', 'immediato', 'scaduto', 'emergenza'];
        $testo = strtolower($this->titolo . ' ' . $this->messaggio);
        
        foreach ($paroleCritiche as $parola) {
            if (strpos($testo, $parola) !== false) {
                return 'alta';
            }
        }
        
        return 'normale';
    }

    public function getClasseUrgenzaAttribute()
    {
        return $this->urgenza === 'alta' ? 'text-danger fw-bold' : '';
    }

    // ===================================
    // METODI UTILITY
    // ===================================

    /**
     * Crea una nuova notifica
     */
    public static function crea($dati)
    {
        // Normalizza i destinatari
        if (isset($dati['destinatari'])) {
            if (!is_array($dati['destinatari'])) {
                $dati['destinatari'] = [$dati['destinatari']];
            }
            
            // Rimuovi duplicati e valori null
            $dati['destinatari'] = array_unique(array_filter($dati['destinatari']));
        }

        $notifica = self::create($dati);

        // Log creazione notifica
        LogAttivita::create([
            'user_id' => auth()->id(),
            'azione' => 'creazione_notifica',
            'modulo' => 'notifiche',
            'risorsa_id' => $notifica->id,
            'descrizione' => "Notifica creata: {$notifica->titolo}",
            'note' => "Destinatari: " . count($notifica->destinatari),
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'System',
            'data_ora' => now()
        ]);

        return $notifica;
    }

    /**
     * Marca come letta da un utente
     */
    public function marcaComeLetta($userId)
    {
        $lettaDa = $this->letta_da ?? [];
        
        if (!in_array($userId, $lettaDa)) {
            $lettaDa[] = $userId;
            $this->update(['letta_da' => $lettaDa]);
            
            return true;
        }
        
        return false;
    }

    /**
     * Marca come non letta da un utente
     */
    public function marcaComeNonLetta($userId)
    {
        $lettaDa = $this->letta_da ?? [];
        $indice = array_search($userId, $lettaDa);
        
        if ($indice !== false) {
            unset($lettaDa[$indice]);
            $this->update(['letta_da' => array_values($lettaDa)]);
            
            return true;
        }
        
        return false;
    }

    /**
     * Verifica se è stata letta da un utente
     */
    public function isLettaDa($userId)
    {
        return in_array($userId, $this->letta_da ?? []);
    }

    /**
     * Verifica se l'utente è destinatario
     */
    public function isDestinatario($userId)
    {
        return in_array($userId, $this->destinatari ?? []);
    }

    /**
     * Ottieni utenti destinatari
     */
    public function getUtentiDestinatari()
    {
        if (empty($this->destinatari)) {
            return collect();
        }
        
        return User::whereIn('id', $this->destinatari)->get();
    }

    /**
     * Ottieni utenti che hanno letto
     */
    public function getUtentiCheHannoLetto()
    {
        if (empty($this->letta_da)) {
            return collect();
        }
        
        return User::whereIn('id', $this->letta_da)->get();
    }

    /**
     * Ottieni utenti che non hanno letto
     */
    public function getUtentiNonLetto()
    {
        $destinatari = $this->destinatari ?? [];
        $lettaDa = $this->letta_da ?? [];
        $nonLetto = array_diff($destinatari, $lettaDa);
        
        if (empty($nonLetto)) {
            return collect();
        }
        
        return User::whereIn('id', $nonLetto)->get();
    }

    /**
     * Aggiungi destinatari
     */
    public function aggiungiDestinatari($utentiIds)
    {
        if (!is_array($utentiIds)) {
            $utentiIds = [$utentiIds];
        }
        
        $destinatariAttuali = $this->destinatari ?? [];
        $nuoviDestinatari = array_unique(array_merge($destinatariAttuali, $utentiIds));
        
        $this->update(['destinatari' => $nuoviDestinatari]);
        
        return $this;
    }

    /**
     * Rimuovi destinatari
     */
    public function rimuoviDestinatari($utentiIds)
    {
        if (!is_array($utentiIds)) {
            $utentiIds = [$utentiIds];
        }
        
        $destinatariAttuali = $this->destinatari ?? [];
        $nuoviDestinatari = array_diff($destinatariAttuali, $utentiIds);
        
        // Rimuovi anche da letta_da se presenti
        $lettaDaAttuali = $this->letta_da ?? [];
        $nuoviLettaDa = array_diff($lettaDaAttuali, $utentiIds);
        
        $this->update([
            'destinatari' => array_values($nuoviDestinatari),
            'letta_da' => array_values($nuoviLettaDa)
        ]);
        
        return $this;
    }

    /**
     * Duplica notifica per nuovi destinatari
     */
    public function duplicaPer($nuoviDestinatari, $modifiche = [])
    {
        $dati = $this->toArray();
        $dati['destinatari'] = is_array($nuoviDestinatari) ? $nuoviDestinatari : [$nuoviDestinatari];
        $dati['letta_da'] = [];
        unset($dati['id'], $dati['created_at'], $dati['updated_at'], $dati['deleted_at']);
        
        // Applica eventuali modifiche
        $dati = array_merge($dati, $modifiche);
        
        return self::create($dati);
    }

    // ===================================
    // METODI STATICI SPECIALIZZATI
    // ===================================

    /**
     * Crea notifica di scadenza
     */
    public static function notificaScadenza($destinatari, $oggetto, $dataScadenza, $dettagli = [])
    {
        $giorni = now()->diffInDays($dataScadenza, false);
        $urgenza = $giorni <= 7 ? ' - URGENTE' : '';
        
        return self::crea([
            'destinatari' => $destinatari,
            'titolo' => 'Scadenza ' . $oggetto . $urgenza,
            'messaggio' => "Il/La {$oggetto} scade tra {$giorni} giorni ({$dataScadenza->format('d/m/Y')})",
            'tipo' => 'scadenza',
            ...$dettagli
        ]);
    }

    /**
     * Crea notifica sottoscorta
     */
    public static function notificaSottoscorta($destinatari, $articolo, $quantitaAttuale, $quantitaMinima, $unitaMisura = 'pezzi')
    {
        return self::crea([
            'destinatari' => $destinatari,
            'titolo' => 'Articolo Sottoscorta',
            'messaggio' => "L'articolo '{$articolo}' è sotto la quantità minima. Disponibili: {$quantitaAttuale} {$unitaMisura} (Minimo: {$quantitaMinima})",
            'tipo' => 'sottoscorta'
        ]);
    }

    /**
     * Crea notifica per tutti gli utenti di un ruolo
     */
    public static function notificaPerRuolo($ruolo, $titolo, $messaggio, $tipo = 'generale')
    {
        $utenti = User::where('ruolo', $ruolo)->where('attivo', true)->pluck('id')->toArray();
        
        if (empty($utenti)) {
            return null;
        }
        
        return self::crea([
            'destinatari' => $utenti,
            'titolo' => $titolo,
            'messaggio' => $messaggio,
            'tipo' => $tipo
        ]);
    }

    /**
     * Crea notifica per amministratori
     */
    public static function notificaAdmin($titolo, $messaggio, $tipo = 'sistema')
    {
        return self::notificaPerRuolo('admin', $titolo, $messaggio, $tipo);
    }

    /**
     * Crea notifica broadcast (tutti gli utenti attivi)
     */
    public static function notificaBroadcast($titolo, $messaggio, $tipo = 'generale', $escludiRuoli = [])
    {
        $query = User::where('attivo', true);
        
        if (!empty($escludiRuoli)) {
            $query->whereNotIn('ruolo', $escludiRuoli);
        }
        
        $utenti = $query->pluck('id')->toArray();
        
        return self::crea([
            'destinatari' => $utenti,
            'titolo' => $titolo,
            'messaggio' => $messaggio,
            'tipo' => $tipo
        ]);
    }

    // ===================================
    // STATISTICHE E REPORT
    // ===================================

    public static function getStatisticheNotifiche()
    {
        return [
            'totale_notifiche' => self::count(),
            'notifiche_oggi' => self::whereDate('created_at', today())->count(),
            'notifiche_settimana' => self::where('created_at', '>=', now()->subWeek())->count(),
            'notifiche_mese' => self::whereMonth('created_at', now()->month)
                                   ->whereYear('created_at', now()->year)
                                   ->count(),
            'notifiche_per_tipo' => self::selectRaw('tipo, COUNT(*) as count')
                                       ->groupBy('tipo')
                                       ->pluck('count', 'tipo'),
            'tasso_lettura_medio' => self::selectRaw('AVG(
                                        CASE 
                                            WHEN JSON_LENGTH(destinatari) > 0 
                                            THEN (JSON_LENGTH(COALESCE(letta_da, "[]")) * 100.0 / JSON_LENGTH(destinatari))
                                            ELSE 0 
                                        END
                                    ) as tasso')
                                        ->value('tasso') ?? 0
        ];
    }

    public static function getNotificheNonLette($userId)
    {
        return self::whereJsonContains('destinatari', $userId)
                   ->whereJsonDoesntContain('letta_da', $userId)
                   ->orderBy('created_at', 'desc')
                   ->get();
    }

    public static function getNotificheRecenti($userId, $limite = 20)
    {
        return self::whereJsonContains('destinatari', $userId)
                   ->orderBy('created_at', 'desc')
                   ->limit($limite)
                   ->get();
    }

    public static function pulisciNotificheVecchie($giorni = 90)
    {
        $count = self::where('created_at', '<', now()->subDays($giorni))->count();
        self::where('created_at', '<', now()->subDays($giorni))->delete();
        
        return $count;
    }

    // ===================================
    // SCOPE QUERIES
    // ===================================

    public function scopePerUtente($query, $userId)
    {
        return $query->whereJsonContains('destinatari', $userId);
    }

    public function scopeNonLette($query, $userId)
    {
        return $query->whereJsonContains('destinatari', $userId)
                     ->whereJsonDoesntContain('letta_da', $userId);
    }

    public function scopeLette($query, $userId)
    {
        return $query->whereJsonContains('destinatari', $userId)
                     ->whereJsonContains('letta_da', $userId);
    }

    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeRecenti($query, $giorni = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($giorni));
    }

    public function scopeUrgenti($query)
    {
        return $query->whereIn('tipo', ['risoluzione_critica', 'sottoscorta', 'scadenza'])
                     ->orWhere(function($q) {
                         $q->where('titolo', 'like', '%urgente%')
                           ->orWhere('titolo', 'like', '%critico%')
                           ->orWhere('messaggio', 'like', '%urgente%')
                           ->orWhere('messaggio', 'like', '%critico%');
                     });
    }

    public function scopeRicerca($query, $termine)
    {
        return $query->where(function($q) use ($termine) {
            $q->where('titolo', 'like', "%{$termine}%")
              ->orWhere('messaggio', 'like', "%{$termine}%");
        });
    }

    // ===================================
    // EVENTI MODEL
    // ===================================

    protected static function boot()
    {
        parent::boot();

        // Log quando una notifica viene creata
        static::created(function ($notifica) {
            // Potresti aggiungere qui logiche aggiuntive per l'invio
            // di notifiche push, email, SMS, ecc.
        });
    }
}