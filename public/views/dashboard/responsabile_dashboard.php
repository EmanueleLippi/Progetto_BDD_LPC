<?php
//require_once __DIR__ . '/../header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-12 text-center text-md-start">
            <h2 class="border-bottom pb-2">Area Responsabile Aziendale</h2>
            <p class="text-muted">Gestisci le tue aziende, compila i bilanci e associa i dati di sostenibilità ESG.</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card h-100 shadow-sm border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Registrazione Azienda</h5>
                </div>
                <div class="card-body">
                    <form id="formAzienda" onsubmit="handleResponsabileAction(event, 'registraAzienda')">
                        <div class="row g-2 mb-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold">Ragione Sociale (Univoca)</label>
                                <input type="text" name="ragione_sociale" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Settore</label>
                                <input type="text" name="settore" class="form-control" placeholder="es. Tech">
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Partita IVA</label>
                                <input type="text" name="piva" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Num. Dipendenti</label>
                                <input type="number" name="n_dipendenti" class="form-control">
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
                    <p class="small text-muted">All'atto della creazione, lo stato sarà impostato su "bozza".</p>
                    <form id="formNuovoBilancio" onsubmit="handleResponsabileAction(event, 'creaBilancio')">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Seleziona Azienda</label>
                            <select name="azienda_piva" class="form-select" id="selectAziende" required>
                                <option value="">Caricamento aziende...</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Data Creazione</label>
                            <input type="date" name="data_creazione" class="form-control"
                                value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Crea Bilancio</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Monitoraggio Bilanci e Indicatori ESG</h5>
                    <button class="btn btn-sm btn-outline-light" onclick="refreshTable()">Aggiorna Dati</button>
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
                            <tbody id="tabella-corpo">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Logica JavaScript per rendere la dashboard dinamica
    document.addEventListener('DOMContentLoaded', () => {
        loadAziende();
        refreshTable();
    });

    function loadAziende() {
        fetch('/controller/responsabileController.php?action=listAziende')
            .then(r => r.json())
            .then(data => {
                const select = document.getElementById('selectAziende');
                select.innerHTML = data.map(a => `<option value="${a.piva}">${a.nome}</option>`).join('');
            });
    }

    function refreshTable() {
        fetch('/controller/responsabileController.php?action=getBilanciResponsabile')
            .then(r => r.json())
            .then(data => {
                const tbody = document.getElementById('tabella-corpo');
                tbody.innerHTML = data.map(b => `
                <tr>
                    <td>#${b.id}</td>
                    <td>${b.azienda_nome}</td>
                    <td><span class="badge ${b.stato_css}">${b.stato}</span></td>
                    <td>${b.voci_compilate}</td>
                    <td>${b.tot_indicatori}</td>
                    <td>
                        <div class="btn-group">
                            <button onclick="apriCompilazione(${b.id})" class="btn btn-sm btn-outline-primary">Popola Voci</button>
                            <button onclick="apriESG(${b.id})" class="btn btn-sm btn-outline-success">Associa ESG</button>
                        </div>
                    </td>
                </tr>
            `).join('');
            });
    }

    function handleResponsabileAction(event, action) {
        event.preventDefault();
        const formData = new FormData(event.target);

        fetch(`/controller/responsabileController.php?action=${action}`, {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert("Operazione completata con successo!");
                    refreshTable();
                    event.target.reset();
                } else {
                    alert("Errore: " + res.message);
                }
            });
    }
</script>

<?php //require_once __DIR__ . '/../footer.php'; ?>