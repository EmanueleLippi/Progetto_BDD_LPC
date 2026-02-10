<?php

namespace App\configurationDB;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $pdo;

    private $host = '127.0.0.1';
    private $port = '8889';
    private $db = 'ESGBALANCE';
    private $user = 'root';
    private $pass = 'root';
    private $charset = 'utf8mb4';

    /**
     * Costruttore privato per impedire la creazione di nuove istanze dall'esterno.
     * Inizializza la connessione al database utilizzando PDO.
     */
    private function __construct()
    {
        $dsn = "mysql:host=$this->host;port=$this->port;dbname=$this->db;charset=$this->charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Solleva eccezioni in caso di errori
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Restituisce i risultati come array associativi
            PDO::ATTR_EMULATE_PREPARES => false, // Usa prepared statements reali
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int) $e->getCode());
        }
    }

    /**
     * Metodo statico per ottenere l'unica istanza della classe Database (Singleton).
     * Se l'istanza non esiste, la crea. Altrimenti, restituisce quella esistente.
     *
     * @return Database L'istanza univoca della classe Database.
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Restituisce l'oggetto di connessione PDO.
     *
     * @return PDO L'oggetto PDO per interagire con il database.
     */
    public function getConnection()
    {
        return $this->pdo;
    }
}
