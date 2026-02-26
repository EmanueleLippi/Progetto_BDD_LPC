<?php

namespace App\configurationDB;
//importazione delle classi globali necessarie
use PDO;
use PDOException;

//classe per gestire la connesione al Databse SQL
class Database
{
    private static $instance = null;    //istanza unica della classe --> evito piu istanze
    private $pdo;                       //oggetto di connessione PDO

    private $host = '127.0.0.1';        //indirizzo del server
    private $port = '8889';             //porta del server
    private $db = 'ESGBALANCE';         //nome del database
    private $user = 'root';             //username per db
    private $pass = 'root';             //password per db
    private $charset = 'utf8mb4';       //caratteri accettati

    /**
     * Costruttore privato per impedire la creazione di nuove istanze dall'esterno.
     * Inizializza la connessione al database utilizzando PDO.
     */
    private function __construct()
    {
        $dsn = "mysql:host=$this->host;port=$this->port;dbname=$this->db;charset=$this->charset"; //stringa con dati per la connessione
        $options = [ //opzioni della connessione e gestione errori
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Solleva eccezioni in caso di errori
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // Restituisce i risultati come array associativi
            PDO::ATTR_EMULATE_PREPARES => false,                // Usa prepared statements reali
        ];

        try {
            // creazione oggetto PDO per la connessione al database
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
        //il self::$instance è la variabile statica che contiene l'istanza unica della classe
        //notazione :: in php indica una costante o una proprietà statica
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
        return $this->pdo; //restituisce l'oggetto PDO per la connessione
    }
}
