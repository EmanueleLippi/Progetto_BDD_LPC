<?php
namespace App\controller;

//import delle classi necessarie
use App\configurationDB\Database;
use App\configurationDB\MongoDB;
use PDOException;

//require per il caricamento automatico delle classi
require_once __DIR__ . '/../../vendor/autoload.php';

//avvio la sessione
session_start();

//Avvio la connessione a Mongo
$mongoDB = new MongoDB();
//controllo se l'utente ha il ruolo di per accedere a questi metodi
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    //se non ha il ruolo di admin lo mando alla pagina di login
    header("Location: /views/login.php?error=Non hai il permesso di accedere a questa pagina");
    //log del tentativo di accesso non autorizzato
    $mongoDB->logEvent('Tentativo di accesso non autorizzato', $_SESSION['user'] ?? 'Sconosciuto', $_SESSION['role'] ?? 'Sconosciuto', 'Tentativo di accesso non autorizzato alla pagina admin');
    exit;
}
//Se ha le credenziali giuste procedo con la connessione a MySQL
//Ottengo l'istanza del database
$db = Database::getInstance();
//Scarico l'azione richiesta o la setto a '' se non c'è
$azione = $_POST['azione'] ?? '';
//Connetto il db
$conn = $db->getConnection();

/**
 * Funzione per il caricamento dell'immagine dell'indicatore ESG
 * @param MongoDB $mongoDB --> istanza del database MongoDB
 * @return string --> percorso dell'immagine caricata
 */
function uploadIndicatoreImmagine(MongoDB $mongoDB): string
{
    //Controllo che dal form sia stato inviato un file consono e privo di errori
    if (!isset($_FILES['img_file']) || $_FILES['img_file']['error'] !== UPLOAD_ERR_OK) {
        $mongoDB->logEvent('upload_img_indicatore', $_SESSION['user'] ?? 'Sconosciuto', $_SESSION['role'] ?? 'Sconosciuto', 'Upload immagine mancante o non valido');
        header("Location: /index.php?error=" . urlencode("Immagine indicatore non valida"));
        exit;
    }

    //Controllo che la dimensione del file non superi i 5MB
    $maxSizeBytes = 5 * 1024 * 1024; // 5 MB
    if ($_FILES['img_file']['size'] > $maxSizeBytes) {
        $mongoDB->logEvent('upload_img_indicatore', $_SESSION['user'] ?? 'Sconosciuto', $_SESSION['role'] ?? 'Sconosciuto', 'Immagine troppo grande');
        header("Location: /index.php?error=" . urlencode("Immagine troppo grande (max 5MB)"));
        exit;
    }

    //prelevo il nome del file caricato e la sua estensione
    $originalName = $_FILES['img_file']['name'] ?? '';
    // strtolower converte l'estensione in minuscolo per evitare problemi di case sensitivity, pathinfo estrae l'estensione del file
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    //Controllo se l'estensione del file è tra quelle accettate
    if (!in_array($extension, $allowedExtensions, true)) {
        $mongoDB->logEvent('upload_img_indicatore', $_SESSION['user'] ?? 'Sconosciuto', $_SESSION['role'] ?? 'Sconosciuto', 'Formato immagine non valido');
        header("Location: /index.php?error=" . urlencode("Formato immagine non valido"));
        exit;
    }

    //imposto la cartella in cui verrà salvata l'immagine
    $uploadDir = __DIR__ . '/../uploads/esg';
    //Controllo se la cartella esiste, altrimenti la creo
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        $mongoDB->logEvent('upload_img_indicatore', $_SESSION['user'] ?? 'Sconosciuto', $_SESSION['role'] ?? 'Sconosciuto', 'Errore creazione cartella upload');
        header("Location: /index.php?error=" . urlencode("Errore salvataggio immagine"));
        exit;
    }

    //creo un nome sicuro per il file: sostituisco il nome del file con il cf dell'utente e aggiungo timestamp e numero casuale oltre che l'estensione
    $safeCf = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($_SESSION['cf'] ?? 'utente'));
    $fileName = $safeCf . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $extension;
    //imposto la destinazione del file
    $destination = $uploadDir . '/' . $fileName;

    //Controllo se il file è stato caricato correttamente
    if (!move_uploaded_file($_FILES['img_file']['tmp_name'], $destination)) {
        $mongoDB->logEvent('upload_img_indicatore', $_SESSION['user'] ?? 'Sconosciuto', $_SESSION['role'] ?? 'Sconosciuto', 'Errore salvataggio file immagine');
        header("Location: /index.php?error=" . urlencode("Errore salvataggio immagine"));
        exit;
    }

    return '/uploads/esg/' . $fileName; //ritorno il path dell'immagine
}

