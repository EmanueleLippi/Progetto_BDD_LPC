DELIMITER $$

-- 1. Inserimento di una nuova voce (creazione Template)
CREATE Procedure InserisciVoce(
    IN Nome varchar(100), 
    IN Descrizione VARCHAR(300), 
    IN Amministratore VARCHAR(20)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
        INSERT INTO Voce(Nome, Descrizione, Amministratore)
        VALUES (Nome, Descrizione, Amministratore);
    COMMIT;
END $$

-- 2. Assegnare un revisore ad un bilancio aziendale
CREATE Procedure AssegnaRevisore(
    IN Revisore VARCHAR(20), 
    IN DataBil DATE, 
    IN BilancioAz VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
        -- Inseriamo NULL per i campi del giudizio non ancora espressi
        INSERT INTO Revisione(Revisore, DataBil, BilancioAz, DataGiudizio, Esito, Rilievi) 
        VALUES(Revisore, DataBil, BilancioAz, NULL, NULL, NULL);
    COMMIT;
END $$

-- 3. Trigger per lo stato "In Revisione"
CREATE Trigger AggiornaStatoBilancio
AFTER INSERT ON Revisione
FOR EACH ROW
BEGIN
    UPDATE Bilancio
    SET Stato = 'In Revisione'
    WHERE Azienda = NEW.BilancioAz AND Data = NEW.DataBil;
END $$

DELIMITER;