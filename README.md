# Progetto_BDD_LPC

Progetto Basi di dati - A.S 2025/2026 - Lippi - Pontini - Cattani

## ESG Balance - README completo

### Indice

1. Panoramica del progetto
2. Stack tecnologico
3. Requisiti
4. Configurazione iniziale (passo-passo)
5. Avvio applicazione
6. Credenziali di test (seed)
7. Funzionalita per ruolo
8. Struttura cartelle e file principali
9. Classi, controller e logica applicativa
10. Modello dati SQL (tabelle, viste, procedure, trigger)
11. Logging MongoDB
12. Upload file
13. Troubleshooting

## 1. Panoramica del progetto

ESG Balance e una web app PHP per:

- gestione utenti con ruoli (Admin, Responsabile, Revisore)
- gestione aziende e bilanci
- popolamento voci contabili
- collegamento indicatori ESG alle voci di bilancio
- revisione dei bilanci (note e giudizio finale)
- statistiche pubbliche aggregate
- audit log degli eventi principali su MongoDB

Routing principale:

- `/index.php` -> homepage o dashboard in base al ruolo in sessione
- `/views/login.php` -> login
- `/views/register.php` -> registrazione
- `/views/statistiche.php` -> statistiche pubbliche

## 2. Stack tecnologico

- PHP 8.1+ (da composer.lock)
- MySQL (accesso via PDO)
- MongoDB (php extension ext-mongodb + libreria mongodb/mongodb)
- Composer (autoload PSR-4 e dipendenze)
- Bootstrap (asset locali in `public/assets/bootstrap`)
- CSS custom in `public/assets/css/style.css`

## 3. Requisiti

Minimo richiesto:

- PHP >= 8.1
- Estensioni PHP:
  - `pdo_mysql`
  - `mongodb` (`ext-mongodb`)
- MySQL attivo
- MongoDB attivo
- Composer

Nota importante configurazione DB SQL:

- File: `public/configurationDB/Database.php`
- Parametri default attuali:
  - host: `127.0.0.1`
  - port: `8889`
  - db: `ESGBALANCE`
  - user: `root`
  - pass: `root`
- Se il tuo MySQL non usa questi valori, aggiornali prima di avviare.

Nota MongoDB:

- File: `public/configurationDB/MongoDB.php`
- Connessione attuale: `mongodb://127.0.0.1:27017`
- DB: `LOG_ESG`
- Collezione: `log`

## 4. Configurazione iniziale (passo-passo)

Da eseguire nella root progetto:
`/Users/emanuelelippi/Desktop/Scrivania - Mac mini di Emanuele/Progetto_BDD_LPC`

### 4.1 Installa dipendenze PHP

- Se `vendor/` e gia presente, puoi saltare.
- Altrimenti:
  - `composer install`
  - oppure (se usi il file locale): `php composer.phar install`

### 4.2 Crea schema SQL e oggetti DB

Ordine consigliato (importante):

1. `create-db-template.sql`
2. `ESGBALANCE-procedures1.sql`
3. `ESGBALANCE-procedures2.sql`
4. `ESGBALANCE-procedures3.sql`
5. `ESGBALANCE-Procedures-Responsabili.sql`
6. `ESGBALANCE-procedures-Revisori.sql`
7. `Viste.sql`
8. `seed-test-data.sql` (facoltativo ma consigliato)

Note:

- `sql/create-db-template.sql` fa `DROP DATABASE IF EXISTS ESGBALANCE`.
  Quindi resetta tutto il database.
- `seed-test-data.sql` e pensato per essere ri-eseguito: svuota tabelle e reinserisce dati test.

### 4.3 Crea DB/collezione MongoDB log

Metodo semplice:

- Avvia mongo
- Esegui:
  - `use LOG_ESG`
  - `db.createCollection("log")`

In alternativa puoi usare:

- `MongoDB/playground-1.mongodb.js`

### 4.4 Verifica cartelle upload

Devono esistere (sono gia presenti nel progetto):

- `public/uploads/cv`
- `public/uploads/esg`
- `public/uploads/aziende`

I controller tentano anche la creazione automatica se manca la cartella.

## 5. Avvio applicazione

Opzione A (sviluppo rapido con server PHP integrato):
`php -S 127.0.0.1:8000 -t public`

Poi apri:
`http://127.0.0.1:8000/index.php`

Opzione B (Apache/Nginx):

- configura document root su cartella `public/`
- assicurati che percorsi assoluti tipo `/views/login.php` puntino alla stessa root web

## 6. Credenziali di test (seed)

Attenzione: il login usa Codice Fiscale + password (NON username).

Admin:

