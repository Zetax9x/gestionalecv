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
        'caricato_da',
        'nome_documento',
        'tipo',
        'sottotipo',
        'file_path',
        'file_originale',
        'mime_type',
        'file_size',
        'hash_file',
        'data_rilascio',
        'data_scadenza',
        'ente_rilascio',
        'numero_documento',
        'stato_validazione',
        'validato_da',
        'data_validazione',
        'note_validazione',
        'notifica_scadenza',
        'giorni_preavviso',
        'ultima_notifica',
        'tags',
        'note',
        'obbligatorio',
        'pubblico',
        'versione',
        'documento_precedente'
    ];

    protected $casts = [
        'data_rilascio' => 'date',
        'data_scadenza' => 'date',
        'data_validazione' => 'datetime',
        'ultima_notifica' => 'datetime',
        'notifica_scadenza' => 'boolean',
        'obbligatorio' => 'boolean',
        'pubblico' => 'boolean',
        'tags' => 'array',
        'file_size' => 'integer',
        'giorni_preavviso' => 'integer',
        'versione' => 'integer'
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
        return $this->belongsTo(User::class, 'caricato_da');
    }

    public function verificatoDa()
    {
        return $this->belongsTo(User::class, 'validato_da');
    }

    // ===================================
    // ATTRIBUTI COMPUTATI
    // ===================================

    public function getUrlDownloadAttribute()
    {
        if ($this->file_path) {
            return asset('storage/' . $this->file_path);
        }
        return null;
    }

    public function getDimensioneFormattataAttribute()
    {
        if (!$this->file_size) return 'N/A';

        $bytes = $this->file_size;
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
        
        return $labels[$this->tipo] ?? 'Documento';
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
        return $query->where('stato_validazione', 'validato');
    }

    public function scopePerTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
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
            'stato_validazione' => 'validato',
            'data_validazione' => now(),
            'validato_da' => $userId ?? auth()->id()
        ]);
    }

    public function rimuoviVerifica()
    {
        $this->update([
            'stato_validazione' => 'in_attesa',
            'data_validazione' => null,
            'validato_da' => null
        ]);
    }
}