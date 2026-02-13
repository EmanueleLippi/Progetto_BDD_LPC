<?php
namespace App\controller;

use App\configurationDB\Database;
use App\configurationDB\MongoDB;
use PDOException;

require_once __DIR__ . "/../../vendor/autoload.php";

session_start();
$mongoDB = new MongoDB();

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "Revisore") {
    header("Location: /views/login.php?error=Non hai il permesso di accedere a questa pagina");
    $mongoDB->logEvent('Tentativo di accesso non autorizzato', $_SESSION['user'] ?? 'Sconosciuto', $_SESSION['role'] ?? 'Sconosciuto', 'Tentativo di accesso non autorizzato alla pagina revisore');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

$azione = $_POST["azione"] ?? "";

switch ($azione) {
    case "inserisciNote":
        $revisore = $_SESSION["cf"];
        $voceriga = $_POST["voceriga"];
        $rigadata = $_POST["rigadata"];
        $rigaazienda = $_POST["rigaazienda"];
        $testonota = $_POST["testonota"];
        try {
            $stmt = $conn->prepare("CALL InserisciNote(:revisore, :voceriga, :rigadata, :rigaazienda, :testonota)");
            $stmt->bindValue(":revisore", $revisore);
            $stmt->bindValue(":voceriga", $voceriga);
            $stmt->bindValue(":rigadata", $rigadata);
            $stmt->bindValue(":rigaazienda", $rigaazienda);
            $stmt->bindValue(":testonota", $testonota);
            $stmt->execute();
            $mongoDB->logEvent('inserisciNote', $_SESSION['user'], $_SESSION['role'], 'Nota inserita');
            header("Location: /index.php?success=Nota inserita con successo");
            exit;
        } catch (PDOException $th) {
            $mongoDB->logEvent('Tentativo di inserimento nota', $_SESSION['user'], $_SESSION['role'], 'Tentativo di inserimento nota fallito');
            header("Location: /index.php?error=Tentativo di inserimento nota fallito " . $th->getMessage());
            exit;
        }
        break;
    case "inserisciGiudizio":
        $revisore = $_SESSION["cf"];
        $databil = $_POST["databil"];
        $bilancioaz = $_POST["bilancioaz"];
        $esito = $_POST["esito"];
        $rilievi = $_POST["rilievi"];
        try {
            $stmt = $conn->prepare("CALL InserisciGiudizio(:revisore, :databil, :bilancioaz, :esito, :rilievi)");
            $stmt->bindValue(":revisore", $revisore);
            $stmt->bindValue(":databil", $databil);
            $stmt->bindValue(":bilancioaz", $bilancioaz);
            $stmt->bindValue(":esito", $esito);
            $stmt->bindValue(":rilievi", $rilievi);
            $stmt->execute();
            $mongoDB->logEvent('inserisciGiudizio', $_SESSION['user'], $_SESSION['role'], 'Giudizio inserito');
            header("Location: /index.php?success=Giudizio inserito con successo");
            exit;
        } catch (PDOException $th) {
            $mongoDB->logEvent('Tentativo di inserimento giudizio', $_SESSION['user'], $_SESSION['role'], 'Tentativo di inserimento giudizio fallito');
            header("Location: /index.php?error=Tentativo di inserimento giudizio fallito " . $th->getMessage());
            exit;
        }
        break;

    case "inserisciNuovaCompetenza":
        $revisore = $_SESSION["cf"];
        $competenza = $_POST["competenza"];
        $livello = $_POST["livello"];
        try {
            $stmt = $conn->prepare("CALL InserisciNuovaCompetenza(:competenza, :revisore)");
            $stmt->bindValue(":competenza", $competenza);
            $stmt->bindValue(":revisore", $revisore);
            $stmt->execute();
            $mongoDB->logEvent('InserisciNuovaCompetenza', $_SESSION['user'], $_SESSION['role'], 'Nuova competenza inserita');
            $stmt->closeCursor();
        } catch (PDOException $th) {
            $mongoDB->logEvent('Tentativo di inserimento competenza', $_SESSION['user'], $_SESSION['role'], 'Tentativo di inserimento competenza fallito');
            header("Location: /index.php?error=Tentativo di inserimento competenza fallito " . $th->getMessage());
            exit;
        }
        try {
            $stmt = $conn->prepare("CALL AssegnaCompetenza(:competenza, :revisore, :livello)");
            $stmt->bindValue(":competenza", $competenza);
            $stmt->bindValue(":revisore", $revisore);
            $stmt->bindValue(":livello", $livello);
            $stmt->execute();
            $mongoDB->logEvent('AssegnaCompetenza', $_SESSION['user'], $_SESSION['role'], 'Competenza assegnata');
            header("Location: /index.php?success=Competenza assegnata con successo");
            exit;
        } catch (PDOException $th) {
            $mongoDB->logEvent('Tentativo di assegnazione competenza', $_SESSION['user'], $_SESSION['role'], 'Tentativo di assegnazione competenza fallito');
            header("Location: /index.php?error=Tentativo di assegnazione competenza fallito " . $th->getMessage());
            exit;
        }
        break;

    case "assegnaCompetenza":
        $revisore = $_SESSION["cf"];
        $competenza = $_POST["competenza"];
        $livello = $_POST["livello"];
        try {
            $stmt = $conn->prepare("CALL AssegnaCompetenza(:competenza, :revisore, :livello)");
            $stmt->bindValue(":competenza", $competenza);
            $stmt->bindValue(":revisore", $revisore);
            $stmt->bindValue(":livello", $livello);
            $stmt->execute();
            $mongoDB->logEvent('AssegnaCompetenza', $_SESSION['user'], $_SESSION['role'], 'Competenza assegnata');
            header("Location: /index.php?success=Competenza assegnata con successo");
            exit;
        } catch (PDOException $th) {
            $mongoDB->logEvent('Tentativo di assegnazione competenza', $_SESSION['user'], $_SESSION['role'], 'Tentativo di assegnazione competenza fallito');
            header("Location: /index.php?error=Tentativo di assegnazione competenza fallito " . $th->getMessage());
            exit;
        }
        break;


    default:
        break;
}