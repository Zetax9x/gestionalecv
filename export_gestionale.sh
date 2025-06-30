#!/bin/bash

# Script per esportare tutto il contenuto del Gestionale Croce Verde
# Autore: Claude Assistant
# Data: $(date)

echo "🚀 Inizio esportazione Gestionale Croce Verde..."

# Nome file output
OUTPUT_FILE="gestionale_completo_$(date +%Y%m%d_%H%M%S).txt"

# Funzione per aggiungere separatori
add_separator() {
    echo "===========================================" >> "$OUTPUT_FILE"
    echo "$1" >> "$OUTPUT_FILE"
    echo "===========================================" >> "$OUTPUT_FILE"
    echo "" >> "$OUTPUT_FILE"
}

# Funzione per aggiungere sezioni
add_section() {
    echo "" >> "$OUTPUT_FILE"
    echo "=== $1 ===" >> "$OUTPUT_FILE"
    echo "" >> "$OUTPUT_FILE"
}

# Funzione per aggiungere file
add_file() {
    local file_path="$1"
    local display_name="$2"
    
    if [ -f "$file_path" ]; then
        echo "--- $display_name ---" >> "$OUTPUT_FILE"
        cat "$file_path" >> "$OUTPUT_FILE"
        echo "" >> "$OUTPUT_FILE"
        echo "✅ Aggiunto: $display_name"
    else
        echo "--- $display_name (NON TROVATO) ---" >> "$OUTPUT_FILE"
        echo "File non presente: $file_path" >> "$OUTPUT_FILE"
        echo "" >> "$OUTPUT_FILE"
        echo "❌ Mancante: $display_name"
    fi
}

# Inizia la creazione del file
add_separator "GESTIONALE CROCE VERDE - CONTENUTO COMPLETO"
echo "Data esportazione: $(date)" >> "$OUTPUT_FILE"
echo "Directory: $(pwd)" >> "$OUTPUT_FILE"
echo "Utente: $(whoami)" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# STRUTTURA PROGETTO
add_section "STRUTTURA PROGETTO"
echo "Struttura cartelle principali:" >> "$OUTPUT_FILE"
if command -v tree &> /dev/null; then
    tree -I 'vendor|node_modules|storage|bootstrap/cache|.git|public/build' -L 3 >> "$OUTPUT_FILE" 2>/dev/null
else
    find . -type d -not -path "./vendor/*" -not -path "./node_modules/*" -not -path "./storage/*" -not -path "./.git/*" | head -30 | sort >> "$OUTPUT_FILE"
fi
echo "" >> "$OUTPUT_FILE"

