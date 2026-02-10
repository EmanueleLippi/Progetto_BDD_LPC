<?php
// DEBUG TEMPORANEO
// ini_set('display_errors', 1);
// error_reporting(E_ALL);
// echo "<!-- DEBUG: admin_dashboard.php loaded -->";
use App\configurationDB\Database;
require_once __DIR__ . '/../header.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

// Controllo sessione
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'])) {
    echo "<script>alert('Accesso negato: Ruolo attuale: " . ($_SESSION['role'] ?? 'nessuno') . "'); window.location.href='/views/login.php';</script>";
    exit;
}

// 1. RECUPERO DATI PER LE SELECT (Revisori e Aziende/Bilanci)
$db = Database::getInstance();
$conn = $db->getConnection();

// Recupero lista Revisori
$stmtRev = $conn->query("SELECT Username FROM Utenti WHERE Ruolo = 'revisore ESG'");
$revisori = $stmtRev->fetchAll(PDO::FETCH_ASSOC);

// Recupero lista Aziende (per selezionare il bilancio)
$stmtAz = $conn->query("SELECT RagioneSociale, PartitaIVA FROM Aziende");
$aziende = $stmtAz->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container my-5">

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-12">
            <h2 class="border-bottom pb-2">Dashboard Admin</h2>
        </div>
    </div>

    <div class="row g-4 mb-4">

        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Gestione Template Bilancio</h5>
                </div>
                <div class="card-body">
                    <form action="/controller/adminController.php" method="POST">
                        <input type="hidden" name="azione" value="inserisci_voce">

                        <div class="mb-3">
                            <label class="form-label">Nome Voce Contabile</label>
                            <input type="text" name="nome" class="form-control" placeholder="es. Ricavi vendite"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descrizione</label>
                            <textarea name="descrizione" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Aggiungi Voce</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Nuovo Indicatore ESG</h5>
                </div>
                <div class="card-body">
                    <form action="/controller/adminController.php" method="POST" id="formIndicatore">

                        <input type="hidden" name="azione" id="azioneIndicatore" value="inserisci_esg">

                        <div class="row mb-2">
                            <div class="col-md-6">
                                <label class="form-label">Nome</label>
                                <input type="text" name="nome" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Rilevanza (0-10)</label>
                                <input type="number" name="rilevanza" class="form-control" min="0" max="10" required>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Nome file Immagine</label>
                            <input type="text" name="img" class="form-control" placeholder="es. logo.png" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Tipologia Indicatore</label>
                            <select id="tipoSelect" class="form-select" onchange="gestisciFormIndicatore()">
                                <option value="generico">Generico</option>
                                <option value="ambientale">Ambientale</option>
                                <option value="sociale">Sociale</option>
                            </select>
                        </div>

                        <div id="fieldsAmbientale" class="d-none p-2 bg-light border mb-3">
                            <label class="form-label text-success">Codice Normativa (amb)</label>
                            <input type="text" name="amb" class="form-control">
                        </div>

                        <div id="fieldsSociale" class="d-none p-2 bg-light border mb-3">
                            <div class="mb-2">
                                <label class="form-label text-primary">Frequenza</label>
                                <input type="text" name="frequenza" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label class="form-label text-primary">Ambito</label>
                                <input type="text" name="ambito" class="form-control">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Salva Indicatore</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-dark">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Assegna Revisore a Bilancio</h5>
                </div>
                <div class="card-body">
                    <form action="/controller/adminController.php" method="POST">
                        <input type="hidden" name="azione" value="assegna_revisore">

                        <div class="row align-items-end">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Azienda (Bilancio)</label>
                                <select name="bilancioAz" class="form-select" required>
                                    <option value="">Seleziona Azienda...</option>
                                    <?php foreach ($aziende as $az): ?>
                                        <option value="<?php echo htmlspecialchars($az['RagioneSociale']); ?>">
                                            <?php echo htmlspecialchars($az['RagioneSociale']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Data Bilancio</label>
                                <input type="date" name="dataBilancio" class="form-control" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Revisore</label>
                                <select name="revisore" class="form-select" required>
                                    <option value="">Seleziona Revisore...</option>
                                    <?php foreach ($revisori as $rev): ?>
                                        <option value="<?php echo htmlspecialchars($rev['Username']); ?>">
                                            <?php echo htmlspecialchars($rev['Username']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-2 mb-3">
                                <button type="submit" class="btn btn-dark w-100">Assegna</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Funzione per gestire la visualizzazione dei campi in base al tipo di indicatore
    function gestisciFormIndicatore() {
        const tipo = document.getElementById('tipoSelect').value;
        const hiddenAzione = document.getElementById('azioneIndicatore');

        const divAmb = document.getElementById('fieldsAmbientale');
        const divSoc = document.getElementById('fieldsSociale');
        const inputAmb = document.querySelector('input[name="amb"]');
        const inputFreq = document.querySelector('input[name="frequenza"]');
        const inputAmbito = document.querySelector('input[name="ambito"]');

        // Reset visualizzazione
        divAmb.classList.add('d-none');
        divSoc.classList.add('d-none');

        // Rimuovi 'required' dai campi nascosti per evitare errori di validazione HTML5
        if (inputAmb) inputAmb.required = false;
        if (inputFreq) inputFreq.required = false;
        if (inputAmbito) inputAmbito.required = false;

        if (tipo === 'ambientale') {
            hiddenAzione.value = 'inserisci_ambientale';
            divAmb.classList.remove('d-none');
            if (inputAmb) inputAmb.required = true;

        } else if (tipo === 'sociale') {
            hiddenAzione.value = 'inserisci_sociale';
            divSoc.classList.remove('d-none');
            if (inputFreq) inputFreq.required = true;
            if (inputAmbito) inputAmbito.required = true;

        } else {
            hiddenAzione.value = 'inserisci_esg';
        }
    }
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>