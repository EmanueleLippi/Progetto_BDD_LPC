<?php

namespace App\controller;

use App\configurationDB\Database;
use App\configurationDB\MongoDB;

require_once __DIR__ . '/../../vendor/autoload.php';

// inizializziamo variabili PHP leggendo i campi inviati dal form via POST
$cf = $_POST['cf'];
$password = $_POST['password'];
$ruolo = $_POST['ruolo'];
$dataNascita = $_POST['dataNascita'];
$luogoNascita = $_POST['LuogoNascita'];
$emails = $_POST['email'] ?? [];
if (!is_array($emails)) {
    $emails = [$emails];
}
$username = $_POST['username'];

// inizializzazione connessioni database
$database = Database::getInstance();
$mongoDB = new MongoDB();
$conn = $database->getConnection();

$cv_path = null; //inizialmente null perchè non detto che ci sia un cv
//caso in cui ruolo è Responsabile allora mi serve e valido il cv
if ($ruolo === 'Responsabile') {
    //controllo se il file ricevuto dal form esiste e non ha errori
    if (!isset($_FILES['cv_path']) || $_FILES['cv_path']['error'] !== UPLOAD_ERR_OK) {
        $mongoDB->logEvent('register', $cf, 'responsabile', 'Upload CV mancante o non valido');
        header("Location: /views/register.php?error=CV non valido");
        exit;
    }

    $maxSizeBytes = 5 * 1024 * 1024; // 5 MB
    //controllo se il file supera la dimensione massima consentita
    if ($_FILES['cv_path']['size'] > $maxSizeBytes) {
        $mongoDB->logEvent('register', $cf, 'responsabile', 'CV troppo grande');
        header("Location: /views/register.php?error=CV troppo grande (max 5MB)");
        exit;
    }

    //salvo il nome originale e la sua estensione
    $originalName = $_FILES['cv_path']['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'doc', 'docx']; //estensioni consentite
    //controllo se l'estensione del file è consentita
    if (!in_array($extension, $allowedExtensions, true)) {
        $mongoDB->logEvent('register', $cf, 'responsabile', 'Formato CV non consentito');
        header("Location: /views/register.php?error=Formato CV non valido");
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/cv'; //cartella di upload
    //controllo se la cartella esiste, altrimenti la creo
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $safeCf = preg_replace('/[^A-Za-z0-9_-]/', '', $cf); //safety check per il cf
    $fileName = $safeCf . '_' . time() . '.' . $extension; //nome del file: cf_timestamp.estensione
    $destination = $uploadDir . '/' . $fileName; //percorso di destinazione

    //controllo se il file è stato caricato correttamente nella cartella di destinazione
    if (!move_uploaded_file($_FILES['cv_path']['tmp_name'], $destination)) {
        $mongoDB->logEvent('register', $cf, 'responsabile', 'Errore nel salvataggio del CV');
        header("Location: /views/register.php?error=Errore salvataggio CV");
        exit;
    }

    $cv_path = '/uploads/cv/' . $fileName; //salvo nella variabile il percorso del file
}

// in base al ruolo dell'utente richiamo la procedura di registrazione opportuna
switch ($ruolo) {
    case 'Admin':
        try {
            $stmt = $conn->prepare("CALL RegistraAdmin(:cf, :username, :password, :dataNascita, :luogoNascita)");
            $stmt->bindValue(":cf", $cf);
            $stmt->bindValue(":password", $password);
            $stmt->bindValue(":dataNascita", $dataNascita);
            $stmt->bindValue(":luogoNascita", $luogoNascita);
            $stmt->bindValue(":username", $username);
            $stmt->execute();
            $stmt->closeCursor();
            foreach ($emails as $ind => $email) {
                $email = trim((string) $email);
                if ($email === '') {
                    continue;
                }
                try {
                    $stmt = $conn->prepare("CALL RegistraEmail(:cf, :email)");
                    $stmt->bindValue(":cf", $cf);
                    $stmt->bindValue(":email", $email);
                    $stmt->execute();
                    $stmt->closeCursor();
                } catch (\PDOException $th) {
                    $sqlState = $th->errorInfo[0] ?? null;
                    $driverErrorCode = $th->errorInfo[1] ?? null;
                    if ($sqlState === '23000' || $driverErrorCode === 1062) {
                        $mongoDB->logEvent('register', $cf, 'N/A', 'Tentativo di inserimento mail duplicata');
                        header("Location: /views/register.php?error=Mail già presente");
                        exit;
                    }
                    throw $th;
                }
            }
        } catch (\PDOException $th) {
            echo ("[ERRORE] Query sql di registrazione fallita" . $th->getMessage() . "\n");
            $mongoDB->logEvent('register', $cf, 'N/A', 'Tentativo di registrazione fallito');
            header("Location: /views/register.php?error=Errore di registrazione");
            exit;
        }
        break;
    case 'Responsabile':
        try {
            $stmt = $conn->prepare('CALL RegistraResponsabile(:cf, :username, :password, :dataNascita, :luogoNascita, :cv_path)');
            $stmt->bindValue(":cf", $cf);
            $stmt->bindValue(":username", $username);
            $stmt->bindValue(":password", $password);
            $stmt->bindValue(":dataNascita", $dataNascita);
            $stmt->bindValue(":luogoNascita", $luogoNascita);
            $stmt->bindValue(":cv_path", $cv_path);
            $stmt->execute();
            $stmt->closeCursor();
            foreach ($emails as $ind => $email) {
                $email = trim((string) $email);
                if ($email === '') {
                    continue;
                }
                try {
                    $stmt = $conn->prepare("CALL RegistraEmail(:cf, :email)");
                    $stmt->bindValue(":cf", $cf);
                    $stmt->bindValue(":email", $email);
                    $stmt->execute();
                    $stmt->closeCursor();
                } catch (\PDOException $th) {
                    $sqlState = $th->errorInfo[0] ?? null;
                    $driverErrorCode = $th->errorInfo[1] ?? null;
                    if ($sqlState === '23000' || $driverErrorCode === 1062) {
                        $mongoDB->logEvent('register', $cf, 'N/A', 'Tentativo di inserimento mail duplicata');
                        header("Location: /views/register.php?error=Mail già presente");
                        exit;
                    }
                    throw $th;
                }
            }
        } catch (\PDOException $th) {
            echo ("[ERRORE] Query sql di registrazione fallita" . $th->getMessage() . "\n");
            $mongoDB->logEvent('register', $cf, 'N/A', 'Tentativo di registrazione fallito');
            header("Location: /views/register.php?error=Errore di registrazione Username o CF già presente");
            exit;
        }
        break;
    case 'Revisore':
        try {
            $indiceAffidabilita = 0;
            $stmt = $conn->prepare('CALL RegistraRevisore(:cf, :username, :password, :dataNascita, :luogoNascita, :indiceAffidabilita)');
            $stmt->bindValue(":cf", $cf);
            $stmt->bindValue(":username", $username);
            $stmt->bindValue(":password", $password);
            $stmt->bindValue(":dataNascita", $dataNascita);
            $stmt->bindValue(":luogoNascita", $luogoNascita);
            $stmt->bindValue(":indiceAffidabilita", $indiceAffidabilita);
            $stmt->execute();
            //dopo la prima call al db chiudo il cursore per evitare errori
            $stmt->closeCursor();
            foreach ($emails as $ind => $email) {
                $email = trim((string) $email);
                if ($email === '') {
                    continue;
                }
                try {
                    $stmt = $conn->prepare("CALL RegistraEmail(:cf, :email)");
                    $stmt->bindValue(":cf", $cf);
                    $stmt->bindValue(":email", $email);
                    $stmt->execute();
                    $stmt->closeCursor();
                } catch (\PDOException $th) {
                    $sqlState = $th->errorInfo[0] ?? null;
                    $driverErrorCode = $th->errorInfo[1] ?? null;
                    if ($sqlState === '23000' || $driverErrorCode === 1062) {
                        $mongoDB->logEvent('register', $cf, 'N/A', 'Tentativo di inserimento mail duplicata');
                        header("Location: /views/register.php?error=Mail già presente");
                        exit;
                    }
                    throw $th;
                }
            }

            $competenzeSelezionate = $_POST['competenze_selezionate'] ?? []; //estraggo l'array delle competenze selezionate dal revisore in registrazione
            $competenzeLivelli = $_POST['competenze_livelli'] ?? []; //estraggo l'array dei livelli delle competenze selezionate
            //controllo se i competenze selezionate e competenze livelli sono array, altrimenti li trasformo per poterli manipolare
            if (!is_array($competenzeSelezionate)) {
                $competenzeSelezionate = [$competenzeSelezionate];
            }
            if (!is_array($competenzeLivelli)) {
                $competenzeLivelli = [$competenzeLivelli];
            }

            //creo un array d'appoggio per le competenze rielaborate (voglio evitare errori tipo: Risk management != RiskManagement)
            $competenzePulite = [];
            //eseguo un foreach su ogni competenza selezionata passata dal front-end
            foreach ($competenzeSelezionate as $index => $competenza) {
                $trimmed = trim((string) $competenza); //rimuovo gli spazi e i caratteri non validi
                if ($trimmed === '') {
                    continue;
                }
                $levelRaw = $competenzeLivelli[$index] ?? 0; //ottengo il livello della competenza
                $level = (int) $levelRaw; //trasformo il livello in intero
                //controllo se il livello è valido
                if ($level < 0) {
                    $level = 0;
                } elseif ($level > 5) {
                    $level = 5;
                }
                $competenzePulite[$trimmed] = $level; //aggiungo la competenza e il livello all'array
            }

            //controllo se l'array non è vuoto
            if (!empty($competenzePulite)) {
                //preparo la seconda call al db
                $stmtInsertCompetenza = $conn->prepare('CALL InserisciNuovaCompetenza(:nome, :revisore)');
                $stmtAssegnaCompetenza = $conn->prepare('CALL AssegnaCompetenza(:competenza, :revisore, :livello)');

                //eseguo un foreach su ogni competenza selezionata pulita in modo da popolare correttamente il db
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

//avvio la sessione e setto le variabili di sessione come in login
session_start();
$_SESSION['user'] = $username;
$_SESSION['role'] = $ruolo;
$_SESSION['cf'] = $cf;

header("Location: /index.php");
