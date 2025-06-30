<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crea utente Admin principale
        User::create([
            'nome' => 'Admin',
            'cognome' => 'Sistema',
            'email' => 'admin@croceverde.it',
            'password' => Hash::make('admin123'),
            'telefono' => '123456789',
            'data_nascita' => '1980-01-01',
            'codice_fiscale' => 'ADMSIS80A01H501A',
            'indirizzo' => 'Via Principale 1',
            'citta' => 'Milano',
            'cap' => '20100',
            'provincia' => 'MI',
            'ruolo' => 'admin',
            'attivo' => true,
        ]);

        // Crea utente Direttivo
        User::create([
            'nome' => 'Mario',
            'cognome' => 'Rossi',
            'email' => 'direttivo@croceverde.it',
            'password' => Hash::make('direttivo123'),
            'telefono' => '987654321',
            'data_nascita' => '1975-06-15',
            'codice_fiscale' => 'RSSMRA75H15F205A',
            'indirizzo' => 'Via Roma 10',
            'citta' => 'Milano',
            'cap' => '20100',
            'provincia' => 'MI',
            'ruolo' => 'direttivo',
            'attivo' => true,
        ]);

        // Crea utente Volontario
        User::create([
            'nome' => 'Giulia',
            'cognome' => 'Bianchi',
            'email' => 'volontario@croceverde.it',
            'password' => Hash::make('volontario123'),
            'telefono' => '555123456',
            'data_nascita' => '1995-03-20',
            'codice_fiscale' => 'BNCGLI95C20F205A',
            'indirizzo' => 'Via Garibaldi 5',
            'citta' => 'Milano',
            'cap' => '20100',
            'provincia' => 'MI',
            'ruolo' => 'volontario',
            'attivo' => true,
        ]);

        // Crea utente Responsabile Mezzi
        User::create([
            'nome' => 'Luca',
            'cognome' => 'Verdi',
            'email' => 'mezzi@croceverde.it',
            'password' => Hash::make('mezzi123'),
            'telefono' => '333789012',
            'data_nascita' => '1985-11-10',
            'codice_fiscale' => 'VRDLCU85S10F205A',
            'indirizzo' => 'Via Manzoni 8',
            'citta' => 'Milano',
            'cap' => '20100',
            'provincia' => 'MI',
            'ruolo' => 'mezzi',
            'attivo' => true,
        ]);
         // Inizializza permessi di default
        try {
            \App\Models\Permission::inizializzaPermessiDefault();
            echo "✅ PERMESSI INIZIALIZZATI\n";
        } catch (\Exception $e) {
            echo "⚠️  Errore inizializzazione permessi: " . $e->getMessage() . "\n";
        }

        echo "\n✅ GESTIONALE CROCE VERDE - UTENTI CREATI:\n";
        echo "===============================================\n";
        echo "👤 ADMIN:      admin@croceverde.it / admin123\n";
        echo "👤 DIRETTIVO:  direttivo@croceverde.it / direttivo123\n";
        echo "👤 VOLONTARIO: volontario@croceverde.it / volontario123\n";
        echo "👤 MEZZI:      mezzi@croceverde.it / mezzi123\n";
        echo "===============================================\n";
        echo "🚀 Ora puoi fare login su: http://your-ip:8000/login\n\n";
    }
}
       
