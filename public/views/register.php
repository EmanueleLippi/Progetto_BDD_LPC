<?php
require_once __DIR__ . '/header.php';
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

            <form action="/controller/registerController.php" method="POST">
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
                        <option value="admin">Admin</option>
                        <option value="responsabile">Responsabile</option>
                        <option value="revisore">Revisore</option>
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
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="username" class="form-label fw-bold">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <!-- TODO inserire comportamento dinamico che mostra e nasconde campi a seconda della selezione del ruolo da assumere -->

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

<?php
// Includiamo il footer
require_once __DIR__ . '/footer.php';
?>