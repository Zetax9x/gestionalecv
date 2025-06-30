<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Documento extends Model
{
    use HasFactory;

    protected $table = 'documenti';

    protected $fillable = [
        'volontario_id',
        'user_id',
        'nome_documento',
        'tipo_documento',
        'numero_documento',
        'data_rilascio',
        'data_scadenza',
        'ente_rilascio',
        'path_file',
        'dimensione_file',
        'mime_type',
        'note',
        'verificato',
        'data_verifica',
        'verificato_da'
    ];

    protected $casts = [
        'data_rilascio' => 'date',
        'data_scadenza' => 'date',
        'data_verifica' => 'datetime',
        'verificato' => 'boolean',
        'dimensione_file' => 'integer'
    ];

    // ===================================
    // RELAZIONI
    // ===================================

    public function volontario()
    {
        return $this->belongsTo(Volontario::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function verificatoDa()
    {
        return $this->belongsTo(User::class, 'verificato_da');
    }

    // ===================================
    // ATTRIBUTI COMPUTATI
    // ===================================

    public function getUrlDownloadAttribute()
    {
        if ($this->path_file) {
            return asset('storage/' . $this->path_file);
        }
        return null;
    }

    public function getDimensioneFormattataAttribute()
    {
        if (!$this->dimensione_file) return 'N/A';
        
        $bytes = $this->dimensione_file;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getGiorniAllaScadenzaAttribute()
    {
        if (!$this->data_scadenza) return null;
        
        return now()->diffInDays($this->data_scadenza, false);
    }

    public function getStatoScadenzaAttribute()
    {
        if (!$this->data_scadenza) return 'valido';
        
        $giorni = $this->giorni_alla_scadenza;
        
        if ($giorni < 0) return 'scaduto';
        if ($giorni <= 30) return 'in_scadenza';
        
        return 'valido';
    }

    public function getColoreStatoAttribute()
    {
        $colori = [
            'valido' => 'success',
            'in_scadenza' => 'warning',
            'scaduto' => 'danger'
        ];
        
        return $colori[$this->stato_scadenza] ?? 'secondary';
    }

    public function getTipoDocumentoLabelAttribute()
    {
        $labels = [
            'carta_identita' => 'Carta d\'Identità',
            'codice_fiscale' => 'Codice Fiscale',
            'patente' => 'Patente di Guida',
            'certificato_medico' => 'Certificato Medico',
            'attestato_corso' => 'Attestato Corso',
            'assicurazione' => 'Assicurazione',
            'altro' => 'Altro'
        ];
        
        return $labels[$this->tipo_documento] ?? 'Documento';
    }

    // ===================================
    // SCOPE QUERIES
    // ===================================

    public function scopeInScadenza($query, $giorni = 30)
    {
        return $query->whereNotNull('data_scadenza')
                    ->whereDate('data_scadenza', '>=', now())
                    ->whereDate('data_scadenza', '<=', now()->addDays($giorni));
    }

    public function scopeScaduti($query)
    {
        return $query->whereNotNull('data_scadenza')
                    ->whereDate('data_scadenza', '<', now());
    }

    public function scopeVerificati($query)
    {
        return $query->where('verificato', true);
    }

    public function scopePerTipo($query, $tipo)
    {
        return $query->where('tipo_documento', $tipo);
    }

    // ===================================
    // METODI UTILITY
    // ===================================

    public function isScaduto()
    {
        return $this->stato_scadenza === 'scaduto';
    }

    public function isInScadenza($giorni = 30)
    {
        return $this->stato_scadenza === 'in_scadenza';
    }

    public function verifica($userId = null)
    {
        $this->update([
            'verificato' => true,
            'data_verifica' => now(),
            'verificato_da' => $userId ?? auth()->id()
        ]);
    }

    public function rimuoviVerifica()
    {
        $this->update([
            'verificato' => false,
            'data_verifica' => null,
            'verificato_da' => null
        ]);
    }
}