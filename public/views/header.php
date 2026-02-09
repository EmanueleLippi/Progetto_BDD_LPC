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

    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="/">ESG Balance</a>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">

                    <?php if (isset($_SESSION['user'])): ?>
                        <li class="nav-item">
                            <span class="nav-link">Ciao, <?php echo $_SESSION['user'] ?></span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-danger text-white btn-sm" href="/views/logout.php">Esci</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link btn btn-primary text-white btn-sm px-3" href="/views/login.php">Accedi</a>
                        </li>
                    <?php endif; ?>

                </ul>
            </div>
        </div>
    </nav>
    <main>