- CF: `ADM0000000000000001`
- Password: `admin123`

Responsabile:

- CF: `RSP0000000000000001`
- Password: `resp123`

Revisore:

- CF: `REV0000000000000001`
- Password: `rev123`

Nel seed sono presenti anche altri utenti `REV*`/`RSP*`.

## 7. Funzionalita per ruolo

Pubblico (non autenticato):

- homepage con invito login
- pagina statistiche pubbliche:
  - numero aziende
  - numero revisori
  - azienda con maggiore affidabilita
  - classifica bilanci per numero indicatori ESG

Admin:

- gestione template bilancio:
  - inserimento nuove voci contabili
- gestione indicatori ESG:
  - indicatore generico
  - indicatore ambientale (con codice normativa)
  - indicatore sociale (con frequenza/ambito)
  - upload immagine indicatore
- assegnazione revisori ai bilanci

Responsabile:

- registrazione nuova azienda (con upload logo)
- creazione bilancio
- popolamento voci di bilancio con importo
- integrazione ESG:
  - collega indicatori ESG alle voci del bilancio
  - data rilevazione, valore numerico, fonte
- monitoraggio bilanci aziendali con stato e conteggi

Revisore:

- gestione competenze:
  - assegna competenza esistente
  - crea nuova competenza e assegnala
- analisi puntuale:
  - inserisci note su singole righe di bilancio
- giudizio finale bilancio:
  - approvazione
  - approvazione con rilievi
  - respingimento

## 8. Struttura cartelle e file principali

Root:

- `composer.json` / `composer.lock`
- `sql/` -> schema, procedure, trigger, viste, seed
- `MongoDB/playground-1.mongodb.js` -> creazione DB/collection log
- `public/` -> codice applicativo web

`public/`:

- `index.php` -> entry point, instrada alla dashboard in base al ruolo
- `configurationDB/`
  - `Database.php`
  - `MongoDB.php`
- `controller/`
  - `authController.php`
  - `registerController.php`
  - `adminController.php`
  - `responsabileController.php`
  - `revisoreController.php`
- `views/`
  - `header.php` / `footer.php` / `home.php`
  - `login.php` / `register.php` / `logout.php`
  - `statistiche.php`
  - `dashboard/admin_dashboard.php`
  - `dashboard/responsabile_dashboard.php`
  - `dashboard/revisore_dashboard.php`
- `uploads/`
  - `cv/`
  - `esg/`
  - `aziende/`
- `assets/`
  - `bootstrap/`
  - `css/style.css`

## 9. Classi, controller e logica applicativa

### 9.1 Classi principali

`App\configurationDB\Database`

- Pattern Singleton.
- Crea e mantiene una sola connessione PDO MySQL.
- Metodi:
  - `getInstance()`: restituisce istanza singleton
  - `getConnection()`: restituisce oggetto PDO

`App\configurationDB\MongoDB`

- Gestisce la connessione a Mongo.
- Collezione: `LOG_ESG.log`
- Metodo:
  - `logEvent(tipoEvento, cfUtente, ruolo, dettagli=[])`:
    salva evento con timestamp UTCDateTime e metadati attore.

### 9.2 Controller (flussi server)

`authController.php`

- Riceve CF e password da login.
- Chiama procedure SQL: Autenticazione.
- In caso successo: avvia sessione e salva user/role/cf.
- Logga esito su MongoDB.

`registerController.php`

- Gestisce registrazione per i 3 ruoli.
- Procedure SQL usate:
  - RegistraAdmin
  - RegistraResponsabile
  - RegistraRevisore
  - RegistraEmail
  - InserisciNuovaCompetenza
  - AssegnaCompetenza
- Validazioni:
  - CV obbligatorio per Responsabile
  - CV max 5MB, estensioni: pdf/doc/docx
  - gestione email multiple e duplicati
  - per Revisore obbligo selezione competenze

`adminController.php`

- Accesso consentito solo a sessione role=Admin.
- Procedure SQL usate:
  - InserisciIndicatore
  - InserisciIndicatoreAmbientale
  - InserisciIndicatoreSociale
  - InserisciVoce
  - AssegnaRevisore
- Upload immagine indicatore:
  - max 5MB
  - estensioni: jpg/jpeg/png/webp/gif

`responsabileController.php`

- Accesso consentito solo a role=Responsabile.
- Procedure SQL usate:
  - RegistraAzienda
  - creaBilancio
  - popolaBilancio
  - creaCollegamentoESG
- Upload logo azienda:
  - max 5MB
  - estensioni: jpg/jpeg/png/webp/gif

`revisoreController.php`

