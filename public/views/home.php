<?php if (isset($_SESSION["user"])): ?>
    <div class="welcome-section">
        <h2>Benvenuto in ESG Balance,
            <?php echo $_SESSION["user"]; ?>!
        </h2>
        <p>La piattaforma per la gestione sostenibile del bilancio aziendale.</p>
        <p>Il tuo ruolo è:
            <?php echo $_SESSION["role"]; ?>
        </p>
    </div>
<?php else: ?>
    <div class="welcome-section">
        <h2>Benvenuto in ESG Balance</h2>
        <p>La piattaforma per la gestione sostenibile del bilancio aziendale.</p>

        <div class="actions">
            <a href="/views/login.php" class="btn btn-primary">Accedi al Portale</a>
        </div>
    </div>
<?php endif; ?>