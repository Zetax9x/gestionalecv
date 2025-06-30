<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'numero_ticket',
        'user_id',
        'assegnato_a',
        'titolo',
        'descrizione',
        'categoria',
        'sottocategoria',
        'priorita',
        'urgenza',
        'blocca_operativita',
        'stato',
        'data_apertura',
        'data_assegnazione',
        'data_inizio_lavori',
        'data_risoluzione',
        'data_chiusura',
        'tempo_risoluzione_ore',
        'mezzo_id',
        'dpi_id',
        'articolo_magazzino_id',
        'ubicazione_problema',
        'soluzione_adottata',
        'note_tecniche',
        'costo_riparazione',
        'fornitore_servizio',
        'richiede_follow_up',
        'data_follow_up',
        'valutazione_richiedente',
        'feedback_richiedente',
        'data_feedback',
        'richiede_approvazione',
        'approvato_da',
        'data_approvazione',
        'note_approvazione',
        'notificati',
        'ultima_notifica',
        'note'
    ];

    protected $casts = [
        'blocca_operativita' => 'boolean',
        'data_apertura' => 'datetime',
        'data_assegnazione' => 'datetime',
        'data_inizio_lavori' => 'datetime',
        'data_risoluzione' => 'datetime',
        'data_chiusura' => 'datetime',
        'data_follow_up' => 'date',
        'costo_riparazione' => 'decimal:2',
        'richiede_follow_up' => 'boolean',
        'data_feedback' => 'datetime',
        'richiede_approvazione' => 'boolean',
        'data_approvazione' => 'datetime',
        'notificati' => 'array',
        'ultima_notifica' => 'datetime'
    ];

    // ===================================
    // RELAZIONI
    // ===================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assegnatario()
    {
        return $this->belongsTo(User::class, 'assegnato_a');
    }

    public function approvatore()
    {
        return $this->belongsTo(User::class, 'approvato_da');
    }

    public function mezzo()
    {
        return $this->belongsTo(Mezzo::class);
    }

    public function dpi()
    {
        return $this->belongsTo(Dpi::class);
    }

    public function articoloMagazzino()
    {
        return $this->belongsTo(Magazzino::class, 'articolo_magazzino_id');
    }

    public function allegati()
    {
        return $this->hasMany(AllegatoTicket::class);
    }

    // ===================================
    // ATTRIBUTI COMPUTATI
    // ===================================

    public function getCategoriaLabelAttribute()
    {
        $categorie = [
            'mezzi' => 'Mezzi e Veicoli',
            'dpi' => 'Dispositivi di Protezione',
            'magazzino' => 'Gestione Magazzino',
            'strutture' => 'Strutture e Impianti',
            'informatica' => 'Sistemi Informatici',
            'formazione' => 'Formazione e Corsi',
            'amministrativo' => 'Questioni Amministrative',
            'sicurezza' => 'Sicurezza e Normative',
            'altro' => 'Altro'
        ];
        
        return $categorie[$this->categoria] ?? $this->categoria;
    }

    public function getPrioritaLabelAttribute()
    {
        $priorita = [
            'bassa' => 'Bassa',
            'media' => 'Media',
            'alta' => 'Alta',
            'critica' => 'Critica'
        ];
        
        return $priorita[$this->priorita] ?? $this->priorita;
    }

    public function getUrgenzaLabelAttribute()
    {
        $urgenza = [
            'non_urgente' => 'Non Urgente',
            'normale' => 'Normale',
            'urgente' => 'Urgente',
            'critica' => 'Critica'
        ];
        
        return $urgenza[$this->urgenza] ?? $this->urgenza;
    }

    public function getStatoLabelAttribute()
    {
        $stati = [
            'aperto' => 'Aperto',
            'assegnato' => 'Assegnato',
            'in_corso' => 'In Corso',
            'in_attesa_parti' => 'In Attesa Parti',
            'in_attesa_approvazione' => 'In Attesa Approvazione',
            'risolto' => 'Risolto',
            'chiuso' => 'Chiuso',
            'annullato' => 'Annullato'
        ];
        
        return $stati[$this->stato] ?? $this->stato;
    }

    public function getColorePrioritaAttribute()
    {
        $colori = [
            'bassa' => 'success',
            'media' => 'info',
            'alta' => 'warning',
            'critica' => 'danger'
        ];
        
        return $colori[$this->priorita] ?? 'secondary';
    }

    public function getColoreStatoAttribute()
    {
        $colori = [
            'aperto' => 'danger',
            'assegnato' => 'warning',
            'in_corso' => 'info',
            'in_attesa_parti' => 'secondary',
            'in_attesa_approvazione' => 'warning',
            'risolto' => 'success',
            'chiuso' => 'dark',
            'annullato' => 'secondary'
        ];
        
        return $colori[$this->stato] ?? 'secondary';
    }

    public function getTempoRisoluzioneTotaleAttribute()
    {
        if ($this->data_risoluzione && $this->data_apertura) {
            return $this->data_apertura->diffInHours($this->data_risoluzione);
        }
        
        return null;
    }

    public function getTempoRispostaAttribute()
    {
        if ($this->data_assegnazione && $this->data_apertura) {
            return $this->data_apertura->diffInHours($this->data_assegnazione);
        }
        
        return null;
    }

    public function getInRitardoAttribute()
    {
        if (in_array($this->stato, ['risolto', 'chiuso', 'annullato'])) {
            return false;
        }

        $sla = $this->getSlaOre();
        $oreTrascorse = $this->data_apertura->diffInHours(now());
        
        return $oreTrascorse > $sla;
    }

    public function getOreResidueSlaAttribute()
    {
        if (in_array($this->stato, ['risolto', 'chiuso', 'annullato'])) {
            return null;
        }

        $sla = $this->getSlaOre();
        $oreTrascorse = $this->data_apertura->diffInHours(now());
        
        return max(0, $sla - $oreTrascorse);
    }

    private function getSlaOre()
    {
        $sla = [
            'critica' => 4,
            'alta' => 24,
            'media' => 72,
            'bassa' => 168
        ];
        
        return $sla[$this->priorita] ?? 72;
    }

    public function getProgressoAttribute()
    {
        $fasi = [
            'aperto' => 10,
            'assegnato' => 25,
            'in_corso' => 50,
            'in_attesa_parti' => 60,
            'in_attesa_approvazione' => 80,
            'risolto' => 90,
            'chiuso' => 100,
            'annullato' => 0
        ];
        
        return $fasi[$this->stato] ?? 0;
    }

    public function getRisorseCollegateAttribute()
    {
        $risorse = [];
        
        if ($this->mezzo) {
            $risorse[] = [
                'tipo' => 'Mezzo',
                'nome' => $this->mezzo->targa . ' - ' . $this->mezzo->tipo_descrizione,
                'url' => route('mezzi.show', $this->mezzo->id)
            ];
        }
        
        if ($this->dpi) {
            $risorse[] = [
                'tipo' => 'DPI',
                'nome' => $this->dpi->nome . ' (' . $this->dpi->codice_dpi . ')',
                'url' => route('dpi.show', $this->dpi->id)
            ];
        }
        
        if ($this->articoloMagazzino) {
            $risorse[] = [
                'tipo' => 'Magazzino',
                'nome' => $this->articoloMagazzino->nome_articolo,
                'url' => route('magazzino.show', $this->articoloMagazzino->id)
            ];
        }
        
        return $risorse;
    }

    // ===================================
    // METODI UTILITY
    // ===================================

    public static function generaNumeroTicket()
    {
        $anno = now()->year;
        $ultimoNumero = self::where('numero_ticket', 'like', 'T' . $anno . '%')
                           ->max('numero_ticket');
                           
        if ($ultimoNumero) {
            $numero = intval(substr($ultimoNumero, -5)) + 1;
        } else {
            $numero = 1;
        }
        
        return 'T' . $anno . str_pad($numero, 5, '0', STR_PAD_LEFT);
    }

    public function assegnaA($userId, $note = null)
    {
        $this->update([
            'assegnato_a' => $userId,
            'stato' => 'assegnato',
            'data_assegnazione' => now(),
            'note' => $this->note . ($note ? "\n[" . now()->format('d/m/Y H:i') . "] Assegnato: {$note}" : "")
        ]);

        // Notifica all'assegnatario
        $this->notificaUtente($userId, 'Ticket Assegnato', "Ti è stato assegnato il ticket #{$this->numero_ticket}: {$this->titolo}");

        // Log attività
        $assegnatario = User::find($userId);
        LogAttivita::create([
            'user_id' => auth()->id(),
            'azione' => 'assegnazione_ticket',
            'modulo' => 'tickets',
            'risorsa_id' => $this->id,
            'descrizione' => "Ticket #{$this->numero_ticket} assegnato a {$assegnatario->nome_completo}",
            'note' => $note,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data_ora' => now()
        ]);

        return $this;
    }

    public function iniziaLavori($note = null)
    {
        $this->update([
            'stato' => 'in_corso',
            'data_inizio_lavori' => now(),
            'note' => $this->note . "\n[" . now()->format('d/m/Y H:i') . "] Lavori iniziati" . ($note ? ": {$note}" : "")
        ]);

        // Notifica al richiedente
        $this->notificaUtente($this->user_id, 'Lavori Iniziati', "Sono iniziati i lavori per il ticket #{$this->numero_ticket}: {$this->titolo}");

        return $this;
    }

    public function risolvi($soluzione, $costo = null, $fornitore = null, $richiedeFollowUp = false, $dataFollowUp = null)
    {
        $datiAggiornamento = [
            'stato' => 'risolto',
            'data_risoluzione' => now(),
            'soluzione_adottata' => $soluzione,
            'tempo_risoluzione_ore' => $this->tempo_risoluzione_totale,
            'richiede_follow_up' => $richiedeFollowUp
        ];

        if ($costo) {
            $datiAggiornamento['costo_riparazione'] = $costo;
        }

        if ($fornitore) {
            $datiAggiornamento['fornitore_servizio'] = $fornitore;
        }

        if ($richiedeFollowUp && $dataFollowUp) {
            $datiAggiornamento['data_follow_up'] = $dataFollowUp;
        }

        $datiAggiornamento['note'] = $this->note . "\n[" . now()->format('d/m/Y H:i') . "] Risolto: {$soluzione}";

        $this->update($datiAggiornamento);

        // Notifica al richiedente
        $this->notificaUtente($this->user_id, 'Ticket Risolto', "Il ticket #{$this->numero_ticket} è stato risolto: {$this->titolo}");

        // Se blocca operatività, notifica responsabili
        if ($this->blocca_operativita) {
            $this->notificaRisoluzioneCritica();
        }

        return $this;
    }

    public function chiudi($valutazione = null, $feedback = null)
    {
        $datiAggiornamento = [
            'stato' => 'chiuso',
            'data_chiusura' => now()
        ];

        if ($valutazione) {
            $datiAggiornamento['valutazione_richiedente'] = $valutazione;
            $datiAggiornamento['data_feedback'] = now();
        }

        if ($feedback) {
            $datiAggiornamento['feedback_richiedente'] = $feedback;
        }

        $datiAggiornamento['note'] = $this->note . "\n[" . now()->format('d/m/Y H:i') . "] Chiuso" . ($feedback ? ": {$feedback}" : "");

        $this->update($datiAggiornamento);

        // Notifica all'assegnatario se diverso dal richiedente
        if ($this->assegnato_a && $this->assegnato_a != $this->user_id) {
            $this->notificaUtente($this->assegnato_a, 'Ticket Chiuso', "Il ticket #{$this->numero_ticket} è stato chiuso dal richiedente");
        }

        return $this;
    }

    public function annulla($motivo)
    {
        $this->update([
            'stato' => 'annullato',
            'data_chiusura' => now(),
            'note' => $this->note . "\n[" . now()->format('d/m/Y H:i') . "] Annullato: {$motivo}"
        ]);

        // Notifica all'assegnatario se presente
        if ($this->assegnato_a) {
            $this->notificaUtente($this->assegnato_a, 'Ticket Annullato', "Il ticket #{$this->numero_ticket} è stato annullato: {$motivo}");
        }

        return $this;
    }

    public function richiedeApprovazione($note = null)
    {
        $this->update([
            'stato' => 'in_attesa_approvazione',
            'richiede_approvazione' => true,
            'note' => $this->note . "\n[" . now()->format('d/m/Y H:i') . "] Richiesta approvazione" . ($note ? ": {$note}" : "")
        ]);

        // Notifica ai responsabili
        $responsabili = User::whereIn('ruolo', ['admin', 'direttivo'])->where('attivo', true)->pluck('id');
        
        foreach ($responsabili as $responsabileId) {
            $this->notificaUtente($responsabileId, 'Approvazione Richiesta', "Il ticket #{$this->numero_ticket} richiede approvazione: {$this->titolo}");
        }

        return $this;
    }

    public function approva($userId, $note = null)
    {
        $this->update([
            'stato' => 'risolto',
            'approvato_da' => $userId,
            'data_approvazione' => now(),
            'note_approvazione' => $note,
            'note' => $this->note . "\n[" . now()->format('d/m/Y H:i') . "] Approvato" . ($note ? ": {$note}" : "")
        ]);

        // Notifica al richiedente e assegnatario
        $this->notificaUtente($this->user_id, 'Ticket Approvato', "Il ticket #{$this->numero_ticket} è stato approvato e risolto");
        
        if ($this->assegnato_a && $this->assegnato_a != $this->user_id) {
            $this->notificaUtente($this->assegnato_a, 'Ticket Approvato', "Il ticket #{$this->numero_ticket} è stato approvato");
        }

        return $this;
    }

    private function notificaUtente($userId, $titolo, $messaggio)
    {
        Notifica::create([
            'destinatari' => [$userId],
            'titolo' => $titolo,
            'messaggio' => $messaggio,
            'tipo' => 'ticket'
        ]);
    }

    private function notificaRisoluzioneCritica()
    {
        $utentiNotifica = User::whereIn('ruolo', ['admin', 'direttivo', 'mezzi'])
                             ->where('attivo', true)
                             ->pluck('id');

        Notifica::create([
            'destinatari' => $utentiNotifica->toArray(),
            'titolo' => 'Problema Critico Risolto',
            'messaggio' => "È stato risolto un problema critico che bloccava l'operatività: Ticket #{$this->numero_ticket}",
            'tipo' => 'risoluzione_critica'
        ]);
    }

    // ===================================
    // STATISTICHE E REPORT
    // ===================================

    public static function getStatisticheTickets()
    {
        return [
            'totale_tickets' => self::count(),
            'tickets_aperti' => self::whereIn('stato', ['aperto', 'assegnato', 'in_corso'])->count(),
            'tickets_in_ritardo' => self::whereIn('stato', ['aperto', 'assegnato', 'in_corso'])
                                       ->get()
                                       ->filter(function($ticket) {
                                           return $ticket->in_ritardo;
                                       })
                                       ->count(),
            'tickets_critici' => self::where('priorita', 'critica')
                                    ->whereIn('stato', ['aperto', 'assegnato', 'in_corso'])
                                    ->count(),
            'tickets_mese' => self::whereMonth('created_at', now()->month)
                                 ->whereYear('created_at', now()->year)
                                 ->count(),
            'tempo_medio_risoluzione' => self::whereNotNull('tempo_risoluzione_ore')
                                            ->avg('tempo_risoluzione_ore'),
            'soddisfazione_media' => self::whereNotNull('valutazione_richiedente')
                                        ->avg('valutazione_richiedente')
        ];
    }

    public static function getTicketsInRitardo()
    {
        return self::whereIn('stato', ['aperto', 'assegnato', 'in_corso'])
                   ->get()
                   ->filter(function($ticket) {
                       return $ticket->in_ritardo;
                   });
    }

    // ===================================
    // SCOPE QUERIES
    // ===================================

    public function scopeAperti($query)
    {
        return $query->whereIn('stato', ['aperto', 'assegnato', 'in_corso']);
    }

    public function scopePriorita($query, $priorita)
    {
        return $query->where('priorita', $priorita);
    }

    public function scopeCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function scopeAssegnatiA($query, $userId)
    {
        return $query->where('assegnato_a', $userId);
    }

    public function scopeCreatiDa($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeInRitardo($query)
    {
        return $query->whereIn('stato', ['aperto', 'assegnato', 'in_corso'])
                     ->get()
                     ->filter(function($ticket) {
                         return $ticket->in_ritardo;
                     });
    }

    public function scopeRicerca($query, $termine)
    {
        return $query->where(function($q) use ($termine) {
            $q->where('numero_ticket', 'like', "%{$termine}%")
              ->orWhere('titolo', 'like', "%{$termine}%")
              ->orWhere('descrizione', 'like', "%{$termine}%")
              ->orWhere('soluzione_adottata', 'like', "%{$termine}%");
        });
    }
}