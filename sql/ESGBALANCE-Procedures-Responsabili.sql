DELIMITER $$

-- 1. Creazione Bilancio d'esercizio
CREATE PROCEDURE creaBilancio(
    IN p_Azienda VARCHAR(255), 
    IN p_Data DATE
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
        -- A. Inseriamo il bilancio (Stato default: 'Bozza')
        INSERT INTO Bilancio (Azienda, Data) 
        VALUES (p_Azienda, p_Data);
        
        -- B. AGGIORNAMENTO RIDONDANZA
        -- Incrementiamo il contatore dei bilanci per quell'azienda
        UPDATE Azienda
        SET NBilanci = NBilanci + 1
        WHERE RagioneSociale = p_Azienda;
        
    COMMIT;
END $$

-- 2. Popolamento di un bilancio (Voci contabili)
CREATE PROCEDURE popolaBilancio(
    IN p_Voce VARCHAR(100), 
    IN p_DataBil DATE, 
    IN p_AziendaBil VARCHAR(255), 
    IN p_Importo FLOAT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
        INSERT INTO RigaBilancio (Voce, DataBil, AziendaBil, Importo) 
        VALUES (p_Voce, p_DataBil, p_AziendaBil, p_Importo);
    COMMIT;
END $$

-- 3. Inserimento indicatori ESG per singole voci [cite: 29, 30]
CREATE PROCEDURE creaCollegamentoESG(
    IN p_Voce VARCHAR(100), 
    IN p_DataBil DATE, 
    IN p_BilancioAz VARCHAR(255), 
    IN p_Indicatore VARCHAR(200), 
    IN p_DataRilevazione DATE, 
    IN p_ValoreNum FLOAT, 
    IN p_Fonte VARCHAR(100)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
        INSERT INTO Collegamento (Voce, DataBil, Bilancio, Indicatore, DataRilevazione, ValoreNum, Fonte)
        VALUES (p_Voce, p_DataBil, p_BilancioAz, p_Indicatore, p_DataRilevazione, p_ValoreNum, p_Fonte);
    COMMIT;
END $$

DELIMITER;