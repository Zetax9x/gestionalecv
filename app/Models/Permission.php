<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'modulo', 
        'ruolo', 
        'visualizza', 
        'crea', 
        'modifica', 
        'elimina',
        'configura',
        'note'
    ];

    protected $casts = [
        'visualizza' => 'boolean',
        'crea' => 'boolean',
        'modifica' => 'boolean',
        'elimina' => 'boolean',
        'configura' => 'boolean',
    ];

    // ===================================
    // COSTANTI
    // ===================================

    /**
     * Moduli disponibili nel sistema
     */
    public const MODULI = [
        'volontari' => 'Gestione Volontari',
        'mezzi' => 'Gestione Mezzi',
        'magazzino' => 'Gestione Magazzino',
        'dpi' => 'Dispositivi di Protezione',
        'documenti' => 'Gestione Documenti',
        'eventi' => 'Eventi e Formazione',
        'avvisi' => 'Bacheca Avvisi',
        'tickets' => 'Segnalazioni Interne',
        'archivio' => 'Archivio Digitale',
        'logs' => 'Registro Attività',
        'configurazione' => 'Configurazioni Sistema'
    ];

    /**
     * Ruoli disponibili
     */
    public const RUOLI = [
        'admin' => 'Amministratore',
        'direttivo' => 'Direttivo',
        'segreteria' => 'Segreteria',
        'mezzi' => 'Responsabile Mezzi',
        'dipendente' => 'Dipendente',
        'volontario' => 'Volontario'
    ];

    /**
     * Azioni disponibili
     */
    public const AZIONI = [
        'visualizza' => 'Visualizzare',
        'crea' => 'Creare',
        'modifica' => 'Modificare',
        'elimina' => 'Eliminare',
        'configura' => 'Configurare'
    ];

    // ===================================
    // METODI STATICI
    // ===================================

    /**
     * Ottieni permessi per ruolo
     */
    public static function getPermessiRuolo($ruolo)
    {
        return Cache::remember("permissions_role_{$ruolo}", 3600, function() use ($ruolo) {
            return self::where('ruolo', $ruolo)->get()->keyBy('modulo');
        });
    }

    /**
     * Ottieni tutti i permessi in formato matrice
     */
    public static function getMatricePermessi()
    {
        return Cache::remember('permissions_matrix', 3600, function() {
            $permessi = self::all();
            $matrice = [];
            
            foreach (self::MODULI as $modulo => $nome) {
                $matrice[$modulo] = ['nome' => $nome, 'ruoli' => []];
                
                foreach (self::RUOLI as $ruolo => $nomeRuolo) {
                    $permesso = $permessi->where('modulo', $modulo)
                                        ->where('ruolo', $ruolo)
                                        ->first();
                    
                    $matrice[$modulo]['ruoli'][$ruolo] = $permesso ?? new self([
                        'modulo' => $modulo,
                        'ruolo' => $ruolo,
                        'visualizza' => false,
                        'crea' => false,
                        'modifica' => false,
                        'elimina' => false,
                        'configura' => false
                    ]);
                }
            }
            
            return $matrice;
        });
    }

    /**
     * Inizializza permessi di default
     */
    public static function inizializzaPermessiDefault()
    {
        $permessiDefault = [
            'admin' => [
                '*' => ['visualizza' => true, 'crea' => true, 'modifica' => true, 'elimina' => true, 'configura' => true]
            ],
            'direttivo' => [
                'volontari' => ['visualizza' => true, 'crea' => true, 'modifica' => true, 'elimina' => false],
                'mezzi' => ['visualizza' => true, 'crea' => true, 'modifica' => true, 'elimina' => false],
                'magazzino' => ['visualizza' => true, 'crea' => true, 'modifica' => true, 'elimina' => false],
                'dpi' => ['visualizza' => true, 'crea' => true, 'modifica' => true, 'elimina' => false],
                'documenti' => ['visualizza' => true, 'crea' => true, 'modifica' => true, 'elimina' => false],
                'eventi' => ['visualizza' => true, 'crea' => true, 'modifica' => true, 'elimina' => false],
                'avvisi' => ['visualizza' => true, 'crea' => true, 'modifica' => true, 'elimina' => false],
                'tickets' => ['visualizza' => true, 'crea' => true, 'modifica' => true, 'elimina' => false],
                'archivio' => ['visualizza' => true, 'crea' => true, 'modifica' => true, 'elimina' => false],
                'logs' => ['visualizza' => true, 'crea' => false, 'modifica' => false, 'elimina' => false]
            ],
            'segreteria' => [
                'volontari' => ['visualizza' => true, 'crea' => true, 'modifica' => true, 'elimina' => false],
                'mezzi' => ['visualizza' => true, 'crea' => false, 'modifica' => true, 'elimina' => false],
                'documenti' => ['visualizza' => true, 'crea' => true, 'modifica' => true, 'elimina' => false],
                'eventi' => ['visualizza' => true, 'crea' => true, 'modifica' => true, 'elimina' => false],
                'avvisi' => ['visualizza' => true, 'crea' => true, 'modifica' => true, 'elimina' => false],
                'archivio' => ['visualizza' => true, 'crea' => true, 'modifica' => false, 'elimina' => false]
            ],
            'mezzi' => [
                'mezzi' => ['visualizza' => true, 'crea' => true, 'modifica' => true, 'elimina' => false],
                'magazzino' => ['visualizza' => true, 'crea' => true, 'modifica' => true, 'elimina' => false],
                'dpi' => ['visualizza' => true, 'crea' => true, 'modifica' => true, 'elimina' => false],
                'tickets' => ['visualizza' => true, 'crea' => true, 'modifica' => true, 'elimina' => false]
            ],
            'dipendente' => [
                'volontari' => ['visualizza' => true, 'crea' => false, 'modifica' => false, 'elimina' => false],
                'eventi' => ['visualizza' => true, 'crea' => false, 'modifica' => false, 'elimina' => false],
                'avvisi' => ['visualizza' => true, 'crea' => false, 'modifica' => false, 'elimina' => false],
                'tickets' => ['visualizza' => true, 'crea' => true, 'modifica' => false, 'elimina' => false]
            ],
            'volontario' => [
                'eventi' => ['visualizza' => true, 'crea' => false, 'modifica' => false, 'elimina' => false],
                'avvisi' => ['visualizza' => true, 'crea' => false, 'modifica' => false, 'elimina' => false],
                'tickets' => ['visualizza' => true, 'crea' => true, 'modifica' => false, 'elimina' => false]
            ]
        ];

        foreach ($permessiDefault as $ruolo => $moduli) {
            foreach ($moduli as $modulo => $azioni) {
                if ($modulo === '*') {
                    // Admin ha accesso a tutto
                    foreach (self::MODULI as $mod => $nome) {
                        self::updateOrCreate(
                            ['modulo' => $mod, 'ruolo' => $ruolo],
                            $azioni
                        );
                    }
                } else {
                    self::updateOrCreate(
                        ['modulo' => $modulo, 'ruolo' => $ruolo],
                        $azioni
                    );
                }
            }
        }

        // Pulisci cache
        self::clearCache();
    }

    /**
     * Pulisci cache permessi
     */
    public static function clearCache()
    {
        Cache::forget('permissions_matrix');
        
        foreach (self::RUOLI as $ruolo => $nome) {
            Cache::forget("permissions_role_{$ruolo}");
        }
    }

    // ===================================
    // EVENTI MODEL
    // ===================================

    protected static function boot()
    {
        parent::boot();

        // Pulisci cache quando i permessi vengono modificati
        static::saved(function ($permission) {
            self::clearCache();
        });

        static::deleted(function ($permission) {
            self::clearCache();
        });
    }

    // ===================================
    // METODI UTILITY
    // ===================================

    /**
     * Ottieni label del modulo
     */
    public function getModuloLabelAttribute()
    {
        return self::MODULI[$this->modulo] ?? $this->modulo;
    }

    /**
     * Ottieni label del ruolo
     */
    public function getRuoloLabelAttribute()
    {
        return self::RUOLI[$this->ruolo] ?? $this->ruolo;
    }

    /**
     * Verifica se ha almeno un permesso
     */
    public function hasAnyPermission()
    {
        return $this->visualizza || $this->crea || $this->modifica || $this->elimina || $this->configura;
    }

    /**
     * Ottieni array delle azioni permesse
     */
    public function getAzioniPermesse()
    {
        $azioni = [];
        
        if ($this->visualizza) $azioni[] = 'visualizza';
        if ($this->crea) $azioni[] = 'crea';
        if ($this->modifica) $azioni[] = 'modifica';
        if ($this->elimina) $azioni[] = 'elimina';
        if ($this->configura) $azioni[] = 'configura';
        
        return $azioni;
    }
}