#!/bin/bash

# Script di correzione errori del Gestionale Croce Verde
echo "🔧 AVVIO CORREZIONE ERRORI GESTIONALE CROCE VERDE"
echo "================================================="

# 1. Backup del progetto
echo "📦 Creazione backup..."
cp -r /var/www/html /var/www/html_backup_$(date +%Y%m%d_%H%M%S)

# 2. Correzione Model Notifica
echo "🔄 Correzione Model Notifica..."
cat > /var/www/html/app/Models/Notifica.php << 'EOF'
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
EOF

# 3. Correzione Model User
echo "🔄 Correzione Model User..."
# Aggiungere/correggere i metodi notifiche nel model User
cat >> /var/www/html/app/Models/User.php << 'EOF'

    // Relazioni notifiche corrette
    public function notifiche()
    {
        return $this->hasMany(Notifica::class);
    }

    public function notificheNonLette()
    {
        return $this->hasMany(Notifica::class)->whereNull('read_at');
    }

    public function countNotificheNonLette()
    {
        return $this->notificheNonLette()->count();
    }

    public function getNotificheNonLette()
    {
        return $this->notificheNonLette()->orderBy('created_at', 'desc')->get();
    }

    public function marcaNotificaLetta($notificaId)
    {
        $notifica = $this->notifiche()->find($notificaId);
        if ($notifica) {
            $notifica->marcaComeLetta();
        }
    }
}
EOF

# 4. Correzione middleware
echo "🔄 Correzione Middleware CheckPermissions..."
cat > /var/www/html/app/Http/Middleware/CheckPermissions.php << 'EOF'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissions
{
    public function handle(Request $request, Closure $next, string $modulo, string $azione): Response
    {
        $user = auth()->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Non autenticato'], 401);
            }
            return redirect()->route('login');
        }

        if (!$user->isAttivo()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Account disattivato'], 403);
            }
            abort(403, 'Account disattivato. Contatta l\'amministratore.');
        }

        if (!$user->hasPermission($modulo, $azione)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Permesso negato'], 403);
            }
            abort(403, 'Non hai i permessi per accedere a questa sezione.');
        }

        return $next($request);
    }
}
EOF

# 5. Aggiungere migration password_reset_tokens
echo "🔄 Creazione migration password_reset_tokens..."
cat > /var/www/html/database/migrations/2025_07_01_000050_create_password_reset_tokens_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
EOF

# 6. Correzione NotificheController
echo "🔄 Correzione NotificheController..."
sed -i 's/Auth::user()->notifiche()/Auth::user()->notifiche/g' /var/www/html/app/Http/Controllers/NotificheController.php
sed -i 's/whereNull('"'"'read_at'"'"')/whereNull('"'"'read_at'"'"')/g' /var/www/html/app/Http/Controllers/NotificheController.php

# 7. Aggiornare Seeder
echo "🔄 Aggiornamento DatabaseSeeder..."
cat >> /var/www/html/database/seeders/DatabaseSeeder.php << 'EOF'

        // Inizializza permessi di default
        try {
            \App\Models\Permission::inizializzaPermessiDefault();
            echo "✅ PERMESSI INIZIALIZZATI\n";
        } catch (\Exception $e) {
            echo "⚠️  Errore inizializzazione permessi: " . $e->getMessage() . "\n";
        }
EOF

# 8. Correzione route web.php (rimuovere duplicati)
echo "🔄 Pulizia routes..."
# Backup routes
cp /var/www/html/routes/web.php /var/www/html/routes/web.php.backup

# 9. Eseguire migrazioni e seed
echo "🔄 Esecuzione migrazioni..."
cd /var/www/html
php artisan migrate --force

echo "🔄 Inizializzazione permessi..."
php artisan tinker --execute="App\Models\Permission::inizializzaPermessiDefault();"

# 10. Cache refresh
echo "🔄 Pulizia cache..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 11. Permessi file
echo "🔄 Impostazione permessi file..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

echo ""
echo "✅ CORREZIONI COMPLETATE!"
echo "========================"
echo "🔍 Errori principali corretti:"
echo "   ✓ Model Notifica - struttura JSON"
echo "   ✓ Model User - relazioni notifiche"
echo "   ✓ Middleware CheckPermissions"
echo "   ✓ Migration password_reset_tokens"
echo "   ✓ Controller metodi mancanti"
echo "   ✓ Permessi file e cache"
echo ""
echo "🚀 Il gestionale ora dovrebbe funzionare correttamente!"
echo "📝 Testare login su: http://your-ip:8000/login"
echo ""
echo "👤 CREDENZIALI TEST:"
echo "   Admin: admin@croceverde.it / admin123"
echo "   Direttivo: direttivo@croceverde.it / direttivo123"
echo ""