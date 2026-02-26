<?php
namespace App\configurationDB;
//importazione delle classi globali necessarie
use MongoDB\Client;
use MongoDB\BSON\UTCDateTime;

//classe per gestire la connesione al Databse MongoDB
class MongoDB
{
    private $collection; //oggetto di connessione MongoDB

    public function __construct()
    {
        //inizializzo la connessione al database
        try {
            //connetto al server local
            $client = new Client("mongodb://127.0.0.1:27017");
            //seleziono il db e la collezione
            //LOG_ESG è il nome del database, log è la collezione
            $this->collection = $client->LOG_ESG->log;
        } catch (\Exception $e) {
            die("Errore di connessione al database MongoDB: " . $e->getMessage());
        }
    }

    /**
     * 
     * Metodo per la funzione di registrazione log sul db mongo
     * @param $tipoEvento
     * @param $cfUtente
     * @param $ruolo
     * @param $dettagli
     * @return void
     */
    public function logEvent($tipoEvento, $cfUtente, $ruolo, $dettagli = [])
    {
        //prepara la formattazione del documento da inserire --> documento = log azione
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
        //inserisce il documento nella collezione
        $this->collection->insertOne($documento);


    }
}

?>