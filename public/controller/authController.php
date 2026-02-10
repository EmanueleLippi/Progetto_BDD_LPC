<?php

namespace App\controller;

use App\configurationDB\Database;
use App\configurationDB\MongoDB;
use PDOException;

require_once __DIR__ . '/../../vendor/autoload.php';

$cf = $_POST['cf'];
$password = $_POST['password'];

// Utilizzo il pattern Singleton per ottenere l'istanza del database
$database = Database::getInstance();
$mongoDB = new MongoDB();
$conn = $database->getConnection();

// Chiamo la procedura Autenticazione
try {
    $stmt = $conn->prepare("CALL Autenticazione(:cf, :password)");
    $stmt->bindValue(":cf", $cf);
    $stmt->bindValue(":password", $password);
    $stmt->execute();
} catch (\PDOException $th) {
    echo ("[ERRORE] Query sql di autenticazione fallita" . $th->getMessage() . "\n");
    $mongoDB->logEvent('login', $cf, 'N/A', 'Tentativo di login fallito');
    header("Location: /views/login.php?error=Errore di autenticazione");
    exit;
}

$result = $stmt->fetch();
if ($result === false) {
    $mongoDB->logEvent('login', $cf, 'N/A', 'Tentativo di login fallito');
    header("Location: /views/login.php?error=Credenziali non valide");
    exit;
}
$mongoDB->logEvent('login', $cf, $result['Ruolo'], 'Tentativo di login');
if ($result) {
    session_start();
    $_SESSION['user'] = $result['Username'] ?? null;
    $_SESSION['role'] = $result['Ruolo'] ?? null;
    $mongoDB->logEvent('login', $cf, $result['Ruolo'], 'Login effettuato');
    header("Location: /index.php");
    exit;
} else {
    $mongoDB->logEvent('login', $cf, 'N/A', 'Tentativo di login fallito');
    header("Location: /views/login.php?error=Credenziali non valide");
    exit;
}
?>