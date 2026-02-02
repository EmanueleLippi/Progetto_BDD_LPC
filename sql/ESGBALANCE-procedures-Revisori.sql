DELIMITER $$

-- 1. Inserimento nuova competenza (nel caso non esista nel sistema)
CREATE Procedure InserisciNuovaCompetenza(IN Nome VARCHAR(200))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
        -- INSERT IGNORE evita errori se la competenza esiste già
        INSERT IGNORE INTO Competenza(Nome) VALUES(Nome);
    COMMIT;
END $$

-- 2. Assegnazione competenza al revisore
CREATE PROCEDURE AssegnaCompetenza(IN Competenza VARCHAR(200), IN Revisore VARCHAR(20), IN Livello INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION; -- Corretto i due punti con punto e virgola
        INSERT INTO Appartiene(Competenza, Revisore, Livello)
        VALUES (Competenza, Revisore, Livello);
    COMMIT;
END $$

-- 3. Inserimento delle note su singola voce
CREATE PROCEDURE InserisciNote(
    IN Revisore VARCHAR(20), 
    IN VoceRiga VARCHAR(100), 
    IN RigaData DATE, 
    IN RigaAzienda VARCHAR(255), 
    IN TestoNota TEXT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
        -- Corretto nome tabella da 'Note' a 'Nota'
        INSERT INTO Nota(Revisore, VoceRiga, RigaData, RigaAzienda, TestoNota, DataNota)
        VALUES (Revisore, VoceRiga, RigaData, RigaAzienda, TestoNota, CURRENT_DATE());
    COMMIT;
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
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
        UPDATE Revisione
        SET Esito = p_Esito, 
            Rilievi = p_Rilievi,
            DataGiudizio = CURRENT_DATE()
        WHERE Revisore = p_Revisore 
          AND DataBil = p_DataBil 
          AND BilancioAz = p_BilancioAz;
    COMMIT;
END $$

-- Trigger per l'aggiornamento dello stato del bilancio
CREATE TRIGGER BilancioValutato
AFTER UPDATE ON Revisione
FOR EACH ROW
BEGIN
    -- Dichiarazione variabili
    DECLARE conteggio_assegnati INT;
    DECLARE conteggio_valutati INT;
    DECLARE conteggio_respinti INT;

    -- 1.  Numero di revisioni assegnate per questo bilancio
    SELECT COUNT(*) INTO conteggio_assegnati
    FROM Revisione
    WHERE BilancioAz = NEW.BilancioAz AND DataBil = NEW.DataBil;

    -- 2. Contiamo quanti hanno già dato un esito (non NULL)
    SELECT COUNT(*) INTO conteggio_valutati
    FROM Revisione
    WHERE BilancioAz = NEW.BilancioAz AND DataBil = NEW.DataBil AND Esito IS NOT NULL;

    -- 3. Se tutti hanno votato, procediamo al calcolo dello stato
    IF conteggio_assegnati = conteggio_valutati THEN
        
        -- Contiamo quanti hanno votato 'Respingimento'
        SELECT COUNT(*) INTO conteggio_respinti
        FROM Revisione
        WHERE BilancioAz = NEW.BilancioAz AND DataBil = NEW.DataBil AND Esito = 'Respingimento';

        IF conteggio_respinti > 0 THEN
            -- CASO A: Almeno un respinto -> Bilancio RESPINTO 
            UPDATE Bilancio
            SET Stato = 'Respinto'
            WHERE Azienda = NEW.BilancioAz AND Data = NEW.DataBil;
        ELSE
            -- CASO B: Nessun respinto -> Bilancio APPROVATO 
            -- (Include sia approvazione piena che con rilievi, come da specifica)
            UPDATE Bilancio
            SET Stato = 'Approvato'
            WHERE Azienda = NEW.BilancioAz AND Data = NEW.DataBil;
        END IF;
    END IF;
END $$

DELIMITER;