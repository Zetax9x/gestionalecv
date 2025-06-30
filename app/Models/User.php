<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'nome', 
        'cognome', 
        'email', 
        'password', 
        'telefono', 
        'data_nascita',
        'codice_fiscale', 
        'indirizzo', 
        'citta',
        'cap',
        'provincia',
        'ruolo', 
        'attivo',
        'avatar',
        'note',
        'dispositivi_autorizzati'
    ];

    protected $hidden = [
        'password', 
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'data_nascita' => 'date',
        'ultimo_accesso' => 'datetime',
        'attivo' => 'boolean',
        'password' => 'hashed',
        'dispositivi_autorizzati' => 'array',
    ];

    // ===================================
    // RELAZIONI
    // ===================================

    /**
     * Relazione con volontario
     */
    public function volontario()
    {
        return $this->hasOne(Volontario::class);
    }
public function notifiche()
{
     return $this->hasMany(Notifica::class, 'user_id');
}

/**
 * Notifiche non lette dell'utente
 */
public function notificheNonLette()
{
     return $this->hasMany(Notifica::class, 'user_id')->whereNull('read_at');
}
    /**
     * Documenti attraverso volontario
     */
    public function documenti()
    {
        return $this->hasManyThrough(Documento::class, Volontario::class);
    }

    /**
     * Tickets creati dall'utente
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Tickets assegnati all'utente
     */
    public function ticketsAssegnati()
    {
        return $this->hasMany(Ticket::class, 'assegnato_a');
    }

    /**
     * Avvisi creati dall'utente
     */
    public function avvisi()
    {
        return $this->hasMany(Avviso::class);
    }

    /**
     * Eventi organizzati
     */
    public function eventiOrganizzati()
    {
        return $this->hasMany(Evento::class, 'organizzatore_id');
    }

    /**
     * Partecipazioni agli eventi
     */
    public function partecipazioniEventi()
    {
        return $this->hasMany(PartecipazioneEvento::class);
    }

    /**
     * Log attività dell'utente
     */
    public function logAttivita()
    {
        return $this->hasMany(LogAttivita::class);
    }

    /**
     * Checklist compilate
     */
    public function checklistCompilate()
    {
        return $this->hasMany(ChecklistCompilata::class);
    }

    /**
     * Movimenti magazzino effettuati
     */
    public function movimentiMagazzino()
    {
        return $this->hasMany(MovimentoMagazzino::class);
    }

    // ===================================
    // ATTRIBUTI COMPUTATI
    // ===================================

    /**
     * Nome completo dell'utente
     */
    public function getNomeCompletoAttribute()
    {
        return trim($this->nome . ' ' . $this->cognome);
    }

    /**
     * Iniziali dell'utente per avatar
     */
    public function getInizialiAttribute()
    {
        return strtoupper(substr($this->nome, 0, 1) . substr($this->cognome, 0, 1));
    }

    /**
     * Età dell'utente
     */
    public function getEtaAttribute()
    {
        return $this->data_nascita ? $this->data_nascita->age : null;
    }

    /**
     * Avatar URL (file o iniziali)
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar && file_exists(storage_path('app/public/' . $this->avatar))) {
            return asset('storage/' . $this->avatar);
        }
        
        // Genera avatar con iniziali
        return "https://ui-avatars.com/api/?name=" . urlencode($this->nome_completo) . 
               "&background=007bff&color=fff&size=150&font-size=0.6";
    }

    /**
     * Colore badge per ruolo
     */
    public function getColoreRuoloAttribute()
    {
        $colori = [
            'admin' => 'danger',
            'direttivo' => 'warning', 
            'segreteria' => 'info',
            'mezzi' => 'success',
            'dipendente' => 'primary',
            'volontario' => 'secondary'
        ];
        
        return $colori[$this->ruolo] ?? 'secondary';
    }

    /**
     * Label leggibile del ruolo
     */
    public function getRuoloLabelAttribute()
    {
        $labels = [
            'admin' => 'Amministratore',
            'direttivo' => 'Direttivo',
            'segreteria' => 'Segreteria',
            'mezzi' => 'Responsabile Mezzi',
            'dipendente' => 'Dipendente',
            'volontario' => 'Volontario'
        ];
        
        return $labels[$this->ruolo] ?? 'Sconosciuto';
    }

    // ===================================
    // METODI ACL E PERMESSI
    // ===================================

    /**
     * Verifica se l'utente ha un permesso specifico
     */
    public function hasPermission($modulo, $azione)
    {
        // Admin ha sempre tutti i permessi
        if ($this->isAdmin()) {
            return true;
        }

        $permission = Permission::where('modulo', $modulo)
                                ->where('ruolo', $this->ruolo)
                                ->first();
        
        return $permission ? $permission->$azione : false;
    }

    /**
     * Verifica se è amministratore
     */
    public function isAdmin()
    {
        return $this->ruolo === 'admin';
    }

    /**
     * Verifica se può accedere alla sezione mezzi
     */
    public function canAccessMezzi()
    {
        return in_array($this->ruolo, ['admin', 'direttivo', 'segreteria', 'mezzi']);
    }

    /**
     * Verifica se può vedere statistiche magazzino
     */
    public function canViewStatisticheMagazzino()
    {
        return in_array($this->ruolo, ['admin', 'mezzi']);
    }

    /**
     * Verifica se può configurare ACL
     */
    public function canConfigureACL()
    {
        return $this->ruolo === 'admin';
    }

    /**
     * Verifica se può vedere i log
     */
    public function canViewLogs()
    {
        return in_array($this->ruolo, ['admin', 'direttivo']);
    }

    /**
     * Ottieni tutti i moduli accessibili dall'utente
     */
    public function getModuliAccessibili()
    {
        if ($this->isAdmin()) {
            return Permission::distinct('modulo')->pluck('modulo')->toArray();
        }

        return Permission::where('ruolo', $this->ruolo)
                        ->where('visualizza', true)
                        ->pluck('modulo')
                        ->toArray();
    }

    // ===================================
    // METODI UTILITY
    // ===================================

    /**
     * Aggiorna ultimo accesso
     */
    public function updateUltimoAccesso()
    {
        $this->update(['ultimo_accesso' => now()]);
    }

    /**
     * Verifica se l'utente è attivo
     */
    public function isAttivo()
    {
        return $this->attivo;
    }

    /**
     * Verifica se è volontario
     */
    public function isVolontario()
    {
        return $this->ruolo === 'volontario' && $this->volontario;
    }

    /**
     * Ottieni notifiche non lette
     */
    public function getNotificheNonLette()
    {
        return Notifica::whereJsonContains('destinatari', $this->id)
                      ->whereJsonDoesntContain('letta_da', $this->id)
                      ->orderBy('created_at', 'desc')
                      ->get();
    }

    /**
     * Conta notifiche non lette
     */
    public function countNotificheNonLette()
    {
        return Notifica::whereJsonContains('destinatari', $this->id)
                      ->whereJsonDoesntContain('letta_da', $this->id)
                      ->count();
    }

    /**
     * Marca notifica come letta
     */
    public function marcaNotificaLetta($notificaId)
    {
        $notifica = Notifica::find($notificaId);
        if ($notifica) {
            $lettaDa = $notifica->letta_da ?? [];
            if (!in_array($this->id, $lettaDa)) {
                $lettaDa[] = $this->id;
                $notifica->update(['letta_da' => $lettaDa]);
            }
        }
    }

    /**
     * Ottieni scadenze personali vicine
     */
    public function getScadenzeVicine($giorni = 30)
    {
        $scadenze = collect();
        
        // Scadenze volontario se applicabile
        if ($this->volontario) {
            $scadenze = $scadenze->merge($this->volontario->scadenze_vicine);
        }
        
        // Scadenze documenti personali
        $documentiInScadenza = $this->documenti()
                                   ->whereNotNull('data_scadenza')
                                   ->whereDate('data_scadenza', '<=', now()->addDays($giorni))
                                   ->whereDate('data_scadenza', '>=', now())
                                   ->get();
                                   
        foreach ($documentiInScadenza as $doc) {
            $scadenze->push([
                'tipo' => 'Documento: ' . $doc->nome_documento,
                'data' => $doc->data_scadenza,
                'giorni' => now()->diffInDays($doc->data_scadenza),
                'url' => route('documenti.show', $doc->id)
            ]);
        }
        
        return $scadenze->sortBy('giorni');
    }

    // ===================================
    // SCOPE QUERIES
    // ===================================

    /**
     * Scope per utenti attivi
     */
    public function scopeAttivi($query)
    {
        return $query->where('attivo', true);
    }

    /**
     * Scope per ruolo specifico
     */
    public function scopeConRuolo($query, $ruolo)
    {
        return $query->where('ruolo', $ruolo);
    }

    /**
     * Scope per ricerca
     */
    public function scopeRicerca($query, $termine)
    {
        return $query->where(function($q) use ($termine) {
            $q->where('nome', 'like', "%{$termine}%")
              ->orWhere('cognome', 'like', "%{$termine}%")
              ->orWhere('email', 'like', "%{$termine}%")
              ->orWhere('telefono', 'like', "%{$termine}%");
        });
    }
}
    // Relazioni notifiche corrette
    public function notifiche()
    {
        return $this->hasMany(Notifica::class);
    }

    public function notificheNonLette()
    {
        return $this->hasMany(Notifica::class)->whereNull('read_at');
    }

    public function countNotificheNonLette()
    {
        return $this->notificheNonLette()->count();
    }

    public function getNotificheNonLette()
    {
        return $this->notificheNonLette()->orderBy('created_at', 'desc')->get();
    }

    public function marcaNotificaLetta($notificaId)
    {
        $notifica = $this->notifiche()->find($notificaId);
        if ($notifica) {
            $notifica->marcaComeLetta();
        }
    }
}
