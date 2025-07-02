<?php
require_once __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;

$app = require_once __DIR__.'/bootstrap/app.php';

try {
    $pdo = new PDO(
        'mysql:host=gondola.proxy.rlwy.net;port=12408;dbname=railway',
        'root',
        'SUWLCTTLuUDChVCxSCzpgYrNhPZJWRRG'
    );
    echo "✅ Connexion DB réussie\n";
} catch (Exception $e) {
    echo "❌ Erreur DB: " . $e->getMessage() . "\n";
}

try {
    Artisan::call('config:cache');
    echo "✅ Config Laravel OK\n";
} catch (Exception $e) {
    echo "❌ Erreur Laravel: " . $e->getMessage() . "\n";
}
?>