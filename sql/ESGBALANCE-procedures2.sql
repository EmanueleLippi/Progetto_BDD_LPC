DELIMITER $$

-- 1. Inserimento Nuovo Indicatore Generico
CREATE PROCEDURE InserisciIndicatore(
    IN Nome VARCHAR(200),
    IN Immagine VARCHAR(200), 
    IN Rilevanza INT, 
    IN Amministratore VARCHAR(20)
)
BEGIN
    DECLARE is_admin INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    -- CONTROLLO SICUREZZA: Verifica che l'utente sia un Amministratore
    SELECT COUNT(*) INTO is_admin 
    FROM Administrator 
    WHERE Utente = Amministratore;

    IF is_admin = 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'ERRORE: Permesso negato. Solo gli amministratori possono inserire indicatori.';
    ELSE
        START TRANSACTION;
            INSERT INTO Indicatore (Nome, Immagine, Rilevanza, Amministratore)
            VALUES (Nome, Immagine, Rilevanza, Amministratore);
        COMMIT;
    END IF;
END $$

-- 2. Inserimento Nuovo Indicatore Ambientale
CREATE PROCEDURE InserisciIndicatoreAmbientale(
    IN Nome VARCHAR(200),
    IN Immagine VARCHAR(200), 
    IN Rilevanza INT, 
    IN Amministratore VARCHAR(20),
    IN amb VARCHAR(100)
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
    FROM Administrator 
    WHERE Utente = Amministratore;

    IF is_admin = 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'ERRORE: Permesso negato. Solo gli amministratori possono inserire indicatori.';
    ELSE
        START TRANSACTION;
            INSERT INTO Indicatore (Nome, Immagine, Rilevanza, Amministratore)
            VALUES (Nome, Immagine, Rilevanza, Amministratore);
            
            INSERT INTO Ambientale (Indicatore, CodNormRile)
            VALUES (Nome, amb);
        COMMIT;
    END IF;
END $$

-- 3. Inserimento Nuovo Indicatore Sociale
CREATE PROCEDURE InserisciIndicatoreSociale(
    IN Nome VARCHAR(200),
    IN Immagine VARCHAR(200), 
    IN Rilevanza INT, 
    IN Amministratore VARCHAR(20),
    IN Freq INT, 
    IN ambito VARCHAR(100)
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
    FROM Administrator 
    WHERE Utente = Amministratore;

    IF is_admin = 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'ERRORE: Permesso negato. Solo gli amministratori possono inserire indicatori.';
    ELSE
        START TRANSACTION;
            INSERT INTO Indicatore (Nome, Immagine, Rilevanza, Amministratore)
            VALUES (Nome, Immagine, Rilevanza, Amministratore);
            
            INSERT INTO Sociale (Indicatore, Frequenza, AmbitoSociale)
            VALUES (Nome, Freq, ambito);
        COMMIT;
    END IF;
END $$

DELIMITER;