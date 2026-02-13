<?php
namespace App\controller;

use App\configurationDB\Database;
use App\configurationDB\MongoDB;
use PDOException;

require_once __DIR__ . "/../../vendor/autoload.php";

session_start();
$mongoDB = new MongoDB();

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Responsabile") {
    header("Location: /views/login.php?error=Non hai il permesso di accedere a questa pagina");
    $mongoDB->logEvent('Tentativo di accesso non autorizzato', $_SESSION['user'] ?? 'Sconosciuto', $_SESSION['role'] ?? 'Sconosciuto', 'Tentativo di accesso non autorizzato alla pagina responsabile');
    exit;
}

$azione = $_POST['azione'] ?? ($_GET['action'] ?? '');
$db = Database::getInstance();
$conn = $db->getConnection();

function uploadLogoAzienda(MongoDB $mongoDB): ?string
{
    if (!isset($_FILES['logo_file']) || $_FILES['logo_file']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES['logo_file']['error'] !== UPLOAD_ERR_OK) {
        $mongoDB->logEvent('upload_logo_azienda', $_SESSION['user'] ?? 'Sconosciuto', $_SESSION['role'] ?? 'Sconosciuto', 'Upload logo non valido');
        header("Location: /index.php?error=" . urlencode("Logo azienda non valido"));
        exit;
    }

    $maxSizeBytes = 5 * 1024 * 1024; // 5 MB
    if ($_FILES['logo_file']['size'] > $maxSizeBytes) {
        $mongoDB->logEvent('upload_logo_azienda', $_SESSION['user'] ?? 'Sconosciuto', $_SESSION['role'] ?? 'Sconosciuto', 'Logo troppo grande');
        header("Location: /index.php?error=" . urlencode("Logo troppo grande (max 5MB)"));
        exit;
    }

    $originalName = $_FILES['logo_file']['name'] ?? '';
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($extension, $allowedExtensions, true)) {
        $mongoDB->logEvent('upload_logo_azienda', $_SESSION['user'] ?? 'Sconosciuto', $_SESSION['role'] ?? 'Sconosciuto', 'Formato logo non valido');
        header("Location: /index.php?error=" . urlencode("Formato logo non valido"));
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/aziende';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        $mongoDB->logEvent('upload_logo_azienda', $_SESSION['user'] ?? 'Sconosciuto', $_SESSION['role'] ?? 'Sconosciuto', 'Errore creazione cartella logo');
        header("Location: /index.php?error=" . urlencode("Errore salvataggio logo"));
        exit;
    }

    $safeCf = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($_SESSION['cf'] ?? 'utente'));
    $fileName = $safeCf . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $extension;
    $destination = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($_FILES['logo_file']['tmp_name'], $destination)) {
        $mongoDB->logEvent('upload_logo_azienda', $_SESSION['user'] ?? 'Sconosciuto', $_SESSION['role'] ?? 'Sconosciuto', 'Errore salvataggio file logo');
        header("Location: /index.php?error=" . urlencode("Errore salvataggio logo"));
        exit;
    }

    return '/uploads/aziende/' . $fileName;
}

