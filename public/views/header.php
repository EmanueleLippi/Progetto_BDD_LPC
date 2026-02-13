<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESG Balance</title>

    <link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm py-2">
        <div class="container-fluid">
            <a class="navbar-brand fs-2 fw-bold text-success" href="/">ESG Balance</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">

                    <li class="nav-item me-lg-4 mb-2 mb-lg-0">
                        <a class="btn btn-outline-primary fw-bold" href="/views/statistiche.php">
                            Statistiche Pubbliche
                        </a>
                    </li>

                    <?php if (isset($_SESSION['user'])): ?>
                        <li class="nav-item me-lg-3">
                            <span class="nav-link fw-semibold">Ciao,
                                <?php echo htmlspecialchars($_SESSION['user']); ?></span>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-danger text-white btn-sm" href="/views/logout.php">Esci</a>
                        </li>
                    <?php endif; ?>

                </ul>
            </div>
        </div>
    </nav>
    <main>