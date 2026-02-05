<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ESG Balance</title>

    <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .login-card {
            max-width: 450px;
            /* Leggermente più largo per i messaggi di errore */
            width: 100%;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .login-header {
            background-color: #0d6efd;
            color: white;
            padding: 20px;
            border-radius: 5px 5px 0 0;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="card login-card">
        <div class="login-header">
            <h3>ESG Login</h3>
            <p class="mb-0">Accedi alla piattaforma</p>
        </div>
        <div class="card-body p-4">

            <form action="../index.php?action=login" method="POST" class="needs-validation" novalidate id="loginForm">

                <div class="mb-3">
                    <label for="email" class="form-label">Indirizzo Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="nome@esempio.it"
                        required>
                    <div class="invalid-feedback">
                        Inserisci un indirizzo email valido (deve contenere @).
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="••••••••"
                        required>
                    <div class="invalid-feedback" id="passwordError">
                        La password è richiesta.
                    </div>
                    <div class="form-text small mt-2">
                        La password deve contenere:
                        <ul class="mb-0 ps-3">
                            <li id="rule-upper" class="text-muted">Una lettera maiuscola</li>
                            <li id="rule-number" class="text-muted">Un numero</li>
                            <li id="rule-special" class="text-muted">Un carattere speciale (!@#$%^&*)</li>
                        </ul>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">Accedi</button>
                </div>

            </form>
        </div>
        <div class="card-footer text-center text-muted py-3">
            <small>Non hai un account? Contatta l'amministratore.</small>
        </div>
    </div>

    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>

    <script>
        (function () {
            'use strict'

            // Recuperiamo il form
            var form = document.getElementById('loginForm');
            var passwordInput = document.getElementById('password');
            var passwordError = document.getElementById('passwordError');

            // Regole per i colori dell'aiuto
            var ruleUpper = document.getElementById('rule-upper');
            var ruleNumber = document.getElementById('rule-number');
            var ruleSpecial = document.getElementById('rule-special');

            // 1. Controllo in tempo reale mentre scrivi la password
            passwordInput.addEventListener('input', function () {
                var val = passwordInput.value;

                // Controlla Maiuscola
                if (/[A-Z]/.test(val)) {
                    ruleUpper.classList.remove('text-muted', 'text-danger');
                    ruleUpper.classList.add('text-success');
                } else {
                    ruleUpper.classList.remove('text-success');
                    ruleUpper.classList.add('text-danger');
                }

                // Controlla Numero
                if (/[0-9]/.test(val)) {
                    ruleNumber.classList.remove('text-muted', 'text-danger');
                    ruleNumber.classList.add('text-success');
                } else {
                    ruleNumber.classList.remove('text-success');
                    ruleNumber.classList.add('text-danger');
                }

                // Controlla Carattere Speciale
                if (/[!@#\$%\^&\*]/.test(val)) {
                    ruleSpecial.classList.remove('text-muted', 'text-danger');
                    ruleSpecial.classList.add('text-success');
                } else {
                    ruleSpecial.classList.remove('text-success');
                    ruleSpecial.classList.add('text-danger');
                }
            });

            // 2. Controllo finale quando clicchi "Accedi"
            form.addEventListener('submit', function (event) {
                var val = passwordInput.value;
                var isValidPassword = true;

                // Regex completa: Maiuscola + Numero + Speciale
                // Se manca qualcosa, impostiamo un errore personalizzato
                if (!/[A-Z]/.test(val) || !/[0-9]/.test(val) || !/[!@#\$%\^&\*]/.test(val)) {
                    isValidPassword = false;
                    passwordInput.setCustomValidity("Requisiti non soddisfatti");
                    passwordError.textContent = "La password non rispetta i requisiti di sicurezza.";
                } else {
                    passwordInput.setCustomValidity(""); // Reset errore
                }

                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                form.classList.add('was-validated');
            }, false)
        })()
    </script>
</body>

</html>