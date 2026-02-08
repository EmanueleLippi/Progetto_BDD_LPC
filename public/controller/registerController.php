<?php

namespace App\controller;

use App\configurationDB\Database;

require_once __DIR__ . '/../../vendor/autoload.php';

$cf = $_POST['cf'];
$password = $_POST['password'];
$ruolo = $_POST['ruolo'];
$dataNascita = $_POST['dataNascita'];
$luogoNascita = $_POST['LuogoNascita'];
$email = $_POST['email'];
$username = $_POST['username'];

$database = Database::getInstance();
$conn = $database->getConnection();

// TODO gestire l'upload del file cv_path

switch ($ruolo) {
    case 'admin':
        $stmt = $conn->prepare("CALL RegistraAdmin(:cf, :username, :password, :email, :dataNascita, :luogoNascita)");
        $stmt->bindValue(":cf", $cf);
        $stmt->bindValue(":password", $password);
        $stmt->bindValue(":email", $email);
        $stmt->bindValue(":dataNascita", $dataNascita);
        $stmt->bindValue(":luogoNascita", $luogoNascita);
        $stmt->bindValue(":username", $username);
        $stmt->execute();
        break;
    case 'responsabile':
        $cv_path = $_POST['cv_path'];
        $stmt = $conn->prepare('CALL RegistraResponsabile(:cf, :username, :password, :email, :dataNascita, :luogoNascita, :cv_path)');
        $stmt->bindValue(":cf", $cf);
        $stmt->bindValue(":username", $username);
        $stmt->bindValue(":password", $password);
        $stmt->bindValue(":email", $email);
        $stmt->bindValue(":dataNascita", $dataNascita);
        $stmt->bindValue(":luogoNascita", $luogoNascita);
        $stmt->bindValue(":cv_path", $cv_path);
        $stmt->execute();
        break;
    case 'revisore':
        $stmt = $conn->prepare('CALL RegistraRevisore(:cf, :username, :password, :email, :dataNascita, :luogoNascita)');
        $stmt->bindValue(":cf", $cf);
        $stmt->bindValue(":username", $username);
        $stmt->bindValue(":password", $password);
        $stmt->bindValue(":email", $email);
        $stmt->bindValue(":dataNascita", $dataNascita);
        $stmt->bindValue(":luogoNascita", $luogoNascita);
        $stmt->execute();
        break;
    default:
        break;
}

//TODO gestire l'errore in caso di registrazione di un utente già esistente
session_start();
$_SESSION['user'] = $username;
$_SESSION['role'] = $ruolo;
header("Location: /index.php");
