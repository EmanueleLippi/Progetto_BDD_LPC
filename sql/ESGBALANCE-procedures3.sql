DELIMITER $$

-- 1. Inserimento di una nuova voce (Creazione Template)
CREATE PROCEDURE InserisciVoce(
    IN p_Nome varchar(100), 
    IN p_Descrizione VARCHAR(300), 
    IN p_AmministratoreRichiedente VARCHAR(20) -- Parametro per il controllo
)
BEGIN
    DECLARE is_admin INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    -- CONTROLLO SICUREZZA: Verifica che chi inserisce sia Admin
    SELECT COUNT(*) INTO is_admin 
    FROM ADMINISTRATOR 
    WHERE Utente = p_AmministratoreRichiedente;

    IF is_admin = 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'ERRORE: Permesso negato. Solo gli amministratori possono modificare il template.';
    ELSE
        START TRANSACTION;
            INSERT INTO Voce(Nome, Descrizione, Amministratore)
            VALUES (p_Nome, p_Descrizione, p_AmministratoreRichiedente);
        COMMIT;
    END IF;
END $$

-- 2. Assegnare un revisore ad un bilancio aziendale
-- NOTA: Ho aggiunto p_AdminRichiedente per poter verificare i permessi!
CREATE PROCEDURE AssegnaRevisore(
    IN p_Revisore VARCHAR(20), 
    IN p_DataBil DATE, 
    IN p_BilancioAz VARCHAR(255),
    IN p_AdminRichiedente VARCHAR(20) 
)
BEGIN
    DECLARE is_admin INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    -- CONTROLLO SICUREZZA
    SELECT COUNT(*) INTO is_admin 
    FROM ADMINISTRATOR 
    WHERE Utente = p_AdminRichiedente;

    IF is_admin = 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'ERRORE: Permesso negato. Solo gli amministratori possono assegnare le revisioni.';
    ELSE
        START TRANSACTION;
            -- Inseriamo NULL per i campi del giudizio non ancora espressi
            -- Questo attiverà automaticamente il TRIGGER 'AggiornaStatoBilancio'
            INSERT INTO Revisione(Revisore, DataBil, BilancioAz, DataGiudizio, Esito, Rilievi) 
            VALUES(p_Revisore, p_DataBil, p_BilancioAz, NULL, NULL, NULL);
        COMMIT;
    END IF;
END $$

-- 3. Trigger per lo stato "In Revisione" (Invariato, va benissimo così)
CREATE TRIGGER AggiornaStatoBilancio
AFTER INSERT ON Revisione
FOR EACH ROW
BEGIN
    UPDATE Bilancio
    SET Stato = 'In Revisione'
    WHERE Azienda = NEW.BilancioAz AND Data = NEW.DataBil;
END $$

DELIMITER;