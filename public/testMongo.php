<?php
// Mostra tutti gli errori a video (utile per debug)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Test MongoDB su MAMP</h1>";

// 1. Verifichiamo se Composer è caricato
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    echo "<p>✅ Libreria Composer caricata.</p>";
} else {
    die("<p>❌ Errore: Non trovo la cartella vendor. Hai eseguito 'composer install'?</p>");
}

// 2. Verifichiamo se l'estensione di basso livello è attiva in MAMP
if (extension_loaded("mongodb")) {
    echo "<p>✅ Estensione 'mongodb' attiva nel PHP di MAMP.</p>";
} else {
    echo "<p>❌ <strong>ERRORE CRITICO:</strong> L'estensione 'mongodb' NON è attiva in questo PHP.</p>";
    echo "<p>Se prosegui, riceverai un errore 'Class not found'.</p>";
}

// 3. Proviamo a connetterci
try {
    // Questa riga fallirà se l'estensione manca
    $client = new MongoDB\Client("mongodb://127.0.0.1:27017");

    // Proviamo un comando reale (listDatabases)
    $dbs = $client->listDatabases();

    echo "<p>✅ <strong>SUCCESSO!</strong> Connessione al database riuscita.</p>";
    echo "<pre>Database trovati:\n";
    foreach ($dbs as $db) {
        echo "- " . $db->getName() . "\n";
    }
    echo "</pre>";

} catch (Error $e) {
    // Questo cattura l'errore "Class 'MongoDB\Driver\Manager' not found"
    echo "<div style='color:red; border:1px solid red; padding:10px;'>";
    echo "<h3>ERRORE FATALE:</h3>";
    echo $e->getMessage();
    echo "</div>";
} catch (Exception $e) {
    echo "<p>Errore di connessione (il server Mongo è acceso?): " . $e->getMessage() . "</p>";
}