DELIMITER $$

-- 1. Inserimento nuova competenza (nel caso non esista nel sistema)
CREATE PROCEDURE InserisciNuovaCompetenza(
    IN p_Nome VARCHAR(200),
    IN p_RevisoreRichiedente VARCHAR(20)
)
BEGIN
    DECLARE is_revisore INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    -- Controllo: L'utente è un Revisore?
    SELECT COUNT(*) INTO is_revisore 
    FROM Revisore 
    WHERE Utente = p_RevisoreRichiedente;

    IF is_revisore = 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'ERRORE: Utente non autorizzato. Solo i revisori possono aggiungere competenze.';
    ELSE
        START TRANSACTION;
            -- INSERT IGNORE evita errori se la competenza esiste già
            INSERT IGNORE INTO Competenza(Nome) VALUES(p_Nome);
        COMMIT;
    END IF;
END $$

-- 2. Assegnazione competenza al revisore
CREATE PROCEDURE AssegnaCompetenza(
    IN p_Competenza VARCHAR(200), 
    IN p_Revisore VARCHAR(20), 
    IN p_Livello INT
)
BEGIN
    DECLARE is_revisore INT DEFAULT 0;

DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

-- Controllo: Il revisore esiste? (Auto-verifica dell'identità)
SELECT COUNT(*) INTO is_revisore
FROM Revisore
WHERE
    Utente = p_Revisore;

IF is_revisore = 0 THEN SIGNAL SQLSTATE '45000'
SET
    MESSAGE_TEXT = 'ERRORE: Revisore non trovato.';

ELSE START TRANSACTION;

INSERT INTO
    Appartiene (Competenza, Revisore, Livello)
VALUES (
        p_Competenza,
        p_Revisore,
        p_Livello
    );

COMMIT;

END IF;

END $$

-- 3. Inserimento delle note su singola voce
CREATE PROCEDURE InserisciNote(
    IN p_Revisore VARCHAR(20), 
    IN p_VoceRiga VARCHAR(100), 
    IN p_RigaData DATE, 
    IN p_RigaAzienda VARCHAR(255), 
    IN p_TestoNota TEXT
)
BEGIN
    DECLARE is_assigned INT DEFAULT 0;

DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

-- CONTROLLO DI SICUREZZA ROBUSTO
-- Verifichiamo se il revisore è stato ASSEGNATO a questo specifico bilancio
SELECT COUNT(*) INTO is_assigned
FROM Revisione
WHERE
    Revisore = p_Revisore
    AND BilancioAz = p_RigaAzienda
    AND DataBil = p_RigaData;

IF is_assigned = 0 THEN SIGNAL SQLSTATE '45000'
SET
    MESSAGE_TEXT = 'ERRORE: Operazione negata. Il revisore non è assegnato a questo bilancio.';

ELSE START TRANSACTION;

INSERT INTO
    Nota (
        Revisore,
        VoceRiga,
        RigaData,
        RigaAzienda,
        TestoNota,
        DataNota
    )
VALUES (
        p_Revisore,
        p_VoceRiga,
        p_RigaData,
        p_RigaAzienda,
        p_TestoNota,
        CURRENT_DATE()
    );

COMMIT;

END IF;

END $$

-- 4. Inserimento del giudizio complessivo
CREATE PROCEDURE InserisciGiudizio(
    IN p_Revisore VARCHAR(20), 
    IN p_DataBil DATE, 
    IN p_BilancioAz VARCHAR(255), 
    IN p_Esito ENUM('Approvazione', 'Approvazione con rilievi', 'Respingimento'), 
    IN p_Rilievi VARCHAR(300)
)
BEGIN
    DECLARE is_assigned INT DEFAULT 0;

DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

-- Controllo: Il revisore è assegnato a questo bilancio?
SELECT COUNT(*) INTO is_assigned
FROM Revisione
WHERE
    Revisore = p_Revisore
    AND BilancioAz = p_BilancioAz
    AND DataBil = p_DataBil;

IF is_assigned = 0 THEN SIGNAL SQLSTATE '45000'
SET
    MESSAGE_TEXT = 'ERRORE: Operazione negata. Il revisore non è assegnato a questo bilancio.';

ELSE START TRANSACTION;

UPDATE Revisione
SET
    Esito = p_Esito,
    Rilievi = p_Rilievi,
    DataGiudizio = CURRENT_DATE()
WHERE
    Revisore = p_Revisore
    AND DataBil = p_DataBil
    AND BilancioAz = p_BilancioAz;

COMMIT;

END IF;

END $$

-- Trigger invariato (non richiede controlli utente diretti, scatta sugli eventi DB)
CREATE TRIGGER BilancioValutato
AFTER UPDATE ON Revisione
FOR EACH ROW
BEGIN
    DECLARE conteggio_assegnati INT;
    DECLARE conteggio_valutati INT;
    DECLARE conteggio_respinti INT;

    SELECT COUNT(*) INTO conteggio_assegnati
    FROM Revisione
    WHERE BilancioAz = NEW.BilancioAz AND DataBil = NEW.DataBil;

    SELECT COUNT(*) INTO conteggio_valutati
    FROM Revisione
    WHERE BilancioAz = NEW.BilancioAz AND DataBil = NEW.DataBil AND Esito IS NOT NULL;

    IF conteggio_assegnati = conteggio_valutati THEN
        SELECT COUNT(*) INTO conteggio_respinti
        FROM Revisione
        WHERE BilancioAz = NEW.BilancioAz AND DataBil = NEW.DataBil AND Esito = 'Respingimento';

        IF conteggio_respinti > 0 THEN
            UPDATE Bilancio
            SET Stato = 'Respinto'
            WHERE Azienda = NEW.BilancioAz AND Data = NEW.DataBil;
        ELSE
            UPDATE Bilancio
            SET Stato = 'Approvato'
            WHERE Azienda = NEW.BilancioAz AND Data = NEW.DataBil;
        END IF;
    END IF;
END $$

DELIMITER;