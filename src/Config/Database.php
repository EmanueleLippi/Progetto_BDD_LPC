<?php

namespace App\Config; // cos'è questa riga --> namespace --> serve a organizzare il codice in cartelle

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $pdo;

    private $host = '127.0.0.1';
    private $db = 'ESGBalance'; // Placeholder name, update if different
    private $user = 'root';
    private $pass = 'root'; // Default XAMPP/MAMP password is often empty or 'root'
    private $charset = 'utf8mb4';

    private function __construct()
    {
        $dsn = "mysql:host=$this->host;dbname=$this->db;charset=$this->charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }
}
