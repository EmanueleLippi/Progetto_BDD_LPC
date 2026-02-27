<?php
use App\configurationDB\Database;
// carica autoloader di composer
require_once __DIR__ . '/../../../vendor/autoload.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Responsabile') {
    echo "<script>alert('Accesso negato: Ruolo attuale: " . ($_SESSION['role'] ?? 'nessuno') . "'); window.location.href='/views/login.php';</script>";
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$aziende = [];
$bilanci = [];
$voci = [];
$indicatori = [];

try {
    $stmtAziende = $conn->prepare("SELECT RagioneSociale, PartitaIva FROM Azienda WHERE Responsabile = :responsabile ORDER BY RagioneSociale");
    $stmtAziende->bindValue(':responsabile', $_SESSION['cf']);
    $stmtAziende->execute();
    // fetchAll con FETCH_ASSOC per ottenere un array associativo più leggibile
    $aziende = $stmtAziende->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Errore caricamento aziende: " . htmlspecialchars($e->getMessage());
}
// Query più  per ottenere i bilanci con il conteggio delle voci compilate e degli indicatori ESG associati
try {
    $sqlBilanci = "
        SELECT
            B.Azienda,
            B.Data,
            B.Stato,
            COUNT(DISTINCT RB.Voce) AS voci_compilate,
            COUNT(DISTINCT C.Indicatore) AS tot_indicatori
        FROM Bilancio B
        JOIN Azienda A ON A.RagioneSociale = B.Azienda
        LEFT JOIN RigaBilancio RB ON RB.AziendaBil = B.Azienda AND RB.DataBil = B.Data
        LEFT JOIN Collegamento C ON C.Bilancio = B.Azienda AND C.DataBil = B.Data
        WHERE A.Responsabile = :responsabile
        GROUP BY B.Azienda, B.Data, B.Stato
        ORDER BY B.Data DESC, B.Azienda ASC
    ";
    $stmtBilanci = $conn->prepare($sqlBilanci);
    $stmtBilanci->bindValue(':responsabile', $_SESSION['cf']);
    $stmtBilanci->execute();
    $bilanci = $stmtBilanci->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Errore caricamento bilanci: " . htmlspecialchars($e->getMessage());
}

try {
    $stmtVoci = $conn->query("SELECT Nome FROM Voce ORDER BY Nome");
    $voci = $stmtVoci->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Errore caricamento voci: " . htmlspecialchars($e->getMessage());
}

try {
    $stmtIndicatori = $conn->query("SELECT Nome FROM Indicatore ORDER BY Nome");
    $indicatori = $stmtIndicatori->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Errore caricamento indicatori: " . htmlspecialchars($e->getMessage());
}

//  trasforma lo stato del bilancio in classi Bootstrap per il badge.
function badgeClassFromStato(string $stato): string
{
    return match ($stato) {
        'Bozza' => 'bg-secondary',
        'In Revisione' => 'bg-warning text-dark',
        'Approvato' => 'bg-success',
        'Respinto' => 'bg-danger',
        default => 'bg-light text-dark',
    };
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
        <div class="col-12 text-center text-md-start">
            <h2 class="border-bottom pb-2">Area Responsabile Aziendale</h2>
            <p class="text-muted">Gestisci le tue aziende, compila i bilanci e associa i dati di sostenibilita ESG.</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card h-100 shadow-sm border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Registrazione Azienda</h5>
                </div>
                <div class="card-body">
                    <form action="/controller/responsabileController.php" method="POST" enctype="multipart/form-data"
                        novalidate data-action-form="registraAzienda">
                        <input type="hidden" name="azione" value="registraAzienda">
                        <div class="alert alert-danger d-none py-2 small mb-3" role="alert" data-form-error></div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Ragione Sociale (Univoca)</label>
                                <input type="text" name="ragione_sociale" class="form-control" required>
                                <div class="invalid-feedback">Inserisci la ragione sociale.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nome Azienda</label>
                                <input type="text" name="nome" class="form-control" placeholder="es. ESG S.p.A."
                                    required>
                                <div class="invalid-feedback">Inserisci il nome azienda.</div>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Settore</label>
                                <input type="text" name="settore" class="form-control" placeholder="es. Tech" required>
                                <div class="invalid-feedback">Inserisci il settore.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Logo Azienda</label>
                                <input type="file" name="logo_file" class="form-control"
                                    accept=".jpg,.jpeg,.png,.webp,.gif" required>
                                <div class="form-text">Formati supportati: JPG, JPEG, PNG, WEBP, GIF. Max 5MB.</div>
                                <div class="invalid-feedback">Carica il logo azienda.</div>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Partita IVA</label>
                                <input type="text" name="piva" class="form-control" required pattern="[0-9]{11}"
                                    maxlength="11">
                                <div class="invalid-feedback">Inserisci una Partita IVA valida (11 cifre).</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Num. Dipendenti</label>
                                <input type="number" name="n_dipendenti" class="form-control" min="1" required>
                                <div class="invalid-feedback">Inserisci il numero dipendenti.</div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-info text-white w-100">Registra Azienda</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 shadow-sm border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Nuovo Bilancio di Esercizio</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted">All'atto della creazione, lo stato sara impostato su "Bozza".</p>
                    <form action="/controller/responsabileController.php" method="POST" novalidate
                        data-action-form="creaBilancio">
                        <input type="hidden" name="azione" value="creaBilancio">
                        <div class="alert alert-danger d-none py-2 small mb-3" role="alert" data-form-error></div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Seleziona Azienda</label>
                            <select name="azienda" class="form-select" required>
                                <option value="">Seleziona azienda...</option>
                                <?php foreach ($aziende as $azienda): ?>
                                    <option value="<?php echo htmlspecialchars($azienda['RagioneSociale']); ?>">
                                        <?php echo htmlspecialchars($azienda['RagioneSociale']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Seleziona un'azienda.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Data Creazione</label>
                            <input type="date" name="data" class="form-control" value="<?php echo date('Y-m-d'); ?>"
                                required>
                            <div class="invalid-feedback">Seleziona una data.</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Crea Bilancio</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- NUOVA RIGA PER COMPILAZIONE ESTESA -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card h-100 shadow-sm border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Compila Voci di Bilancio</h5>
                </div>
                <div class="card-body">
                    <form action="/controller/responsabileController.php" method="POST" novalidate
                        data-action-form="popolaBilancio">
                        <input type="hidden" name="azione" value="popolaBilancio">
                        <div class="alert alert-danger d-none py-2 small mb-3" role="alert" data-form-error></div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Seleziona Bilancio</label>
                            <select name="azienda" class="form-select" required>
                                <option value="">Seleziona...</option>
                                <?php foreach ($bilanci as $b): ?>
                                    <option value="<?php echo htmlspecialchars($b['Azienda']); ?>"
                                        data-date="<?php echo htmlspecialchars($b['Data']); ?>">
                                        <?php echo htmlspecialchars($b['Azienda'] . ' (' . $b['Data'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Seleziona un bilancio.</div>
                            <!-- Campo nascosto per la data che serve al controller -->
                            <input type="hidden" name="data" value="">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Voce Contabile</label>
                            <select name="voce" class="form-select" required>
                                <option value="">Seleziona Voce...</option>
                                <?php foreach ($voci as $v): ?>
                                    <option value="<?php echo htmlspecialchars($v['Nome']); ?>">
                                        <?php echo htmlspecialchars($v['Nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Seleziona una voce.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Importo (€)</label>
                            <input type="number" name="importo" class="form-control" step="0.01" required>
                            <div class="invalid-feedback">Inserisci un importo valido.</div>
                        </div>

                        <button type="submit" class="btn btn-warning w-100">Salva Voce</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 shadow-sm border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Integrazione ESG</h5>
                </div>
                <div class="card-body">
                    <form action="/controller/responsabileController.php" method="POST" novalidate
                        data-action-form="creaCollegamentoESG">
                        <input type="hidden" name="azione" value="creaCollegamentoESG">
                        <div class="alert alert-danger d-none py-2 small mb-3" role="alert" data-form-error></div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Bilancio</label>
                                <select name="azienda" class="form-select" required>
                                    <option value="">Seleziona...</option>
                                    <?php foreach ($bilanci as $b): ?>
                                        <option value="<?php echo htmlspecialchars($b['Azienda']); ?>"
                                            data-date-esg="<?php echo htmlspecialchars($b['Data']); ?>">
                                            <?php echo htmlspecialchars($b['Azienda']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Seleziona bilancio.</div>
                                <input type="hidden" name="dataBil" value="">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Voce Riferimento</label>
                                <select name="voce" class="form-select" required>
                                    <option value="">Seleziona...</option>
                                    <?php foreach ($voci as $v): ?>
                                        <option value="<?php echo htmlspecialchars($v['Nome']); ?>">
                                            <?php echo htmlspecialchars($v['Nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Seleziona voce.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Indicatore ESG</label>
                            <select name="indicatore" class="form-select" required>
                                <option value="">Seleziona Indicatore...</option>
                                <?php foreach ($indicatori as $i): ?>
                                    <option value="<?php echo htmlspecialchars($i['Nome']); ?>">
                                        <?php echo htmlspecialchars($i['Nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Seleziona un indicatore.</div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Data Ril.</label>
                                <input type="date" name="dataRilevazione" class="form-control" required>
                                <div class="invalid-feedback">Data richiesta.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Valore</label>
                                <input type="number" name="valoreNum" class="form-control" step="0.01" required>
                                <div class="invalid-feedback">Valore richiesto.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Fonte</label>
                                <input type="text" name="fonte" class="form-control" placeholder="Es. report interno"
                                    required>
                                <div class="invalid-feedback">Fonte richiesta.</div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Collega ESG</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Monitoraggio Bilanci e Indicatori ESG</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID Bilancio</th>
                                    <th>Azienda</th>
                                    <th>Stato</th>
                                    <th>Voci Template</th>
                                    <th>Indicatori ESG</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bilanci)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Nessun bilancio disponibile.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bilanci as $bilancio): ?>
                                        <tr>
                                            <td>#<?php echo htmlspecialchars($bilancio['Data'] . '-' . $bilancio['Azienda']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($bilancio['Azienda']); ?></td>
                                            <td>
                                                <span
                                                    class="badge <?php echo badgeClassFromStato((string) $bilancio['Stato']); ?>">
                                                    <?php echo htmlspecialchars($bilancio['Stato']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo (int) $bilancio['voci_compilate']; ?></td>
                                            <td><?php echo (int) $bilancio['tot_indicatori']; ?></td>
                                            <td><span class="text-muted">Operazioni avanzate in arrivo</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        // definisce i campi obbligatori per ogni azione in modo centralizzato
        const requiredByAction = {
            registraAzienda: ['ragione_sociale', 'nome', 'settore', 'logo_file', 'piva', 'n_dipendenti'],
            creaBilancio: ['azienda', 'data'],
            popolaBilancio: ['azienda', 'voce', 'importo'],
            creaCollegamentoESG: ['azienda', 'voce', 'indicatore', 'dataRilevazione', 'valoreNum', 'fonte']
        };

        //  leggono l’azione del form e recuperano un campo per nome
        function getActionName(form) {
            const hidden = form.querySelector('input[name="azione"]');
            return hidden ? hidden.value : '';
        }

        function getField(form, name) {
            return form.querySelector(`[name="${name}"]`);
        }
        // aggiunge o rimuove classi bootstarp
        function setFieldValidity(field, isValid) {
            if (!field) return;
            field.classList.toggle('is-invalid', !isValid);
            field.classList.toggle('is-valid', isValid && field.value.trim() !== '');
        }
        
        // prende l'azione corrente, controlla i campi obbligatori e valida
        function validateForm(form) {
            const action = getActionName(form);
            const requiredFields = requiredByAction[action] || [];
            let allValid = true;

            requiredFields.forEach((fieldName) => {
                const field = getField(form, fieldName);
                if (!field) return;
                const value = (field.value || '').trim();
                const valid = (field.type === 'file')
                    ? (field.files && field.files.length > 0 && field.checkValidity())
                    : (value !== '' && field.checkValidity());
                setFieldValidity(field, valid);
                if (!valid) {
                    allValid = false;
                }
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
                    if (firstInvalid) {
                        firstInvalid.focus();
                    }
                }
            });
        });
        const selectBilPopola = document.querySelector('form[data-action-form="popolaBilancio"] select[name="azienda"]');
        if (selectBilPopola) {
            selectBilPopola.addEventListener('change', function () {
                const selectedOption = this.options[this.selectedIndex];
                const date = selectedOption.getAttribute('data-date');
                const hiddenData = this.form.querySelector('input[name="data"]');
                if (hiddenData) hiddenData.value = date || '';
            });
        }

        const selectBilEsg = document.querySelector('form[data-action-form="creaCollegamentoESG"] select[name="azienda"]');
        if (selectBilEsg) {
            selectBilEsg.addEventListener('change', function () {
                const selectedOption = this.options[this.selectedIndex];
                const date = selectedOption.getAttribute('data-date-esg');
                const hiddenData = this.form.querySelector('input[name="dataBil"]');
                if (hiddenData) hiddenData.value = date || '';
            });
        }

    })();
</script>