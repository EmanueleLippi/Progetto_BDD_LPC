DELIMITER $$

-- 1. Inserimento Nuovo Indicatore Generico
CREATE PROCEDURE InserisciIndicatore(IN Nome VARCHAR(200),
IN Immagine VARCHAR(200), IN Rilevanza INT, IN Amministratore VARCHAR(20))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    START TRANSACTION;
        INSERT INTO Indicatore (Nome, Immagine, Rilevanza, Amministratore)
        VALUES (Nome, Immagine, Rilevanza, Amministratore);
    COMMIT;
END $$

-- 1. Inserimento Nuovo Indicatore Ambientale
CREATE PROCEDURE InserisciIndicatoreAmbientale(IN Nome VARCHAR(200),
IN Immagine VARCHAR(200), IN Rilevanza INT, IN Amministratore VARCHAR(20),
IN amb VARCHAR(100))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    START TRANSACTION;
        INSERT INTO Indicatore (Nome, Immagine, Rilevanza, Amministratore)
        VALUES (Nome, Immagine, Rilevanza, Amministratore);
        INSERT INTO Ambientale (Indicatore, CodNormRile)
        VALUES (Nome, amb);
    COMMIT;
END $$

-- 1. Inserimento Nuovo Indicatore Sociale
CREATE PROCEDURE InserisciIndicatoreSociale(IN Nome VARCHAR(200),
IN Immagine VARCHAR(200), IN Rilevanza INT, IN Amministratore VARCHAR(20),
IN Freq INT, IN ambito VARCHAR(100))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    START TRANSACTION;
        INSERT INTO Indicatore (Nome, Immagine, Rilevanza, Amministratore)
        VALUES (Nome, Immagine, Rilevanza, Amministratore);
        INSERT INTO Sociale (Indicatore, Frequenza, AmbitoSociale)
        VALUES (Nome, Freq, ambito);
    COMMIT;
END $$