switch ($azione) {
    case 'registraAzienda':
        $ragione_sociale = trim((string) ($_POST['ragione_sociale'] ?? ''));
        $nome = trim((string) ($_POST['nome'] ?? $ragione_sociale));
        $settore = trim((string) ($_POST['settore'] ?? ''));
        $n_dipendenti_raw = $_POST['n_dipendenti'] ?? null;
        $n_dipendenti = ($n_dipendenti_raw === '' || $n_dipendenti_raw === null) ? null : (int) $n_dipendenti_raw;
        $logoUploaded = uploadLogoAzienda($mongoDB);
        $logo = $logoUploaded ?? trim((string) ($_POST['logo'] ?? ''));
        $piva = trim((string) ($_POST['piva'] ?? ''));
        $responsabile = $_SESSION['cf'];

        if ($ragione_sociale === '' || $piva === '') {
            header("Location: /index.php?error=" . urlencode("Ragione sociale e Partita IVA sono obbligatorie"));
            exit;
        }
        try {
            $stmt = $conn->prepare("CALL RegistraAzienda(:ragione_sociale, :nome, :settore, :n_dipendenti, :logo, :piva, :responsabile)");
            $stmt->bindValue(":ragione_sociale", $ragione_sociale);
            $stmt->bindValue(":nome", $nome);
            $stmt->bindValue(":settore", $settore);
            if ($n_dipendenti === null) {
                $stmt->bindValue(":n_dipendenti", null, \PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(":n_dipendenti", $n_dipendenti, \PDO::PARAM_INT);
            }
            $stmt->bindValue(":logo", $logo);
            $stmt->bindValue(":piva", $piva);
            $stmt->bindValue(":responsabile", $responsabile);
            $stmt->execute();
            $mongoDB->logEvent('registraAzienda', $_SESSION['user'], $_SESSION['role'], 'Azienda registrata');
            header("Location: /index.php?success=" . urlencode("Azienda registrata con successo"));
            exit;
        } catch (PDOException $th) {
            $mongoDB->logEvent('Tentativo di registrazione azienda', $_SESSION['user'], $_SESSION['role'], 'Tentativo di registrazione azienda fallito');
            header("Location: /index.php?error=" . urlencode("Tentativo di registrazione azienda fallito: Non ci possono essere 2 aziende con la stessa Ragione Sociale" . $th->getMessage()));
            exit;
        }
        break;
    case "creaBilancio":
        $Azienda = trim((string) ($_POST["azienda"] ?? $_POST["azienda_piva"] ?? ''));
        $data = trim((string) ($_POST["data"] ?? $_POST["data_creazione"] ?? ''));
        $responsabile = $_SESSION["cf"];
        if ($Azienda === '' || $data === '') {
            header("Location: /index.php?error=" . urlencode("Azienda e data bilancio sono obbligatorie"));
            exit;
        }
        try {
            $stmt = $conn->prepare("CALL creaBilancio(:Azienda, :data, :responsabile)");
            $stmt->bindValue(":Azienda", $Azienda);
            $stmt->bindValue(":data", $data);
            $stmt->bindValue(":responsabile", $responsabile);
            $stmt->execute();
            $mongoDB->logEvent('creaBilancio', $_SESSION['user'], $_SESSION['role'], 'Bilancio creato');
            header("Location: /index.php?success=" . urlencode("Bilancio creato con successo"));
            exit;
        } catch (PDOException $th) {
            $mongoDB->logEvent('Tentativo di creazione bilancio', $_SESSION['user'], $_SESSION['role'], 'Tentativo di creazione bilancio fallito');
            header("Location: /index.php?error=" . urlencode("Tentativo di creazione bilancio fallito: Non ci possono essere 2 bilanci per la stessa azienda e per lo stesso giorno" . $th->getMessage()));
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
            header("Location: /index.php?success=" . urlencode("Bilancio popolato con successo"));
            exit;
        } catch (PDOException $th) {
            $mongoDB->logEvent('Tentativo di popolamento bilancio', $_SESSION['user'], $_SESSION['role'], 'Tentativo di popolamento bilancio fallito');
            header("Location: /index.php?error=" . urlencode("Tentativo di popolamento bilancio fallito: " . $th->getMessage()));
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
            header("Location: /index.php?success=" . urlencode("Collegamento ESG creato con successo"));
            exit;
        } catch (PDOException $th) {
            $mongoDB->logEvent('Tentativo di creazione collegamento ESG', $_SESSION['user'], $_SESSION['role'], 'Tentativo di creazione collegamento ESG fallito');
            header("Location: /index.php?error=" . urlencode("Tentativo di creazione collegamento ESG fallito: " . $th->getMessage()));
            exit;
        }
        break;

    default:
        header("Location: /index.php?error=" . urlencode("Azione non valida"));
        exit;
        break;
}

?>