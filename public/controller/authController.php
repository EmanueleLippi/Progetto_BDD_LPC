<?php

namespace App\controller;

use App\configurationDB\Database;

require_once __DIR__ . '/../../vendor/autoload.php';

$cf = $_POST['cf'];
$password = $_POST['password'];

// Utilizzo il pattern Singleton per ottenere l'istanza del database
$database = Database::getInstance();
$conn = $database->getConnection();

// Chiamo la procedura Autenticazione
$stmt = $conn->prepare("CALL Autenticazione(:cf, :password)");
$stmt->bindValue(":cf", $cf);
$stmt->bindValue(":password", $password);
$stmt->execute();

$result = $stmt->fetch();

if ($result) {
    session_start();
    $_SESSION['user'] = $result['Username'] ?? null;
    $_SESSION['role'] = $result['Ruolo'] ?? null;
    header("Location: /index.php");
    exit;
} else {
    header("Location: /views/login.php?error=Credenziali non valide");
    exit;
}
//TODO gestire l'errore in caso di credenziali non valide

?>