<?php
use App\configurationDB\Database;
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/header.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (Exception $e) {
    $erroreDb = "Errore di connessione al database: " . $e->getMessage();
}

// Inizializzazione variabili
$numAziende = 0;
$numRevisori = 0;
$aziendaTop = null;
$classificaESG = [];

if (!isset($erroreDb)) {
    try {
        //numero aziende
        $stmt1 = $conn->query("SELECT NumeroAziende FROM NumeroAziende");
        $numAziende = $stmt1->fetchColumn() ?: 0;

        //numero revisori
        $stmt2 = $conn->query("SELECT NumeroRevisoriESG FROM NumeroRevisoriESG");
        $numRevisori = $stmt2->fetchColumn() ?: 0;

        //azienda affidabilità maggiore
        $stmt3 = $conn->query("SELECT * FROM AziendaAffidabilitaMaggiore");
        $aziendaTop = $stmt3->fetch(PDO::FETCH_ASSOC);

        //classifica bilanci (ordinata per indicatori ESG decrescenti)
        $stmt4 = $conn->query("SELECT * FROM Vista_ClassificaESG ORDER BY Numero_Indicatori_ESG DESC, DataBilancio DESC");
        $classificaESG = $stmt4->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $erroreDb = "Errore nel caricamento delle statistiche: " . $e->getMessage();
    }
}
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="display-5 fw-bold text-primary">Statistiche Piattaforma</h1>
            <p class="text-muted lead">Dati aggregati e classifiche sulle performance ESG delle aziende registrate.</p>
        </div>
    </div>

    <?php if (isset($erroreDb)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($erroreDb); ?>
        </div>
    <?php else: ?>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0 bg-light text-center">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <h5 class="text-uppercase text-muted small fw-bold">Aziende Registrate</h5>
                        <h2 class="display-3 text-dark mb-0 fw-bold"><?php echo (int) $numAziende; ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0 bg-light text-center">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <h5 class="text-uppercase text-muted small fw-bold">Revisori ESG</h5>
                        <h2 class="display-3 text-dark mb-0 fw-bold"><?php echo (int) $numRevisori; ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 shadow border-success text-center">
                    <div class="card-header bg-success text-white fw-bold">
                        🏆 Azienda Più Affidabile
                    </div>
                    <div class="card-body d-flex flex-column justify-content-center">
                        <?php if ($aziendaTop && !empty($aziendaTop['NomeAzienda'])): ?>
                            <h3 class="text-success fw-bold mb-1">
                                <?php echo htmlspecialchars($aziendaTop['NomeAzienda']); ?>
                            </h3>
                            <p class="text-muted small mb-2">Settore: <?php echo htmlspecialchars($aziendaTop['Settore']); ?>
                            </p>
                            <div class="fs-5">
                                Affidabilità:
                                <strong><?php echo number_format((float) $aziendaTop['Percentuale_Affidabilita'], 1); ?>%</strong>
                            </div>
                            <div class="small text-muted mt-2">
                                (<?php echo (int) $aziendaTop['NumApprovazioni']; ?> su
                                <?php echo (int) $aziendaTop['NumTotali']; ?> bilanci approvati)
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">Dati insufficienti per il calcolo.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Classifica Bilanci per Indicatori ESG</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Posizione</th>
                                        <th>Nome Azienda</th>
                                        <th>Settore</th>
                                        <th>Data Bilancio</th>
                                        <th class="text-center">Numero Indicatori ESG</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($classificaESG)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Nessun bilancio o indicatore
                                                associato al momento.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $pos = 1;
                                        foreach ($classificaESG as $riga): ?>
                                            <tr>
                                                <td class="ps-4 fw-bold text-muted">#<?php echo $pos++; ?></td>
                                                <td class="fw-bold"><?php echo htmlspecialchars($riga['NomeAzienda']); ?></td>
                                                <td><?php echo htmlspecialchars($riga['Settore']); ?></td>
                                                <td><?php echo htmlspecialchars($riga['DataBilancio']); ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary rounded-pill fs-6">
                                                        <?php echo (int) $riga['Numero_Indicatori_ESG']; ?>
                                                    </span>
                                                </td>
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

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>