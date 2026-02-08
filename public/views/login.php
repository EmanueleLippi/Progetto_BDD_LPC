<?php
require_once __DIR__ . '/header.php';
?>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="card shadow-lg p-4" style="width: 100%; max-width: 400px; border-radius: 15px;">
        <div class="card-body">
            <div class="text-center mb-4">
                <h3 class="text-primary fw-bold">ESG Balance</h3>
                <p class="text-muted">Accedi al sistema</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger text-center" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="/controller/authController.php" method="POST">
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

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                        Accedi
                    </button>
                </div>
            </form>
        </div>
        <div class="card-footer text-center bg-white border-0 mt-2">
            <small class="text-muted">Progetto BDD - LPC 2025/2026</small>
        </div>
    </div>
</div>

<?php
// Includiamo il footer
require_once __DIR__ . '/footer.php';
?>