- Accesso consentito solo a role=Revisore.
- Procedure SQL usate:
  - InserisciNote
  - InserisciGiudizio
  - InserisciNuovaCompetenza
  - AssegnaCompetenza

### 9.3 Views e comportamento UI

- Dashboard specifiche per ruolo con query dirette al DB per popolare select/tabelle.
- Validazioni client-side JS:
  - campi obbligatori per azione
  - select dinamiche (azienda -> date bilancio -> voci)
  - toggle campi in base al contesto (tipo indicatore, tipo competenza, rilievi)
- Messaggi utente tramite query string:
  - `?success=...`
  - `?error=...`

## 10. Modello dati SQL (tabelle, viste, procedure, trigger)

### 10.1 Tabelle principali

Utenti e ruoli:

- Utente
- Email
- Administrator
- Revisore
- Responsabile

Competenze revisori:

- Competenza
- Appartiene (competenza <-> revisore, livello 0..5)

Dominio azienda/bilancio:

- Azienda
- Bilancio (stato: Bozza, In Revisione, Approvato, Respinto)
- Voce
- RigaBilancio

Dominio ESG:

- Indicatore
- Ambientale
- Sociale
- Collegamento (lega righe bilancio a indicatori ESG)

Revisione:

- Revisione
- Nota

### 10.2 Viste

- NumeroAziende
- NumeroRevisoriESG
- AziendaAffidabilitaMaggiore
- Vista_ClassificaESG

### 10.3 Procedure stored (per file)

`sql/ESGBALANCE-procedures1.sql`

- RegistraAdmin
- RegistraRevisore
- RegistraResponsabile
- RegistraAzienda
- Autenticazione
- RegistraEmail

`sql/ESGBALANCE-procedures2.sql`

- InserisciIndicatore
- InserisciIndicatoreAmbientale
- InserisciIndicatoreSociale

`sql/ESGBALANCE-procedures3.sql`

- InserisciVoce
- AssegnaRevisore

`sql/ESGBALANCE-Procedures-Responsabili.sql`

- creaBilancio
- popolaBilancio
- creaCollegamentoESG

`sql/ESGBALANCE-procedures-Revisori.sql`

- InserisciNuovaCompetenza
- AssegnaCompetenza
- InserisciNote
- InserisciGiudizio

### 10.4 Trigger

- AggiornaStatoBilancio
  - dopo inserimento in Revisione, imposta Bilancio.Stato='In Revisione'
- BilancioValutato
  - quando tutti i revisori assegnati hanno espresso esito:
    - almeno un Respingimento -> Stato='Respinto'
    - altrimenti -> Stato='Approvato'
- Nrevisioni
  - incrementa Revisore.NRevisioni al primo passaggio da esito NULL a non-NULL

## 11. Logging MongoDB

Evento loggati tipicamente:

- login riuscito/fallito
- registrazione riuscita/fallita
- accessi non autorizzati a controller di ruolo
- operazioni business (inserimento indicatori, creazione bilanci, note, giudizi, ecc.)
- errori upload e validazioni lato server

Formato base documento log:

- timestamp
- tipo_evento
- attore (cf, ruolo)
- dettagli
- descrizione

## 12. Upload file

CV Responsabile:

- percorso pubblico: `/uploads/cv/...`
- estensioni consentite: pdf, doc, docx
- dimensione max: 5MB

Immagine indicatore ESG:

- percorso pubblico: `/uploads/esg/...`
- estensioni consentite: jpg, jpeg, png, webp, gif
- dimensione max: 5MB

Logo azienda:

- percorso pubblico: `/uploads/aziende/...`
- estensioni consentite: jpg, jpeg, png, webp, gif
- dimensione max: 5MB

Naming file:

- viene generato nome "safe" con CF + timestamp (+ random per immagini/logo).

## 13. Troubleshooting

Problema: errore connessione MySQL

- Controlla host/port/user/password in `public/configurationDB/Database.php`
- Verifica che il DB ESGBALANCE esista e che script SQL siano stati eseguiti.

Problema: "procedure non trovata"

- Non hai caricato tutti i file SQL procedure.
- Riesegui in ordine il punto 4.2.

Problema: errore MongoDB / ext-mongodb assente

- Installa estensione PHP mongodb.
- Verifica servizio MongoDB attivo su 127.0.0.1:27017.
- Esegui composer install se vendor non e allineato.

Problema: upload non funzionante

- Verifica permessi di scrittura in `public/uploads/*`.
- Controlla limiti PHP upload: upload_max_filesize, post_max_size.

Problema: pagine non trovate / redirect strani

- Assicurati che la web root sia `public/` (non root progetto).

Fine.
