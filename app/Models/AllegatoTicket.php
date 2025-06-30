<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllegatoTicket extends Model
{
    use HasFactory;

    protected $table = 'allegati_tickets';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'nome_file',
        'file_path',
        'file_originale',
        'mime_type',
        'file_size',
        'tipo',
        'descrizione',
        'metadata',
        'pubblico'
    ];

    protected $casts = [
        'metadata' => 'array',
        'pubblico' => 'boolean',
        'file_size' => 'integer'
    ];

    // ===================================
    // RELAZIONI
    // ===================================

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ===================================
    // ATTRIBUTI COMPUTATI
    // ===================================

    public function getUrlAttribute()
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }
}
