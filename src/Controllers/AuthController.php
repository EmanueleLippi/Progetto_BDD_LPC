<?php
namespace App\Controllers;

use APP\Config\Database;
use App\Config\MongoDB;
use PDOException;
use PDO;

class AuthController
{
    private $mongo;

    public function __construct()
    {
        // Initialize MongoDB logging
        try {
            $this->mongo = new MongoDB();
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
        //Estraggo i dati dal form
        $cf = $_POST['cf'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($cf) || empty($password)) {
            echo "<script>alert('Inserisci CF e password'); window.location.href='/login';</script>";
            return;
        }
        $db = Database::getInstance()->getConnection();
        try {
            //Chiamo la procedures del db SQL
            $stmt = $db->prepare("CALL Autenticazione(:cf, :pw)");
            $stmt->execute([':cf' => $cf, ':pw' => $password]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC); //prendo la prima riga del risultato

            //check del risultato
            if ($user) {
                // Login corretto
                //creo la sessione
                $_SESSION['user_cf'] = $user['U.Cf'];
                $_SESSION['User_Role'] = $user['Ruolo'];

                //Log dell'evento
                $logger = new MongoDB();
                $logger->logEvent(
                    'LOGIN_SUCCESS',
                    $user['U.Cf'],
                    $user['Ruolo'],
                    ['ip' => $_SERVER['REMOTE_ADDR']]
                );
                // C. Reindirizzo alla dashboard
                // (Per ora reindirizzo in home, poi inserirò la dashboard)
                header("Location: /");
                exit;

            } else {
                //Login Fallito
                //Log del fallimento
                $logger = new MongoDB();
                $logger->logEvent(
                    'LOGIN_FAILED',
                    $cf,
                    'GUEST',
                    ['Errore' => 'Credenziali errate']
                );
                echo "<script>alert('Credenziali errate'); window.location.href='/login';</script>";
                return;
            }
        } catch (PDOException $e) {
            echo "Errore Database SQL: " . $e->getMessage();
        } catch (\Exception $e) {
            echo "Errore Generico: " . $e->getMessage();
        }
    }

    public function logout()
    {
        //log del logout
        if (isset($_SESSION["user_cf"])) {
            $logger = new MongoDB();
            $logger->logEvent('LOGOUT', $_SESSION['user_cf'], $_SESSION['user_role'] ?? 'UNKNOWN');
        }
        //distruggo la sessione
        session_destroy();
        header("Location: /login");
        exit;
    }
}
