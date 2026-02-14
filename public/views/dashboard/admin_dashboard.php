<?php
use App\configurationDB\Database;
require_once __DIR__ . '/../../../vendor/autoload.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    echo "<script>alert('Accesso negato: Ruolo attuale: " . ($_SESSION['role'] ?? 'nessuno') . "'); window.location.href='/views/login.php';</script>";
    exit;
}

//recupero i dati
$db = Database::getInstance();
$conn = $db->getConnection();

//recupero lista Revisori
try {
    //estraggo solo i revisori attraverso il Join
    $stmtRev = $conn->query("SELECT Cf, Username FROM Utente JOIN Revisore ON Utente.Cf = Revisore.Utente");
    $revisori = $stmtRev->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Errore: " . $e->getMessage();
    $revisori = [];
}


//recupero lista Aziende 
try {
    $stmtAz = $conn->query("SELECT RagioneSociale, PartitaIVA FROM Azienda");
    $aziende = $stmtAz->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Errore: " . $e->getMessage();
    $aziende = [];
}

// recupero date bilancio per azienda (usate per la select dinamica)
try {
    $stmtBilanci = $conn->query("SELECT Azienda, Data FROM Bilancio ORDER BY Data DESC");
    $bilanci = $stmtBilanci->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Errore: " . $e->getMessage();
    $bilanci = [];
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
                    <form action="/controller/adminController.php" method="POST" novalidate
                        data-action-form="inserisci_voce">
                        <input type="hidden" name="azione" value="inserisci_voce">
                        <div class="alert alert-danger d-none py-2 small mb-3" role="alert" data-form-error></div>

                        <div class="mb-3">
                            <label class="form-label">Nome Voce Contabile</label>
                            <input type="text" name="nome" class="form-control" placeholder="es. Ricavi vendite"
                                required>
                            <div class="invalid-feedback">Inserisci il nome della voce contabile.</div>
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
                    <form action="/controller/adminController.php" method="POST" id="formIndicatore"
                        enctype="multipart/form-data" novalidate data-action-form="inserisci_esg">

                        <input type="hidden" name="azione" id="azioneIndicatore" value="inserisci_esg">
                        <div class="alert alert-danger d-none py-2 small mb-3" role="alert" data-form-error></div>

                        <div class="row mb-2">
                            <div class="col-md-6">
                                <label class="form-label">Nome</label>
                                <input type="text" name="nome" class="form-control" required>
                                <div class="invalid-feedback">Inserisci il nome dell'indicatore.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Rilevanza (0-10)</label>
                                <input type="number" name="rilevanza" class="form-control" min="0" max="10" required>
                                <div class="invalid-feedback">Inserisci una rilevanza tra 0 e 10.</div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Immagine Indicatore</label>
                            <input type="file" name="img_file" class="form-control" accept=".jpg,.jpeg,.png,.webp,.gif"
                                required>
                            <div class="form-text">Formati supportati: JPG, JPEG, PNG, WEBP, GIF. Max 5MB.</div>
                            <div class="invalid-feedback">Carica un'immagine valida.</div>
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
                            <div class="invalid-feedback">Inserisci il codice normativa.</div>
                        </div>

                        <div id="fieldsSociale" class="d-none p-2 bg-light border mb-3">
                            <div class="mb-2">
                                <label class="form-label text-primary">Frequenza</label>
                                <input type="text" name="frequenza" class="form-control">
                                <div class="invalid-feedback">Inserisci la frequenza.</div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label text-primary">Ambito</label>
                                <input type="text" name="ambito" class="form-control">
                                <div class="invalid-feedback">Inserisci l'ambito sociale.</div>
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
                    <form action="/controller/adminController.php" method="POST" novalidate
                        data-action-form="assegna_revisore">
                        <input type="hidden" name="azione" value="assegna_revisore">
                        <div class="alert alert-danger d-none py-2 small mb-3" role="alert" data-form-error></div>

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
                                <div class="invalid-feedback">Seleziona un'azienda.</div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Data Bilancio</label>
                                <select name="dataBilancio" id="dataBilancioSelect" class="form-select" required
                                    disabled>
                                    <option value="">Seleziona prima un'azienda...</option>
                                </select>
                                <div class="invalid-feedback">Seleziona la data bilancio.</div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Revisore</label>
                                <select name="revisore" class="form-select" required>
                                    <option value="">Seleziona Revisore...</option>
                                    <?php foreach ($revisori as $rev): ?>
                                        <option value="<?php echo htmlspecialchars($rev['Cf']); ?>">
                                            <?php echo htmlspecialchars($rev['Username']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Seleziona un revisore.</div>
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
    const bilanciByAzienda = <?php
    $bilanciByAzienda = [];
    foreach ($bilanci as $bilancio) {
        $azienda = (string) ($bilancio['Azienda'] ?? '');
        $data = (string) ($bilancio['Data'] ?? '');
        if ($azienda !== '' && $data !== '') {
            if (!isset($bilanciByAzienda[$azienda])) {
                $bilanciByAzienda[$azienda] = [];
            }
            $bilanciByAzienda[$azienda][] = $data;
        }
    }
    echo json_encode($bilanciByAzienda, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ?>;

    //funzione per gestire la visualizzazione dei campi in base al tipo di indicatore
    function gestisciFormIndicatore() {
        const tipo = document.getElementById('tipoSelect').value;
        const hiddenAzione = document.getElementById('azioneIndicatore');

        const divAmb = document.getElementById('fieldsAmbientale');
        const divSoc = document.getElementById('fieldsSociale');
        const inputAmb = document.querySelector('input[name="amb"]');
        const inputFreq = document.querySelector('input[name="frequenza"]');
        const inputAmbito = document.querySelector('input[name="ambito"]');

        //reset visualizzazione
        divAmb.classList.add('d-none');
        divSoc.classList.add('d-none');

        //rimuovo 'required' dai campi nascosti per evitare errori di validazione
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

    (function () {
        function aggiornaDateBilancio() {
            const aziendaSelect = document.querySelector('select[name="bilancioAz"]');
            const dataSelect = document.getElementById('dataBilancioSelect');
            if (!aziendaSelect || !dataSelect) return;

            const aziendaSelezionata = aziendaSelect.value;
            const dateDisponibili = bilanciByAzienda[aziendaSelezionata] || [];

            dataSelect.innerHTML = '';

            const defaultOption = document.createElement('option');
            defaultOption.value = '';

            if (!aziendaSelezionata) {
                defaultOption.textContent = "Seleziona prima un'azienda...";
                dataSelect.appendChild(defaultOption);
                dataSelect.disabled = true;
                return;
            }

            if (dateDisponibili.length === 0) {
                defaultOption.textContent = 'Nessun bilancio disponibile';
                dataSelect.appendChild(defaultOption);
                dataSelect.disabled = true;
                return;
            }

            defaultOption.textContent = 'Seleziona data bilancio...';
            dataSelect.appendChild(defaultOption);

            dateDisponibili.forEach((data) => {
                const option = document.createElement('option');
                option.value = data;
                option.textContent = data;
                dataSelect.appendChild(option);
            });

            dataSelect.disabled = false;
        }

        const requiredByAction = {
            inserisci_voce: ['nome'],
            inserisci_esg: ['nome', 'rilevanza', 'img_file'],
            inserisci_ambientale: ['nome', 'rilevanza', 'img_file', 'amb'],
            inserisci_sociale: ['nome', 'rilevanza', 'img_file', 'frequenza', 'ambito'],
            assegna_revisore: ['bilancioAz', 'dataBilancio', 'revisore']
        };

        function getActionName(form) {
            const hidden = form.querySelector('input[name="azione"]');
            return hidden ? hidden.value : '';
        }

        function getField(form, name) {
            return form.querySelector(`[name="${name}"]`);
        }

        function isFieldValuePresent(field) {
            if (!field) return false;
            if (field.type === 'file') {
                return field.files && field.files.length > 0;
            }
            return (field.value || '').trim() !== '';
        }

        function setFieldValidity(field, isValid) {
            if (!field) return;
            field.classList.toggle('is-invalid', !isValid);
            field.classList.toggle('is-valid', isValid && isFieldValuePresent(field));
        }

        function validateForm(form) {
            const action = getActionName(form);
            const requiredFields = requiredByAction[action] || [];
            let allValid = true;

            requiredFields.forEach((fieldName) => {
                const field = getField(form, fieldName);
                if (!field) return;
                const valid = isFieldValuePresent(field) && field.checkValidity();
                setFieldValidity(field, valid);
                if (!valid) allValid = false;
            });

            const errorBox = form.querySelector('[data-form-error]');
            if (errorBox) {
                if (!allValid) {
                    errorBox.textContent = 'Compila correttamente tutti i campi obbligatori per questa azione.';
                    errorBox.classList.remove('d-none');
                } else {
                    errorBox.textContent = '';
                    errorBox.classList.add('d-none');
                }
            }

            return allValid;
        }

        const forms = document.querySelectorAll('form[data-action-form]');
        forms.forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!validateForm(form)) {
                    event.preventDefault();
                    const firstInvalid = form.querySelector('.is-invalid');
                    if (firstInvalid) firstInvalid.focus();
                }
            });
        });

        const tipoSelect = document.getElementById('tipoSelect');
        if (tipoSelect) {
            tipoSelect.addEventListener('change', () => {
                gestisciFormIndicatore();
                const formIndicatore = document.getElementById('formIndicatore');
                if (formIndicatore) validateForm(formIndicatore);
            });
        }

        const aziendaBilancioSelect = document.querySelector('select[name="bilancioAz"]');
        if (aziendaBilancioSelect) {
            aziendaBilancioSelect.addEventListener('change', () => {
                aggiornaDateBilancio();
                const formAssegna = document.querySelector('form[data-action-form="assegna_revisore"]');
                if (formAssegna) {
                    const errorBox = formAssegna.querySelector('[data-form-error]');
                    if (errorBox) {
                        errorBox.textContent = '';
                        errorBox.classList.add('d-none');
                    }
                }
            });
        }

        aggiornaDateBilancio();
        gestisciFormIndicatore();
    })();
</script>