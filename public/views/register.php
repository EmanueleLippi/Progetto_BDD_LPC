<?php
require_once __DIR__ . '/header.php';
$error = $_GET['error'] ?? null;
?>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="card shadow-lg p-4" style="width: 100%; max-width: 400px; border-radius: 15px;">
        <div class="card-body">
            <div class="text-center mb-4">
                <h3 class="text-primary fw-bold">ESG Balance</h3>
                <p class="text-muted">Registrati al sistema</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger text-center" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="/controller/registerController.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="cf" class="form-label fw-bold">Codice Fiscale</label>
                    <input type="text" class="form-control" id="cf" name="cf" placeholder="Inserisci il tuo CF" required
                        autofocus>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-bold">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="••••••••"
                        required>
                </div>

                <div class="mb-3">
                    <label for="ruolo" class="form-label fw-bold">Ruolo</label>
                    <select class="form-select" id="ruolo" name="ruolo" placeholder="Seleziona un ruolo" required>
                        <option value="Admin">Admin</option>
                        <option value="Responsabile">Responsabile</option>
                        <option value="Revisore">Revisore</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="dataNascita" class="form-label fw-bold">Data di Nascita</label>
                    <input type="date" class="form-control" id="dataNascita" name="dataNascita" required>
                </div>
                <div class="mb-3">
                    <label for="LuogoNascita" class="form-label fw-bold">Luogo di Nascita</label>
                    <input type="text" class="form-control" id="LuogoNascita" name="LuogoNascita" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label fw-bold">Email</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="email" class="form-control" id="email" name="email[]" required>
                        <button type="button" class="btn btn-outline-primary" id="mailAdditor" aria-label="Aggiungi email">+</button>
                    </div>
                    <div id="additional_emails" class="mt-2"></div>
                </div>
                <div class="mb-3">
                    <label for="username" class="form-label fw-bold">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <!-- CV per responsabili -->
                <div class="mb-3" id="cv_path_div">
                    <label for="cv_path" class="form-label fw-bold">CV Path</label>
                    <input type="file" class="form-control" id="cv_path" name="cv_path" accept=".pdf,.doc,.docx">
                </div>

                <!-- Competenze per revisori -->
                <div class="mb-3" id="competenze_div">
                    <label for="competenze" class="form-label fw-bold">Competenze</label>
                    <select class="form-select" id="competenze" name="competenze">
                        <option value="">Seleziona una competenza</option>
                        <?php
                        use App\configurationDB\Database;
                        require_once __DIR__ . '/../../vendor/autoload.php';
                        $database = Database::getInstance();
                        $conn = $database->getConnection();
                        try {
                            $stmt = $conn->prepare("SELECT * FROM Competenza");
                            $stmt->execute();
                        } catch (\PDOException $th) {
                            echo "[ERRORE] Query sql di selezione competenze fallita" . $th->getMessage() . "\n";
                            header("Location: /views/register.php?error=Errore di registrazione");
                            exit;
                        }
                        $competenze = $stmt->fetchAll();
                        foreach ($competenze as $competenza) {
                            echo "<option value='" . $competenza['Nome'] . "'>" . $competenza['Nome'] . "</option>";
                        }
                        ?>
                    </select>
                    <label for="livello_competenza" class="form-label fw-bold mt-2">Livello (0-5)</label>
                    <select class="form-select" id="livello_competenza">
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                    <button type="button" class="btn btn-primary btn-lg shadow-sm" id="aggiungi_competenza_btn">
                        Aggiungi
                    </button><br>
                    <div id="competenze_selezionate" class="mt-3">
                        <label>Competenze selezionate:</label>
                        <div id="competenze_selezionate_div" class="mt-2"></div>
                    </div>
                    <div class="alert alert-danger mt-3 d-none" role="alert" id="competenze_alert"></div>
                    <label>Aggiungi competenza:</label>
                    <input type="text" class="form-control" id="nuova_competenza" name="nuova_competenza">
                    <label for="livello_nuova_competenza" class="form-label fw-bold mt-2">Livello (0-5)</label>
                    <select class="form-select" id="livello_nuova_competenza">
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                    <button type="button" class="btn btn-primary btn-lg shadow-sm mt-2"
                        id="aggiungi_nuova_competenza_btn">
                        Aggiungi nuova
                    </button>
                </div>
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                        Registrati
                    </button>
                </div>
            </form>
        </div>
        <div class="card-footer text-center bg-white border-0 mt-2">
            <small class="text-muted">Progetto BDD - LPC 2025/2026</small>
            <small class="text-muted">Hai già un account? <a href="/views/login.php">Accedi</a></small>
        </div>
    </div>
</div>