echo "Lista file principali:" >> "$OUTPUT_FILE"
echo "Controllers: $(ls app/Http/Controllers/*.php 2>/dev/null | wc -l)" >> "$OUTPUT_FILE"
echo "Models: $(ls app/Models/*.php 2>/dev/null | wc -l)" >> "$OUTPUT_FILE"
echo "Views: $(find resources/views -name "*.blade.php" 2>/dev/null | wc -l)" >> "$OUTPUT_FILE"
echo "Migrations: $(ls database/migrations/*.php 2>/dev/null | wc -l)" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# CONTROLLERS
add_section "CONTROLLERS"
if [ -d "app/Http/Controllers" ]; then
    for file in app/Http/Controllers/*.php; do
        if [ -f "$file" ]; then
            add_file "$file" "$(basename $file)"
        fi
    done
else
    echo "Cartella Controllers non trovata!" >> "$OUTPUT_FILE"
fi

# MODELS
add_section "MODELS"
if [ -d "app/Models" ]; then
    for file in app/Models/*.php; do
        if [ -f "$file" ]; then
            add_file "$file" "$(basename $file)"
        fi
    done
else
    echo "Cartella Models non trovata!" >> "$OUTPUT_FILE"
fi

# MIDDLEWARE
add_section "MIDDLEWARE"
if [ -d "app/Http/Middleware" ]; then
    for file in app/Http/Middleware/*.php; do
        if [ -f "$file" ]; then
            add_file "$file" "$(basename $file)"
        fi
    done
else
    echo "Cartella Middleware non trovata!" >> "$OUTPUT_FILE"
fi

# PROVIDERS
add_section "PROVIDERS"
if [ -d "app/Providers" ]; then
    for file in app/Providers/*.php; do
        if [ -f "$file" ]; then
            add_file "$file" "$(basename $file)"
        fi
    done
else
    echo "Cartella Providers non trovata!" >> "$OUTPUT_FILE"
fi

# VIEWS
add_section "VIEWS"
if [ -d "resources/views" ]; then
    find resources/views -name "*.blade.php" -type f | while read file; do
        add_file "$file" "$file"
    done
else
    echo "Cartella Views non trovata!" >> "$OUTPUT_FILE"
fi

# ROUTES
add_section "ROUTES"
add_file "routes/web.php" "web.php"
add_file "routes/api.php" "api.php"
add_file "routes/console.php" "console.php"

# CONFIGURAZIONI
add_section "CONFIGURAZIONI"
add_file "bootstrap/app.php" "bootstrap/app.php"
add_file "config/app.php" "config/app.php"
add_file "config/database.php" "config/database.php"
add_file "config/auth.php" "config/auth.php"
add_file "config/cache.php" "config/cache.php"
add_file "config/session.php" "config/session.php"

# FILE DI CONFIGURAZIONE PROGETTO
add_section "FILE PROGETTO"
add_file "composer.json" "composer.json"
add_file "package.json" "package.json"
add_file ".env.example" ".env.example"
add_file "artisan" "artisan"
add_file "vite.config.js" "vite.config.js"

# MIGRAZIONI E SEEDERS
add_section "MIGRAZIONI"
echo "Lista migrazioni:" >> "$OUTPUT_FILE"
if [ -d "database/migrations" ]; then
    ls -la database/migrations/ >> "$OUTPUT_FILE"
else
    echo "Cartella migrations non trovata!" >> "$OUTPUT_FILE"
fi
echo "" >> "$OUTPUT_FILE"

add_section "SEEDERS"
if [ -d "database/seeders" ]; then
    for file in database/seeders/*.php; do
        if [ -f "$file" ]; then
            add_file "$file" "$(basename $file)"
        fi
    done
else
    echo "Cartella seeders non trovata!" >> "$OUTPUT_FILE"
fi

# INFORMAZIONI AGGIUNTIVE
add_section "INFORMAZIONI SISTEMA"
echo "PHP Version: $(php --version | head -1)" >> "$OUTPUT_FILE"
echo "Laravel Version: $(php artisan --version 2>/dev/null || echo 'Non rilevata')" >> "$OUTPUT_FILE"
echo "Composer packages:" >> "$OUTPUT_FILE"
if [ -f "composer.lock" ]; then
    grep '"name"' composer.lock | head -20 >> "$OUTPUT_FILE"
else
    echo "composer.lock non trovato" >> "$OUTPUT_FILE"
fi
echo "" >> "$OUTPUT_FILE"

# STATO DATABASE
add_section "STATO DATABASE"
echo "Stato migrazioni:" >> "$OUTPUT_FILE"
php artisan migrate:status >> "$OUTPUT_FILE" 2>/dev/null || echo "Impossibile verificare stato migrazioni" >> "$OUTPUT_FILE"
echo "" >> "$OUTPUT_FILE"

# FINE FILE
add_separator "FINE ESPORTAZIONE"
echo "File generato: $OUTPUT_FILE" >> "$OUTPUT_FILE"
echo "Data completamento: $(date)" >> "$OUTPUT_FILE"
echo "Dimensione finale: $(du -h "$OUTPUT_FILE" | cut -f1)" >> "$OUTPUT_FILE"

# Statistiche finali
echo ""
echo "✅ Esportazione completata!"
echo "📁 File creato: $OUTPUT_FILE"
echo "📊 Dimensione: $(du -h "$OUTPUT_FILE" | cut -f1)"
echo "📄 Righe totali: $(wc -l < "$OUTPUT_FILE")"
echo ""
echo "Per visualizzare il file:"
echo "cat $OUTPUT_FILE"
echo ""
echo "Per copiarlo in una posizione accessibile via web:"
echo "sudo cp $OUTPUT_FILE /var/www/html/"
echo ""
echo "🎉 Esportazione terminata con successo!"
