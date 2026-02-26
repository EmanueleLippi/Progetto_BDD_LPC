DELIMITER $$

-- 1. Creazione Bilancio d'esercizio
CREATE PROCEDURE creaBilancio(
    IN p_Azienda VARCHAR(255), 
    IN p_Data DATE,
    IN p_Responsabile VARCHAR(20) -- CF dell'utente che prova a fare l'azione
)
BEGIN
    DECLARE is_authorized INT DEFAULT 0;

    -- se avviene un errore qualsiasi fai rollback -> annulla tutta la transazione
    -- resignal -> rilancia l'errore al chiamante
    -- dopo aver eseguito il blocco esce dal begin ... end
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    -- CONTROLLO DI SICUREZZA ROBUSTO
    -- Verifichiamo se p_Responsabile è DAVVERO il capo di p_Azienda
    SELECT COUNT(*) INTO is_authorized 
    FROM Azienda 
    WHERE RagioneSociale = p_Azienda AND Responsabile = p_Responsabile;

    IF is_authorized = 0 THEN
        -- lancia eccezione personalizzata se non autorizzato
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'ERRORE: Utente non autorizzato per questa azienda';
    ELSE
        -- Se autorizzato, procediamo
        START TRANSACTION;
            -- A. Inserimento Bilancio
            INSERT INTO Bilancio (Azienda, Data) 
            VALUES (p_Azienda, p_Data);
            
            -- B. Aggiornamento Ridondanza [cite: 65, 66]
            UPDATE Azienda
            SET NBilanci = NBilanci + 1
            WHERE RagioneSociale = p_Azienda;
        COMMIT;
    END IF;
END $$

-- 2. Popolamento di un bilancio (Voci contabili)
CREATE PROCEDURE popolaBilancio(
    IN p_Voce VARCHAR(100), 
    IN p_DataBil DATE, 
    IN p_AziendaBil VARCHAR(255), 
    IN p_Responsabile VARCHAR(20), 
    IN p_Importo FLOAT
)
BEGIN
    DECLARE is_authorized INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    -- Controllo: l'utente gestisce l'azienda di cui sta modificando il bilancio?
    SELECT COUNT(*) INTO is_authorized 
    FROM Azienda 
    WHERE RagioneSociale = p_AziendaBil AND Responsabile = p_Responsabile;

    IF is_authorized = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERRORE: Utente non autorizzato';
    ELSE
        START TRANSACTION;
            INSERT INTO RigaBilancio (Voce, DataBil, AziendaBil, Importo) 
            VALUES (p_Voce, p_DataBil, p_AziendaBil, p_Importo);
        COMMIT;
    END IF;
END $$

-- 3. Inserimento indicatori ESG per singole voci
CREATE PROCEDURE creaCollegamentoESG(
    IN p_Voce VARCHAR(100), 
    IN p_DataBil DATE, 
    IN p_BilancioAz VARCHAR(255), 
    IN p_Indicatore VARCHAR(200), 
    IN p_DataRilevazione DATE, 
    IN p_ValoreNum FLOAT, 
    IN p_Fonte VARCHAR(100),
    IN p_Responsabile VARCHAR(20)
)
BEGIN
    DECLARE is_authorized INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    -- Controllo autorizzazione
    SELECT COUNT(*) INTO is_authorized 
    FROM Azienda 
    WHERE RagioneSociale = p_BilancioAz AND Responsabile = p_Responsabile;

    IF is_authorized = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ERRORE: Utente non autorizzato';
    ELSE
        START TRANSACTION;
            INSERT INTO Collegamento (Voce, DataBil, Bilancio, Indicatore, DataRilevazione, ValoreNum, Fonte)
            VALUES (p_Voce, p_DataBil, p_BilancioAz, p_Indicatore, p_DataRilevazione, p_ValoreNum, p_Fonte);
        COMMIT;
    END IF;
END $$

DELIMITER;