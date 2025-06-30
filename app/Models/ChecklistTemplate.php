<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistTemplate extends Model
{
    use HasFactory;

    protected $table = 'checklist_templates';

    protected $fillable = [
        'nome',
        'descrizione',
        'tipo_mezzo',
        'controlli',
        'attivo',
        'ordine',
        'frequenza',
        'obbligatoria'
    ];

    protected $casts = [
        'controlli' => 'array',
        'attivo' => 'boolean',
        'obbligatoria' => 'boolean'
    ];

    // Relazioni
    public function checklistCompilate()
    {
        return $this->hasMany(ChecklistCompilata::class, 'template_id');
    }

    // Scope
    public function scopeAttivi($query)
    {
        return $query->where('attivo', true);
    }

    public function scopePerTipoMezzo($query, $tipo)
    {
        return $query->where(function($q) use ($tipo) {
            $q->where('tipo_mezzo', $tipo)
              ->orWhere('tipo_mezzo', 'tutti');
        });
    }
}