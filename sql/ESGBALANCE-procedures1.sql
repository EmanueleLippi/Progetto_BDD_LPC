DELIMITER $$

-- 1. REGISTRAZIONE AMMINISTRATORE
CREATE PROCEDURE RegistraAdmin(
    IN p_CF VARCHAR(20),
    IN p_UserName VARCHAR(20),
    IN p_Password VARCHAR(20),
    IN p_Email VARCHAR(30),
    IN p_DataNascita DATE,
    IN p_LuogoNascita VARCHAR(20)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
        INSERT INTO UTENTE (Cf, Username, PW, DataNascita, LuogoNascita)
        VALUES (p_CF, p_UserName, p_Password, p_DataNascita, p_LuogoNascita);
        
        INSERT INTO EMAIL (Utente, Indirizzo)
        VALUES (p_CF, p_Email);
        
        INSERT INTO ADMINISTRATOR (Utente)
        VALUES (p_CF);
    COMMIT;
END $$

-- 2. REGISTRAZIONE REVISORE
CREATE PROCEDURE RegistraRevisore(
    IN p_CF VARCHAR(20),
    IN p_UserName VARCHAR(20),
    IN p_Password VARCHAR(20),
    IN p_Email VARCHAR(30),
    IN p_DataNascita DATE,
    IN p_LuogoNascita VARCHAR(20),
    IN p_IndAff FLOAT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
        INSERT INTO UTENTE (Cf, Username, PW, DataNascita, LuogoNascita)
        VALUES (p_CF, p_UserName, p_Password, p_DataNascita, p_LuogoNascita);
        
        INSERT INTO EMAIL (Utente, Indirizzo)
        VALUES (p_CF, p_Email);
        
        INSERT INTO REVISORE (Utente, NRevisioni, IndiceAffidabilita)
        VALUES (p_CF, 0, p_IndAff);
    COMMIT;
END $$

-- 3. REGISTRAZIONE RESPONSABILE
CREATE PROCEDURE RegistraResponsabile(
    IN p_CF VARCHAR(20),
    IN p_UserName VARCHAR(20),
    IN p_Password VARCHAR(20),
    IN p_Email VARCHAR(30),
    IN p_DataNascita DATE,
    IN p_LuogoNascita VARCHAR(20),
    IN p_Cv_Path VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
        INSERT INTO UTENTE (Cf, Username, PW, DataNascita, LuogoNascita)
        VALUES (p_CF, p_UserName, p_Password, p_DataNascita, p_LuogoNascita);
        
        INSERT INTO EMAIL (Utente, Indirizzo)
        VALUES (p_CF, p_Email);
        
        INSERT INTO Responsabile (Utente, Cv_Path)
        VALUES (p_CF, p_Cv_Path);
    COMMIT;
END $$

-- 4. REGISTRAZIONE AZIENDA
CREATE PROCEDURE RegistraAzienda(
    IN p_RagioneSociale VARCHAR(255),
    IN p_Nome VARCHAR(200),
    IN p_Settore VARCHAR(200),
    IN p_NDipendenti INT,
    IN p_Logo VARCHAR(255),
    IN p_PIva VARCHAR(11),
    IN p_ResponsabileCF VARCHAR(20)
)
BEGIN
    INSERT INTO Azienda (RagioneSociale, Nome, Settore, NBilanci, NDipendenti, Logo, PartitaIva, Responsabile)
    VALUES (p_RagioneSociale, p_Nome, p_Settore, 0, p_NDipendenti, p_Logo, p_PIva, p_ResponsabileCF);
END $$

CREATE PROCEDURE Autenticazione(
    IN p_cf VARCHAR(20), 
    IN p_PW VARCHAR(20)
)
BEGIN
    SELECT U.*,
        CASE 
            WHEN A.Utente IS NOT NULL THEN 'Admin'
            WHEN R.Utente IS NOT NULL THEN 'Revisore'
            WHEN Resp.Utente IS NOT NULL THEN 'Responsabile'
            ELSE 'Utente Semplice'
        END as Ruolo
    FROM UTENTE U
    LEFT JOIN ADMINISTRATOR A ON U.Cf = A.Utente
    LEFT JOIN REVISORE R ON U.Cf = R.Utente
    LEFT JOIN Responsabile Resp ON U.Cf = Resp.Utente
    WHERE U.Cf = p_cf AND U.PW = p_PW;
END $$

DELIMITER;