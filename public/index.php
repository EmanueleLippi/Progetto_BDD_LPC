<?php
require_once __DIR__ . '/../vendor/autoload.php';
require __DIR__ . "/views/header.php";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION["role"])) {
    switch ($_SESSION["role"]) {
        case "admin":
            require __DIR__ . "/views/dashboard/admin_dashboard.php";
            break;
        case "revisore ESG":
            require __DIR__ . "/views/dashboard/revisore_dashboard.php";
            break;
        case "responsabile":
            require __DIR__ . "/views/dashboard/responsabile_dashboard.php";
            break;
    }
} else {
    require __DIR__ . "/views/home.php";
}
require __DIR__ . "/views/footer.php";
?>