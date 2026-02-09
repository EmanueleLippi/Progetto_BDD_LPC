<?php

namespace App\controller;

use App\configurationDB\Database;
use App\configurationDB\MongoDB;

require_once __DIR__ . '/../../vendor/autoload.php';

$cf = $_POST['cf'];
$password = $_POST['password'];
$ruolo = $_POST['ruolo'];
$dataNascita = $_POST['dataNascita'];
$luogoNascita = $_POST['LuogoNascita'];
$email = $_POST['email'];
$username = $_POST['username'];

$database = Database::getInstance();
$mongoDB = new MongoDB();
$conn = $database->getConnection();

$cv_path = null;
if ($ruolo === 'responsabile') {
    if (!isset($_FILES['cv_path']) || $_FILES['cv_path']['error'] !== UPLOAD_ERR_OK) {
        $mongoDB->logEvent('register', $cf, 'responsabile', 'Upload CV mancante o non valido');
        header("Location: /views/register.php?error=CV non valido");
        exit;
    }

    $maxSizeBytes = 5 * 1024 * 1024; // 5 MB
    if ($_FILES['cv_path']['size'] > $maxSizeBytes) {
        $mongoDB->logEvent('register', $cf, 'responsabile', 'CV troppo grande');
        header("Location: /views/register.php?error=CV troppo grande (max 5MB)");
        exit;
    }

    $originalName = $_FILES['cv_path']['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'doc', 'docx'];
    if (!in_array($extension, $allowedExtensions, true)) {
        $mongoDB->logEvent('register', $cf, 'responsabile', 'Formato CV non consentito');
        header("Location: /views/register.php?error=Formato CV non valido");
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/cv';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $safeCf = preg_replace('/[^A-Za-z0-9_-]/', '', $cf);
    $fileName = $safeCf . '_' . time() . '.' . $extension;
    $destination = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($_FILES['cv_path']['tmp_name'], $destination)) {
        $mongoDB->logEvent('register', $cf, 'responsabile', 'Errore nel salvataggio del CV');
        header("Location: /views/register.php?error=Errore salvataggio CV");
        exit;
    }

    $cv_path = '/uploads/cv/' . $fileName;
}

switch ($ruolo) {
    case 'admin':
        try {
            $stmt = $conn->prepare("CALL RegistraAdmin(:cf, :username, :password, :email, :dataNascita, :luogoNascita)");
            $stmt->bindValue(":cf", $cf);
            $stmt->bindValue(":password", $password);
            $stmt->bindValue(":email", $email);
            $stmt->bindValue(":dataNascita", $dataNascita);
            $stmt->bindValue(":luogoNascita", $luogoNascita);
            $stmt->bindValue(":username", $username);
            $stmt->execute();
        } catch (\PDOException $th) {
            echo ("[ERRORE] Query sql di registrazione fallita" . $th->getMessage() . "\n");
            $mongoDB->logEvent('register', $cf, 'N/A', 'Tentativo di registrazione fallito');
            header("Location: /views/register.php?error=Errore di registrazione");
            exit;
        }
        break;
    case 'responsabile':
        try {
            $stmt = $conn->prepare('CALL RegistraResponsabile(:cf, :username, :password, :email, :dataNascita, :luogoNascita, :cv_path)');
            $stmt->bindValue(":cf", $cf);
            $stmt->bindValue(":username", $username);
            $stmt->bindValue(":password", $password);
            $stmt->bindValue(":email", $email);
            $stmt->bindValue(":dataNascita", $dataNascita);
            $stmt->bindValue(":luogoNascita", $luogoNascita);
            $stmt->bindValue(":cv_path", $cv_path);
            $stmt->execute();
        } catch (\PDOException $th) {
            echo ("[ERRORE] Query sql di registrazione fallita" . $th->getMessage() . "\n");
            $mongoDB->logEvent('register', $cf, 'N/A', 'Tentativo di registrazione fallito');
            header("Location: /views/register.php?error=Errore di registrazione");
            exit;
        }
        break;
    case 'revisore':
        try {
            $indiceAffidabilita = 0;
            $stmt = $conn->prepare('CALL RegistraRevisore(:cf, :username, :password, :email, :dataNascita, :luogoNascita, :indiceAffidabilita)');
            $stmt->bindValue(":cf", $cf);
            $stmt->bindValue(":username", $username);
            $stmt->bindValue(":password", $password);
            $stmt->bindValue(":email", $email);
            $stmt->bindValue(":dataNascita", $dataNascita);
            $stmt->bindValue(":luogoNascita", $luogoNascita);
            $stmt->bindValue(":indiceAffidabilita", $indiceAffidabilita);
            $stmt->execute();
            $stmt->closeCursor();

            $competenzeSelezionate = $_POST['competenze_selezionate'] ?? [];
            $competenzeLivelli = $_POST['competenze_livelli'] ?? [];
            if (!is_array($competenzeSelezionate)) {
                $competenzeSelezionate = [$competenzeSelezionate];
            }
            if (!is_array($competenzeLivelli)) {
                $competenzeLivelli = [$competenzeLivelli];
            }

            $competenzePulite = [];
            foreach ($competenzeSelezionate as $index => $competenza) {
                $trimmed = trim((string) $competenza);
                if ($trimmed === '') {
                    continue;
                }
                $levelRaw = $competenzeLivelli[$index] ?? 0;
                $level = (int) $levelRaw;
                if ($level < 0) {
                    $level = 0;
                } elseif ($level > 5) {
                    $level = 5;
                }
                $competenzePulite[$trimmed] = $level;
            }

            if (!empty($competenzePulite)) {
                $stmtInsertCompetenza = $conn->prepare('CALL InserisciNuovaCompetenza(:nome, :revisore)');
                $stmtAssegnaCompetenza = $conn->prepare('CALL AssegnaCompetenza(:competenza, :revisore, :livello)');

                foreach ($competenzePulite as $competenzaNome => $livello) {
                    $stmtInsertCompetenza->bindValue(':nome', $competenzaNome);
                    $stmtInsertCompetenza->bindValue(':revisore', $cf);
                    $stmtInsertCompetenza->execute();
                    $stmtInsertCompetenza->closeCursor();

                    $stmtAssegnaCompetenza->bindValue(':competenza', $competenzaNome);
                    $stmtAssegnaCompetenza->bindValue(':revisore', $cf);
                    $stmtAssegnaCompetenza->bindValue(':livello', $livello, \PDO::PARAM_INT);
                    $stmtAssegnaCompetenza->execute();
                    $stmtAssegnaCompetenza->closeCursor();
                }
            }
        } catch (\PDOException $th) {
            echo ("[ERRORE] Query sql di registrazione fallita" . $th->getMessage() . "\n");
            $mongoDB->logEvent('register', $cf, 'N/A', 'Tentativo di registrazione fallito');
            header("Location: /views/register.php?error=Errore di registrazione");
            exit;
        }
        break;

    default:
        break;
}
$mongoDB->logEvent('register', $cf, $ruolo, 'Registrazione effettuata');

session_start();
$_SESSION['user'] = $username;
$_SESSION['role'] = $ruolo;

header("Location: /index.php");