<script>
    (function () {
        const ruoloSelect = document.getElementById('ruolo');
        const cvDiv = document.getElementById('cv_path_div');
        const cvInput = document.getElementById('cv_path');
        const competenzeSelect = document.getElementById('competenze');
        const competenzeContainer = document.getElementById('competenze_selezionate_div');
        const competenzeDiv = document.getElementById('competenze_div');
        const competenzeAlert = document.getElementById('competenze_alert');
        const livelloCompetenzaSelect = document.getElementById('livello_competenza');
        const aggiungiCompetenzaBtn = document.getElementById('aggiungi_competenza_btn');
        const nuovaCompetenzaInput = document.getElementById('nuova_competenza');
        const livelloNuovaCompetenzaSelect = document.getElementById('livello_nuova_competenza');
        const aggiungiNuovaCompetenzaBtn = document.getElementById('aggiungi_nuova_competenza_btn');
        const mailAdditorBtn = document.getElementById('mailAdditor');
        const additionalEmailsContainer = document.getElementById('additional_emails');
        const form = competenzeSelect.closest('form');
        const selected = new Map();

        function toggleCv() {
            const isResponsabile = ruoloSelect.value === 'Responsabile';
            cvDiv.style.display = isResponsabile ? 'block' : 'none';
            cvInput.required = isResponsabile;
            cvInput.disabled = !isResponsabile;
            if (!isResponsabile) {
                cvInput.value = '';
            }
        }

        function toggleCompetenze() {
            const isRevisore = ruoloSelect.value === 'Revisore';
            competenzeDiv.style.display = isRevisore ? 'block' : 'none';
            competenzeSelect.disabled = !isRevisore;
            if (!isRevisore) {
                competenzeSelect.value = '';
                selected.clear();
                renderSelected();
            }
        }

        function renderSelected() {
            competenzeContainer.innerHTML = '';
            selected.forEach((level, name) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'd-flex align-items-center gap-2 mb-2';

                const badge = document.createElement('span');
                badge.className = 'badge bg-secondary';
                badge.textContent = name;

                const levelBadge = document.createElement('span');
                levelBadge.className = 'badge bg-info text-dark';
                levelBadge.textContent = `Livello ${level}`;

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-outline-danger';
                removeBtn.textContent = 'Rimuovi';
                removeBtn.addEventListener('click', () => {
                    selected.delete(name);
                    renderSelected();
                });

                wrapper.appendChild(badge);
                wrapper.appendChild(levelBadge);
                wrapper.appendChild(removeBtn);
                competenzeContainer.appendChild(wrapper);
            });
        }

        function showCompetenzeAlert(message) {
            competenzeAlert.textContent = message;
            competenzeAlert.classList.remove('d-none');
        }

        function hideCompetenzeAlert() {
            competenzeAlert.textContent = '';
            competenzeAlert.classList.add('d-none');
        }

        function addCompetenza(value, level) {
            const trimmed = value.trim();
            if (!trimmed) {
                return;
            }
            if (selected.has(trimmed)) {
                const existingLevel = selected.get(trimmed);
                if (existingLevel !== level) {
                    showCompetenzeAlert('Competenza già selezionata con un livello diverso.');
                }
                return;
            }
            selected.set(trimmed, level);
            hideCompetenzeAlert();
            renderSelected();
        }

        aggiungiCompetenzaBtn.addEventListener('click', () => {
            const level = parseInt(livelloCompetenzaSelect.value, 10);
            if (!competenzeSelect.value) {
                showCompetenzeAlert('Seleziona una competenza dall\'elenco.');
                return;
            }
            addCompetenza(competenzeSelect.value, Number.isNaN(level) ? 0 : level);
            competenzeSelect.value = '';
            livelloCompetenzaSelect.value = '0';
        });

        aggiungiNuovaCompetenzaBtn.addEventListener('click', () => {
            const level = parseInt(livelloNuovaCompetenzaSelect.value, 10);
            addCompetenza(nuovaCompetenzaInput.value, Number.isNaN(level) ? 0 : level);
            nuovaCompetenzaInput.value = '';
            livelloNuovaCompetenzaSelect.value = '0';
        });

        mailAdditorBtn.addEventListener('click', () => {
            const wrapper = document.createElement('div');
            wrapper.className = 'd-flex align-items-center gap-2 mt-2';

            const input = document.createElement('input');
            input.type = 'email';
            input.className = 'form-control';
            input.name = 'email[]';
            input.placeholder = 'Email aggiuntiva (opzionale)';
            input.required = false;

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-outline-danger';
            removeBtn.textContent = 'Rimuovi';
            removeBtn.addEventListener('click', () => {
                wrapper.remove();
            });

            wrapper.appendChild(input);
            wrapper.appendChild(removeBtn);
            additionalEmailsContainer.appendChild(wrapper);
        });

        form.addEventListener('submit', (event) => {
            const allEmails = form.querySelectorAll('input[name="email[]"]');
            allEmails.forEach((input, index) => {
                const isMain = index === 0;
                const trimmedValue = input.value.trim();
                if (!isMain && trimmedValue === '') {
                    input.disabled = true;
                    return;
                }
                input.disabled = false;
                input.value = trimmedValue;
            });

            const existing = form.querySelectorAll('input[name="competenze_selezionate[]"]');
            existing.forEach((node) => node.remove());
            const existingLevels = form.querySelectorAll('input[name="competenze_livelli[]"]');
            existingLevels.forEach((node) => node.remove());
            if (ruoloSelect.value === 'Revisore' && selected.size === 0) {
                showCompetenzeAlert('Sei revisore: seleziona almeno una competenza.');
                event.preventDefault();
                return;
            }
            hideCompetenzeAlert();
            selected.forEach((level, name) => {
                const hiddenName = document.createElement('input');
                hiddenName.type = 'hidden';
                hiddenName.name = 'competenze_selezionate[]';
                hiddenName.value = name;
                form.appendChild(hiddenName);

                const hiddenLevel = document.createElement('input');
                hiddenLevel.type = 'hidden';
                hiddenLevel.name = 'competenze_livelli[]';
                hiddenLevel.value = String(level);
                form.appendChild(hiddenLevel);
            });
        });

        ruoloSelect.addEventListener('change', () => {
            toggleCv();
            toggleCompetenze();
        });
        toggleCv();
        toggleCompetenze();
    })();
</script>

<?php
// Includiamo il footer
require_once __DIR__ . '/footer.php';
?>
