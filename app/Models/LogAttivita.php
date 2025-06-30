<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogAttivita extends Model
{
    use HasFactory;

    protected $table = 'log_attivita';

    protected $fillable = [
        'user_id',
        'azione',
        'modulo',
        'record_id',
        'record_type',
        'dati_prima',
        'dati_dopo',
        'ip_address',
        'user_agent',
        'note'
    ];

    protected $casts = [
        'dati_prima' => 'array',
        'dati_dopo' => 'array'
    ];

    // ===================================
    // RELAZIONI
    // ===================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function record()
    {
        return $this->morphTo();
    }

    // ===================================
    // ATTRIBUTI COMPUTATI
    // ===================================

    public function getAzioneLabelAttribute()
    {
        $labels = [
            'create' => 'Creazione',
            'update' => 'Modifica',
            'delete' => 'Eliminazione',
            'login' => 'Accesso',
            'logout' => 'Uscita',
            'view' => 'Visualizzazione',
            'download' => 'Download',
            'upload' => 'Upload'
        ];
        
        return $labels[$this->azione] ?? 'Azione';
    }

    public function getColoreAzioneAttribute()
    {
        $colori = [
            'create' => 'success',
            'update' => 'warning',
            'delete' => 'danger',
            'login' => 'info',
            'logout' => 'secondary',
            'view' => 'primary',
            'download' => 'info',
            'upload' => 'success'
        ];
        
        return $colori[$this->azione] ?? 'secondary';
    }

    public function getModuloLabelAttribute()
    {
        $labels = [
            'volontari' => 'Volontari',
            'mezzi' => 'Mezzi',
            'eventi' => 'Eventi',
            'dpi' => 'DPI',
            'magazzino' => 'Magazzino',
            'tickets' => 'Tickets',
            'auth' => 'Autenticazione',
            'admin' => 'Amministrazione'
        ];
        
        return $labels[$this->modulo] ?? 'Sistema';
    }

    // ===================================
    // SCOPE QUERIES
    // ===================================

    public function scopePerUtente($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePerModulo($query, $modulo)
    {
        return $query->where('modulo', $modulo);
    }

    public function scopePerAzione($query, $azione)
    {
        return $query->where('azione', $azione);
    }

    public function scopeRecenti($query, $giorni = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($giorni));
    }

    // ===================================
    // METODI STATICI
    // ===================================

    public static function registra($azione, $modulo, $record = null, $datiPrima = null, $datiDopo = null, $note = null)
    {
        return self::create([
            'user_id' => auth()->id(),
            'azione' => $azione,
            'modulo' => $modulo,
            'record_id' => $record ? $record->id : null,
            'record_type' => $record ? get_class($record) : null,
            'dati_prima' => $datiPrima,
            'dati_dopo' => $datiDopo,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'note' => $note
        ]);
    }
}