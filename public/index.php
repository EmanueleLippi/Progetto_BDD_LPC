<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Basic Autoloader (Manteniamo anche il tuo manuale se serve, ma Composer è meglio)
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0)
        return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file))
        require $file;
});

session_start();

// --- GESTIONE ROUTING DINAMICA ---

// 1. Prendi l'URI richiesto (es: /Progetto/public/login)
$request = $_SERVER['REQUEST_URI'];

// 2. Rimuovi eventuali query string (es: ?id=1)
$request = strtok($request, '?');

// 3. Calcola la cartella in cui si trova questo script (index.php)
// Es: se il file è in /Progetto/public/index.php, $scriptDir sarà /Progetto/public
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);

// 4. Se la richiesta inizia con la cartella dello script, rimuovila
// Questo trasforma "/Progetto/public/login" in "/login"
if (strpos($request, $scriptDir) === 0) {
    $request = substr($request, strlen($scriptDir));
}

// 5. Se la richiesta inizia con /index.php, rimuovilo
// 5. Normalizzazione robusta: Rimuoviamo index.php dall'inizio
$request = ltrim($request, '/'); // Rimuovo slash iniziali per standardizzare
if (strpos($request, 'index.php') === 0) {
    $request = substr($request, strlen('index.php'));
}

// 6. Assicuriamoci che il percorso inizi sempre con / (per lo switch)
$request = '/' . ltrim($request, '/');

// --- FINE GESTIONE ROUTING ---

// Debug: Se hai ancora problemi, togli il commento alla riga sotto per vedere cosa legge il router
// die("Indirizzo letto dal router: " . $request);

switch ($request) {
    case '/':
    case '/index.php':
        require __DIR__ . '/../src/Views/home.php';
        break;

    case '/login':
        $auth = new \App\Controllers\AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $auth->login();
        } else {
            $auth->showLogin();
        }
        break;

    case '/logout':
        $auth = new \App\Controllers\AuthController();
        $auth->logout();
        break;

    default:
        http_response_code(404);
        require __DIR__ . '/../src/Views/layout/header.php';
        echo "<div class='container mt-5'><h1>404 - Pagina non trovata</h1><p>La risorsa richiesta non esiste.</p></div>";
        require __DIR__ . '/../src/Views/layout/footer.php';
        break;
}