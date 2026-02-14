<?php
use App\configurationDB\Database;
require_once __DIR__ . '/../../../vendor/autoload.php';

//controllo sessione e ruolo
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Revisore') {
    echo "<script>alert('Accesso negato: Ruolo attuale: " . ($_SESSION['role'] ?? 'nessuno') . "'); window.location.href='/views/login.php';</script>";
    exit;
}

//recupero dati
$db = Database::getInstance();
$conn = $db->getConnection();
$revisore = $_SESSION['cf'];

//recupero lista di tutte le competenze a sistema per poterle assegnare
$stmtComp = $conn->query("SELECT DISTINCT Nome FROM Competenza");
$competenze_esistenti = $stmtComp->fetchAll(PDO::FETCH_ASSOC);

//recupero bilanci assegnati al revisore loggato
$stmtBilanci = $conn->prepare("
    SELECT R.DataBil AS Data, R.BilancioAz AS Azienda
    FROM Revisione R
    WHERE R.Revisore = :revisore
    ORDER BY R.DataBil DESC, R.BilancioAz ASC
");
$stmtBilanci->execute([':revisore' => $revisore]);
$bilanci = $stmtBilanci->fetchAll(PDO::FETCH_ASSOC);

// recupero voci contabili disponibili sui bilanci assegnati al revisore
$stmtVoci = $conn->prepare("
    SELECT DISTINCT RB.AziendaBil AS Azienda, RB.DataBil AS DataBilancio, RB.Voce AS Voce
    FROM Revisione R
    JOIN RigaBilancio RB ON RB.AziendaBil = R.BilancioAz AND RB.DataBil = R.DataBil
    WHERE R.Revisore = :revisore
    ORDER BY RB.AziendaBil ASC, RB.DataBil DESC, RB.Voce ASC
");
$stmtVoci->execute([':revisore' => $revisore]);
$vociBilancio = $stmtVoci->fetchAll(PDO::FETCH_ASSOC);

// dataset per select dinamiche
$aziendeAssegnate = [];
$dateByAzienda = [];
foreach ($bilanci as $bilancio) {
    $azienda = (string) ($bilancio['Azienda'] ?? '');
    $data = (string) ($bilancio['Data'] ?? '');
    if ($azienda === '' || $data === '') {
        continue;
    }
    $aziendeAssegnate[$azienda] = true;
    if (!isset($dateByAzienda[$azienda])) {
        $dateByAzienda[$azienda] = [];
    }
    if (!in_array($data, $dateByAzienda[$azienda], true)) {
        $dateByAzienda[$azienda][] = $data;
    }
}

$vociByAziendaData = [];
foreach ($vociBilancio as $rigaVoce) {
    $azienda = (string) ($rigaVoce['Azienda'] ?? '');
    $data = (string) ($rigaVoce['DataBilancio'] ?? '');
    $voce = (string) ($rigaVoce['Voce'] ?? '');
    if ($azienda === '' || $data === '' || $voce === '') {
        continue;
    }
    if (!isset($vociByAziendaData[$azienda])) {
        $vociByAziendaData[$azienda] = [];
    }
    if (!isset($vociByAziendaData[$azienda][$data])) {
        $vociByAziendaData[$azienda][$data] = [];
    }
    if (!in_array($voce, $vociByAziendaData[$azienda][$data], true)) {
        $vociByAziendaData[$azienda][$data][] = $voce;
    }
}
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
            <h2 class="border-bottom pb-2">Dashboard Revisore ESG</h2>
        </div>
    </div>

    <div class="row g-4 mb-4">

        <div class="col-md-5">
            <div class="card h-100 shadow-sm border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Dichiara le tue Competenze</h5>
                </div>
                <div class="card-body">
                    <form action="/controller/revisoreController.php" method="POST">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Modalità Inserimento</label>
                            <select id="tipoCompetenza" class="form-select" onchange="gestisciCompetenza()">
                                <option value="assegnaCompetenza">Seleziona una competenza esistente</option>
                                <option value="inserisciNuovaCompetenza">Crea una nuova competenza</option>
                            </select>
                        </div>

                        <input type="hidden" name="azione" id="azioneCompetenza" value="assegnaCompetenza">

                        <div class="mb-3" id="divCompetenzaEsistente">
                            <label class="form-label">Competenza</label>
                            <select name="competenza_esistente" id="inputCompEsistente" class="form-select">
                                <?php foreach ($competenze_esistenti as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c['Nome']); ?>">
                                        <?php echo htmlspecialchars($c['Nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3 d-none" id="divCompetenzaNuova">
                            <label class="form-label">Nome Nuova Competenza</label>
                            <input type="text" name="competenza_nuova" id="inputCompNuova" class="form-control"
                                placeholder="Es. Audit Ambientale ISO">
                        </div>

                        <input type="hidden" name="competenza" id="competenzaFinale" value="">

                        <div class="mb-3">
                            <label class="form-label">Livello (0-5)</label>
                            <input type="number" name="livello" class="form-control" min="0" max="5" required>
                        </div>
                        <button type="submit" class="btn btn-info text-white w-100" onclick="preparaCompetenza()">Salva
                            Competenza</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card h-100 shadow-sm border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Rilascia Giudizio Finale su Bilancio</h5>
                </div>
                <div class="card-body">
                    <form action="/controller/revisoreController.php" method="POST">
                        <input type="hidden" name="azione" value="inserisciGiudizio">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Azienda (bilancioaz)</label>
                                <select name="bilancioaz" id="giudizioAzienda" class="form-select" required>
                                    <option value="">Seleziona...</option>
                                    <?php foreach (array_keys($aziendeAssegnate) as $azienda): ?>
                                        <option value="<?php echo htmlspecialchars($azienda); ?>">
                                            <?php echo htmlspecialchars($azienda); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Data Bilancio (databil)</label>
                                <select name="databil" id="giudizioData" class="form-select" required disabled>
                                    <option value="">Seleziona prima un'azienda...</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Esito</label>
                            <select name="esito" id="esitoGiudizio" class="form-select" onchange="toggleRilievi()"
                                required>
                                <option value="Approvazione">Approvazione</option>
                                <option value="Approvazione con rilievi">Approvazione con rilievi</option>
                                <option value="Respingimento">Respingimento</option>
                            </select>
                        </div>

                        <div class="mb-3 d-none" id="divRilievi">
                            <label class="form-label">Rilievi / Note Finali</label>
                            <textarea name="rilievi" id="rilieviField" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Conferma Giudizio</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-dark">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Analisi Analitica - Inserisci Note sulle Voci</h5>
                </div>
                <div class="card-body">
                    <form action="/controller/revisoreController.php" method="POST">
                        <input type="hidden" name="azione" value="inserisciNote">

                        <div class="row align-items-end">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Azienda (rigaazienda)</label>
                                <select name="rigaazienda" id="notaAzienda" class="form-select" required>
                                    <option value="">Seleziona...</option>
                                    <?php foreach (array_keys($aziendeAssegnate) as $azienda): ?>
                                        <option value="<?php echo htmlspecialchars($azienda); ?>">
                                            <?php echo htmlspecialchars($azienda); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-2 mb-3">
                                <label class="form-label">Data Bil. (rigadata)</label>
                                <select name="rigadata" id="notaData" class="form-select" required disabled>
                                    <option value="">Seleziona prima un'azienda...</option>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Voce Contabile (voceriga)</label>
                                <select name="voceriga" id="notaVoce" class="form-select" required disabled>
                                    <option value="">Seleziona prima azienda e data...</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Testo della Nota (testonota)</label>
                                <div class="input-group">
                                    <input type="text" name="testonota" class="form-control"
                                        placeholder="Es. Valore non conforme..." required>
                                    <button type="submit" class="btn btn-dark">Inserisci</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const dateByAzienda = <?php echo json_encode($dateByAzienda, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const vociByAziendaData = <?php echo json_encode($vociByAziendaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    function popolaSelectDate(azienda, selectData) {
        const dateDisponibili = dateByAzienda[azienda] || [];
        selectData.innerHTML = '';

        const defaultOption = document.createElement('option');
        defaultOption.value = '';

        if (!azienda) {
            defaultOption.textContent = "Seleziona prima un'azienda...";
            selectData.appendChild(defaultOption);
            selectData.disabled = true;
            return;
        }

        if (dateDisponibili.length === 0) {
            defaultOption.textContent = 'Nessuna data disponibile';
            selectData.appendChild(defaultOption);
            selectData.disabled = true;
            return;
        }

        defaultOption.textContent = 'Seleziona data bilancio...';
        selectData.appendChild(defaultOption);

        dateDisponibili.forEach((data) => {
            const option = document.createElement('option');
            option.value = data;
            option.textContent = data;
            selectData.appendChild(option);
        });

        selectData.disabled = false;
    }

    function popolaSelectVoci(azienda, dataBilancio, selectVoce) {
        const vociDisponibili = (vociByAziendaData[azienda] && vociByAziendaData[azienda][dataBilancio])
            ? vociByAziendaData[azienda][dataBilancio]
            : [];

        selectVoce.innerHTML = '';

        const defaultOption = document.createElement('option');
        defaultOption.value = '';

        if (!azienda || !dataBilancio) {
            defaultOption.textContent = 'Seleziona prima azienda e data...';
            selectVoce.appendChild(defaultOption);
            selectVoce.disabled = true;
            return;
        }

        if (vociDisponibili.length === 0) {
            defaultOption.textContent = 'Nessuna voce disponibile';
            selectVoce.appendChild(defaultOption);
            selectVoce.disabled = true;
            return;
        }

        defaultOption.textContent = 'Seleziona voce contabile...';
        selectVoce.appendChild(defaultOption);

        vociDisponibili.forEach((voce) => {
            const option = document.createElement('option');
            option.value = voce;
            option.textContent = voce;
            selectVoce.appendChild(option);
        });

        selectVoce.disabled = false;
    }

    //gestione del form Competenze per supportare entrambe le azioni del controller
    function gestisciCompetenza() {
        const tipo = document.getElementById('tipoCompetenza').value;
        const divEsistente = document.getElementById('divCompetenzaEsistente');
        const divNuova = document.getElementById('divCompetenzaNuova');
        const inputAzione = document.getElementById('azioneCompetenza');

        inputAzione.value = tipo;

        if (tipo === 'assegnaCompetenza') {
            divEsistente.classList.remove('d-none');
            divNuova.classList.add('d-none');
        } else {
            divEsistente.classList.add('d-none');
            divNuova.classList.remove('d-none');
        }
    }

    //assicura che il campo <input name="competenza"> riceva il valore giusto prima dell'invio
    function preparaCompetenza() {
        const tipo = document.getElementById('tipoCompetenza').value;
        const compFinale = document.getElementById('competenzaFinale');

        if (tipo === 'assegnaCompetenza') {
            compFinale.value = document.getElementById('inputCompEsistente').value;
        } else {
            compFinale.value = document.getElementById('inputCompNuova').value;
        }
    }

    function toggleRilievi() {
        const esito = document.getElementById('esitoGiudizio').value;
        const divRilievi = document.getElementById('divRilievi');
        const rilieviField = document.getElementById('rilieviField');

        if (esito === 'Approvazione con rilievi') {
            divRilievi.classList.remove('d-none');
            rilieviField.required = true;
        } else {
            divRilievi.classList.add('d-none');
            rilieviField.required = false;
            rilieviField.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const giudizioAzienda = document.getElementById('giudizioAzienda');
        const giudizioData = document.getElementById('giudizioData');
        const notaAzienda = document.getElementById('notaAzienda');
        const notaData = document.getElementById('notaData');
        const notaVoce = document.getElementById('notaVoce');

        if (giudizioAzienda && giudizioData) {
            giudizioAzienda.addEventListener('change', () => {
                popolaSelectDate(giudizioAzienda.value, giudizioData);
            });
            popolaSelectDate(giudizioAzienda.value, giudizioData);
        }

        if (notaAzienda && notaData && notaVoce) {
            notaAzienda.addEventListener('change', () => {
                popolaSelectDate(notaAzienda.value, notaData);
                popolaSelectVoci('', '', notaVoce);
            });

            notaData.addEventListener('change', () => {
                popolaSelectVoci(notaAzienda.value, notaData.value, notaVoce);
            });

            popolaSelectDate(notaAzienda.value, notaData);
            popolaSelectVoci(notaAzienda.value, notaData.value, notaVoce);
        }

        toggleRilievi();
    });
</script>
