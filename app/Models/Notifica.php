<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notifica extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'notifiche';

    protected $fillable = [
        'user_id',
        'destinatari',
        'titolo',
        'messaggio',
        'tipo',
        'letta_da',
        'priorita',
        'url_azione',
        'testo_azione',
        'scade_il',
        'metadati',
        'read_at'
    ];

    protected $casts = [
        'destinatari' => 'array',
        'letta_da' => 'array',
        'metadati' => 'array',
        'scade_il' => 'datetime',
        'read_at' => 'datetime'
    ];

    // Relazioni
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope corretti
    public function scopePerUtente($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeNonLette($query, $userId)
    {
        return $query->where('user_id', $userId)->whereNull('read_at');
    }

    public function scopeLette($query, $userId)
    {
        return $query->where('user_id', $userId)->whereNotNull('read_at');
    }

    // Metodi utility
    public function marcaComeLetta()
    {
        $this->update(['read_at' => now()]);
        return $this;
    }

    public static function crea($dati)
    {
        // Se destinatari è array, crea notifiche multiple
        if (isset($dati['destinatari']) && is_array($dati['destinatari'])) {
            $notifiche = [];
            foreach ($dati['destinatari'] as $userId) {
                $notificaData = $dati;
                $notificaData['user_id'] = $userId;
                unset($notificaData['destinatari']);
                $notifiche[] = self::create($notificaData);
            }
            return $notifiche;
        }
        
        return self::create($dati);
    }
}
