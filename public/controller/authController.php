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
$conn = $database->getConnection(); //ottengo la connessione al database

// Chiamo la procedura Autenticazione
try {
    $stmt = $conn->prepare("CALL Autenticazione(:cf, :password)");
    $stmt->bindValue(":cf", $cf);
    $stmt->bindValue(":password", $password);
    $stmt->execute();
} catch (PDOException $th) {
    echo ("[ERRORE] Query sql di autenticazione fallita" . $th->getMessage() . "\n");
    $mongoDB->logEvent('login', $cf, 'N/A', 'Tentativo di login fallito');
    header("Location: /views/login.php?error=Errore di autenticazione");
    exit;
}
//ottengo il risultato della query
$result = $stmt->fetch();
//controllo che il risultato esista --> se non esiste login fallito
if ($result) {
    //avvio la sessione e setto le variabili
    session_start();
    $_SESSION['user'] = $result['Username'] ?? null; //setto il nome utente
    $_SESSION['role'] = $result['Ruolo'] ?? null; //setto il ruolo
    $_SESSION['cf'] = $result['Cf'] ?? null; //setto il cf
    $mongoDB->logEvent('login', $cf, $result['Ruolo'], 'Login effettuato'); //aggiorno mongo
    header("Location: /index.php"); //redirect alla pagina index
    exit;
} else {
    $mongoDB->logEvent('login', $cf, 'N/A', 'Tentativo di login fallito'); //aggiorno mongo con errore
    header("Location: /views/login.php?error=Credenziali non valide"); //redirect alla pagina login con errore
    exit;
}
?>