# Gestionale Croce Verde

Questo progetto Laravel fornisce un'API di base per la gestione di volontari, mezzi e checklist dell'associazione "Croce Verde Ascoli Piceno".

## Requisiti
- PHP >= 8.2
- Composer

## Installazione
1. Copiare `.env.example` in `.env` e configurare i parametri del database.
2. Eseguire `composer install` per installare le dipendenze.
3. Lanciare le migrazioni con `php artisan migrate`.
4. Avviare l'applicazione con `php artisan serve` oppure tramite docker-compose.

## Funzionalità
- CRUD volontari
- CRUD mezzi
- CRUD checklist
- CRUD documenti (allegabili a volontari o mezzi)

Le rotte API sono definite in `routes/api.php`.
