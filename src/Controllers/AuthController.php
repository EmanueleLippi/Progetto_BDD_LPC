<?php
namespace App\Controllers;

use App\Config\MongoLoader;

class AuthController
{
    private $mongo;

    public function __construct()
    {
        // Initialize MongoDB logging
        try {
            $this->mongo = new MongoLoader();
        } catch (\Exception $e) {
            // Handle connection error if needed, or rely on MongoLoader's die()
            die("Errore nella connessione al database");
        }
    }

    public function showLogin()
    {
        // Render the login view
        require_once __DIR__ . '/../Views/auth/login.php';
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            // Example logging
            if ($this->mongo) {
                // Determine mechanism (Guest vs User) later
                $this->mongo->logEvent(
                    'LOGIN_ATTEMPT',
                    $email,
                    'GUEST',
                    ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']
                );
            }

            // TODO: Authenticate user

            echo "Login attempt recorded.";
        }
    }
}