//switch per le azioni richieste
switch ($azione) {
    case "inserisci_esg":
        $nome = $_POST['nome'];
        $img = uploadIndicatoreImmagine($mongoDB); //carico l'immagine dell'indicatore
        $rilevanza = $_POST['rilevanza'];
        $amministratore = $_SESSION['cf'];
        try {
            //preparo la query
            $stmt = $conn->prepare("CALL InserisciIndicatore(:nome, :img, :rilevanza, :amministratore)");
            //faccio il bind dei parametri della query
            $stmt->bindValue(":nome", $nome);
            $stmt->bindValue(":img", $img);
            $stmt->bindValue(":rilevanza", $rilevanza);
            $stmt->bindValue(":amministratore", $amministratore);
            //eseguo la query
            $stmt->execute();
            //Aggiorno mongo
            $mongoDB->logEvent('inserisci_esg', $_SESSION['user'], $_SESSION['role'], 'Indicatore inserito');
            header("Location: /index.php?success=" . urlencode("Indicatore inserito con successo"));
            exit;
        } catch (PDOException $th) {
            $mongoDB->logEvent('Tentativo di inserimento indicatore', $_SESSION['user'], $_SESSION['role'], 'Tentativo di inserimento indicatore fallito');
            header("Location: /index.php?error=" . urlencode("Tentativo di inserimento indicatore fallito: " . $th->getMessage()));
            exit;
        }
        break;

    case "inserisci_ambientale":
        $nome = $_POST["nome"];
        $img = uploadIndicatoreImmagine($mongoDB);
        $rilevanza = $_POST["rilevanza"];
        $admin = $_SESSION["cf"];
        $amb = $_POST["amb"];
        try {
            $stmt = $conn->prepare("CALL InserisciIndicatoreAmbientale(:nome, :img, :rilevanza, :admin, :amb)");
            $stmt->bindValue(":nome", $nome);
            $stmt->bindValue(":img", $img);
            $stmt->bindValue(":rilevanza", $rilevanza);
            $stmt->bindValue(":admin", $admin);
            $stmt->bindValue(":amb", $amb);
            $stmt->execute();
            $mongoDB->logEvent('inserisci_ambientale', $_SESSION['user'], $_SESSION['role'], 'Ambientale inserito');
            header("Location: /index.php?success=" . urlencode("Ambientale inserito con successo"));
            exit;
        } catch (PDOException $th) {
            $mongoDB->logEvent('Tentativo di inserimento ambientale', $_SESSION['user'], $_SESSION['role'], 'Tentativo di inserimento ambientale fallito');
            header("Location: /index.php?error=" . urlencode("Tentativo di inserimento ambientale fallito: " . $th->getMessage()));
            exit;
        }
        break;

    case "inserisci_sociale":
        $nome = $_POST["nome"];
        $img = uploadIndicatoreImmagine($mongoDB);
        $rilevanza = $_POST["rilevanza"];
        $admin = $_SESSION["cf"];
        $frequenza = $_POST["frequenza"];
        $ambito = $_POST["ambito"];
        try {
            $stmt = $conn->prepare("CALL InserisciIndicatoreSociale(:nome, :img, :rilevanza, :admin, :frequenza, :ambito)");
            $stmt->bindValue(":nome", $nome);
            $stmt->bindValue(":img", $img);
            $stmt->bindValue(":rilevanza", $rilevanza);
            $stmt->bindValue(":admin", $admin);
            $stmt->bindValue(":frequenza", $frequenza);
            $stmt->bindValue(":ambito", $ambito);
            $stmt->execute();
            $mongoDB->logEvent('inserisci_sociale', $_SESSION['user'], $_SESSION['role'], 'Sociale inserito');
            header("Location: /index.php?success=" . urlencode("Sociale inserito con successo"));
            exit;
        } catch (PDOException $th) {
            $mongoDB->logEvent('Tentativo di inserimento sociale', $_SESSION['user'], $_SESSION['role'], 'Tentativo di inserimento sociale fallito');
            header("Location: /index.php?error=" . urlencode("Tentativo di inserimento sociale fallito: " . $th->getMessage()));
            exit;
        }
        break;

    case "inserisci_voce":
        $nome = $_POST["nome"];
        $descr = $_POST["descrizione"];
        $admin = $_SESSION["cf"];
        try {
            $stmt = $conn->prepare("CALL InserisciVoce(:nome, :descrizione, :admin)");
            $stmt->bindValue(":nome", $nome);
            $stmt->bindValue(":descrizione", $descr);
            $stmt->bindValue(":admin", $admin);
            $stmt->execute();
            $mongoDB->logEvent('inserisci_voce', $_SESSION['user'], $_SESSION['role'], 'Voce inserita');
            header("Location: /index.php?success=" . urlencode("Voce inserita con successo"));
            exit;
        } catch (PDOException $th) {
            $mongoDB->logEvent('Tentativo di inserimento voce', $_SESSION['user'], $_SESSION['role'], 'Tentativo di inserimento voce fallito');
            header("Location: /index.php?error=" . urlencode("Tentativo di inserimento voce fallito: " . $th->getMessage()));
            exit;
        }
        break;

    case "assegna_revisore":
        $revisore = $_POST["revisore"];
        $dataBilancio = $_POST["dataBilancio"];
        $bilancioAz = $_POST["bilancioAz"];
        $admin = $_SESSION["cf"];
        try {
            $stmt = $conn->prepare("CALL AssegnaRevisore(:revisore, :dataBilancio, :bilancioAz, :admin)");
            $stmt->bindValue(":revisore", $revisore);
            $stmt->bindValue(":dataBilancio", $dataBilancio);
            $stmt->bindValue(":bilancioAz", $bilancioAz);
            $stmt->bindValue(":admin", $admin);
            $stmt->execute();
            $mongoDB->logEvent('assegna_revisore', $_SESSION['user'], $_SESSION['role'], 'Revisore assegnato');
            header("Location: /index.php?success=" . urlencode("Revisore assegnato con successo"));
            exit;
        } catch (PDOException $th) {
            $mongoDB->logEvent('Tentativo di assegnazione revisore', $_SESSION['user'], $_SESSION['role'], 'Tentativo di assegnazione revisore fallito');
            header("Location: /index.php?error=" . urlencode("Tentativo di assegnazione revisore fallito: " . $th->getMessage()));
            exit;
        }
        break;

    default:
        break;
}
