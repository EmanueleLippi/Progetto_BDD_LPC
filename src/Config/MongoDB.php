<?php
namespace App\Config;

use MongoDB\Client;
use MongoDB\BSON\UTCDateTime;

class MongoLoader
{
    private $collection;

    public function __construct()
    {

        try {
            //connetto al server local
            $client = new Client("mongodb://127.0.0.1:27017");
            //seleziono il db e la collezione
            $this->collection = $client->LOG_ESG->log;
        } catch (\Exception $e) {
            die("Errore di connessione al database MongoDB: " . $e->getMessage());
        }
    }

    public function logEvent($tipoEvento, $cfUtente, $ruolo, $dettagli = [])
    {
        $documento = [
            'timestamp' => new UTCDateTime(),
            'tipo_evento' => $tipoEvento,
            'attore' => [
                'cf' => $cfUtente,
                'ruolo' => $ruolo
            ],
            'dettagli' => $dettagli,
            'descrizione' => "L'utente $cfUtente ha eseguito l'azione: $tipoEvento"
        ];
        $this->collection->insertOne($documento);


    }
}

?>