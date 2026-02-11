<?php
namespace App\controller;

use App\configurationDB\Database;
use App\configurationDB\MongoDB;
use PDOException;

require_once __DIR__ . "/../../vendor/autoload.php";

session_start();
$mongoDB = new MongoDB();

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "Responsabile") {
    header("Location: /views/login.php?error=Non hai il permesso di accedere a questa pagina");
    $mongoDB->logEvent('Tentativo di accesso non autorizzato', $_SESSION['user'] ?? 'Sconosciuto', $_SESSION['role'] ?? 'Sconosciuto', 'Tentativo di accesso non autorizzato alla pagina responsabile');
    exit;
}

$azione = $_POST['azione'] ?? '';
$db = Database::getInstance();
$conn = $db->getConnection();

switch ($azione) {
    case 'registraAzienda':
        $ragione_sociale = $_POST['ragione_sociale'];
        $nome = $_POST['nome'];
        $settore = $_POST['settore'];
        $n_dipendenti = $_POST['n_dipendenti'];
        $logo = $_POST['logo'];
        $piva = $_POST['piva'];
        $responsabile = $_SESSION['cf'];
        try {
            $stmt = $conn->prepare("CALL RegistraAzienda(:ragione_sociale, :nome, :settore, :n_dipendenti, :logo, :piva, :responsabile)");
            $stmt->bindValue(":ragione_sociale", $ragione_sociale);
            $stmt->bindValue(":nome", $nome);
            $stmt->bindValue(":settore", $settore);
            $stmt->bindValue(":n_dipendenti", $n_dipendenti);
            $stmt->bindValue(":logo", $logo);
            $stmt->bindValue(":piva", $piva);
            $stmt->bindValue(":responsabile", $responsabile);
            $stmt->execute();
            $mongoDB->logEvent('registraAzienda', $_SESSION['user'], $_SESSION['role'], 'Azienda registrata');
            header("Location: /views/responsabile.php?success=Azienda registrata con successo");
            exit;
        } catch (PDOException $th) {
            $mongoDB->logEvent('Tentativo di registrazione azienda', $_SESSION['user'], $_SESSION['role'], 'Tentativo di registrazione azienda fallito');
            header("Location: /views/responsabile.php?error=Tentativo di registrazione azienda fallito " . $th->getMessage());
            exit;
        }
        break;
    case "creaBilancio":
        $Azienda = $_POST["azienda"];
        $data = $_POST["data"];
        $responsabile = $_SESSION["cf"];
        try {
            $stmt = $conn->prepare("CALL creaBilancio(:Azienda, :data, :responsabile)");
            $stmt->bindValue(":Azienda", $Azienda);
            $stmt->bindValue(":data", $data);
            $stmt->bindValue(":responsabile", $responsabile);
            $stmt->execute();
            $mongoDB->logEvent('creaBilancio', $_SESSION['user'], $_SESSION['role'], 'Bilancio creato');
            header("Location: /views/responsabile.php?success=Bilancio creato con successo");
            exit;
        } catch (PDOException $th) {
            $mongoDB->logEvent('Tentativo di creazione bilancio', $_SESSION['user'], $_SESSION['role'], 'Tentativo di creazione bilancio fallito');
            header("Location: /views/responsabile.php?error=Tentativo di creazione bilancio fallito " . $th->getMessage());
            exit;
        }
        break;

    case "popolaBilancio":
        $voce = $_POST["voce"];
        $data = $_POST["data"];
        $azienda = $_POST["azienda"];
        $responsabile = $_SESSION["cf"];
        $importo = $_POST["importo"];
        try {
            $stmt = $conn->prepare("CALL popolaBilancio(:voce, :data, :azienda, :responsabile, :importo)");
            $stmt->bindValue(":voce", $voce);
            $stmt->bindValue(":data", $data);
            $stmt->bindValue(":azienda", $azienda);
            $stmt->bindValue(":responsabile", $responsabile);
            $stmt->bindValue(":importo", $importo);
            $stmt->execute();
            $mongoDB->logEvent('popolaBilancio', $_SESSION['user'], $_SESSION['role'], 'Bilancio popolato');
            header("Location: /views/responsabile.php?success=Bilancio popolato con successo");
            exit;
        } catch (PDOException $th) {
            $mongoDB->logEvent('Tentativo di popolamento bilancio', $_SESSION['user'], $_SESSION['role'], 'Tentativo di popolamento bilancio fallito');
            header("Location: /views/responsabile.php?error=Tentativo di popolamento bilancio fallito " . $th->getMessage());
            exit;
        }
        break;

    case "creaCollegamentoESG":
        $voce = $_POST["voce"];
        $databil = $_POST["dataBil"];
        $bilancioAz = $_POST["azienda"];
        $indicatore = $_POST["indicatore"];
        $dataRilevazione = $_POST["dataRilevazione"];
        $valoreNum = $_POST["valoreNum"];
        $fonte = $_POST["fonte"];
        $responsabile = $_SESSION["cf"];
        try {
            $stmt = $conn->prepare("CALL creaCollegamentoESG(:voce, :dataBil, :azienda, :indicatore, :dataRilevazione, :valoreNum, :fonte, :responsabile)");
            $stmt->bindValue(":voce", $voce);
            $stmt->bindValue(":dataBil", $databil);
            $stmt->bindValue(":azienda", $bilancioAz);
            $stmt->bindValue(":indicatore", $indicatore);
            $stmt->bindValue(":dataRilevazione", $dataRilevazione);
            $stmt->bindValue(":valoreNum", $valoreNum);
            $stmt->bindValue(":fonte", $fonte);
            $stmt->bindValue(":responsabile", $responsabile);
            $stmt->execute();
            $mongoDB->logEvent('creaCollegamentoESG', $_SESSION['user'], $_SESSION['role'], 'Collegamento ESG creato');
            header("Location: /views/responsabile.php?success=Collegamento ESG creato con successo");
            exit;
        } catch (PDOException $th) {
            $mongoDB->logEvent('Tentativo di creazione collegamento ESG', $_SESSION['user'], $_SESSION['role'], 'Tentativo di creazione collegamento ESG fallito');
            header("Location: /views/responsabile.php?error=Tentativo di creazione collegamento ESG fallito " . $th->getMessage());
            exit;
        }
        break;

    default:
        break;
}

?>