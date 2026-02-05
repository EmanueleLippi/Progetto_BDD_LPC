USE ESGBALANCE;

-- Disabilita controlli foreign key temporaneamente per evitare errori durante pulizia
SET FOREIGN_KEY_CHECKS = 0;

-- Pulizia tabelle (ordine inverso rispetto dipendenze o uso cascade)
DELETE FROM Nota;

DELETE FROM Revisione;

DELETE FROM Collegamento;

DELETE FROM RigaBilancio;

DELETE FROM Bilancio;

DELETE FROM Azienda;

DELETE FROM Appartiene;

DELETE FROM Competenza;

DELETE FROM Ambientale;

DELETE FROM Sociale;

DELETE FROM Indicatore;

DELETE FROM Voce;

DELETE FROM Administrator;

DELETE FROM Revisore;

DELETE FROM Responsabile;

DELETE FROM Email;

DELETE FROM Utente;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. UTENTI (Password hashate o in chiaro secondo logica app? Schema dice VARCHAR(20), quindi molto probabile in chiaro per ora o hash corto. Uso 'password' semplice)
INSERT INTO
    UTENTE (
        Cf,
        Username,
        PW,
        DataNascita,
        LuogoNascita
    )
VALUES (
        'ADM001',
        'admin',
        'admin',
        '1980-01-01',
        'Roma'
    ),
    (
        'REV001',
        'revisore',
        'revisore',
        '1985-05-15',
        'Milano'
    ),
    (
        'RSP001',
        'responsabile',
        'responsabile',
        '1990-08-20',
        'Torino'
    ),
    (
        'RSP002',
        'resp_eco',
        'resp_eco',
        '1992-11-10',
        'Bologna'
    );

-- 2. ENTI SPECIFICI
INSERT INTO ADMINISTRATOR (Utente) VALUES ('ADM001');

INSERT INTO
    REVISORE (
        Utente,
        NRevisioni,
        IndiceAffidabilita
    )
VALUES ('REV001', 5, 9.5);

INSERT INTO
    RESPONSABILE (Utente, Cv_Path)
VALUES (
        'RSP001',
        '/uploads/cv/rsp001.pdf'
    ),
    (
        'RSP002',
        '/uploads/cv/rsp002.pdf'
    );

-- EMAIL
INSERT INTO
    EMAIL (Utente, Indirizzo)
VALUES (
        'ADM001',
        'admin@esgbalance.com'
    ),
    ('REV001', 'rev@audit.com'),
    (
        'RSP001',
        'resp@greentech.com'
    );

-- 3. COMPETENZE
INSERT INTO
    Competenza (Nome)
VALUES ('Sostenibilità Ambientale'),
    ('Diritti Umani'),
    ('Corporate Governance');

INSERT INTO
    Appartiene (Competenza, Revisore, Livello)
VALUES (
        'Sostenibilità Ambientale',
        'REV001',
        4
    ),
    (
        'Corporate Governance',
        'REV001',
        3
    );

-- 4. AZIENDE
INSERT INTO
    Azienda (
        RagioneSociale,
        Nome,
        Settore,
        NBilanci,
        NDipendenti,
        Logo,
        PartitaIva,
        Responsabile
    )
VALUES (
        'GreenTech SpA',
        'GreenTech',
        'Tecnologia',
        2,
        150,
        'logo_gt.png',
        'IT123456789',
        'RSP001'
    ),
    (
        'EcoFood Ltd',
        'EcoFood',
        'Alimentare',
        1,
        50,
        'logo_ef.png',
        'IT987654321',
        'RSP002'
    );

-- 5. BILANCI
INSERT INTO
    Bilancio (Azienda, Data, Stato)
VALUES (
        'GreenTech SpA',
        '2023-12-31',
        'Approvato'
    ),
    (
        'GreenTech SpA',
        '2024-12-31',
        'In Revisione'
    ),
    (
        'EcoFood Ltd',
        '2024-12-31',
        'Bozza'
    );

-- 6. VOCI E INDICATORI (Base)
INSERT INTO
    Indicatore (
        Nome,
        Immagine,
        Rilevanza,
        Amministratore
    )
VALUES (
        'CO2 Equivalente',
        'co2.png',
        10,
        'ADM001'
    ),
    (
        'Metri Cubi Acqua',
        'water.png',
        8,
        'ADM001'
    ),
    (
        'Ore Formazione',
        'training.png',
        7,
        'ADM001'
    );

INSERT INTO
    Ambientale (Indicatore, CodNormRile)
VALUES (
        'CO2 Equivalente',
        'ISO 14064'
    ),
    (
        'Metri Cubi Acqua',
        'ISO 14046'
    );

INSERT INTO
    Sociale (
        Indicatore,
        Frequenza,
        AmbitoSociale
    )
VALUES (
        'Ore Formazione',
        6,
        'Welfare'
    );

INSERT INTO
    Voce (
        Nome,
        Descrizione,
        Amministratore
    )
VALUES (
        'Emissioni GHG',
        'Emissioni di gas serra dirette e indirette',
        'ADM001'
    ),
    (
        'Consumo Idrico',
        'Totale acqua consumata in metri cubi',
        'ADM001'
    ),
    (
        'Formazione Dipendenti',
        'Ore di formazione erogate per dipendente',
        'ADM001'
    );

-- 7. RIGHE BILANCIO
INSERT INTO
    RigaBilancio (
        Voce,
        DataBil,
        AziendaBil,
        Importo
    )
VALUES
    -- GreenTech 2023
    (
        'Emissioni GHG',
        '2023-12-31',
        'GreenTech SpA',
        500.5
    ),
    (
        'Consumo Idrico',
        '2023-12-31',
        'GreenTech SpA',
        12000
    ),
    -- GreenTech 2024
    (
        'Emissioni GHG',
        '2024-12-31',
        'GreenTech SpA',
        480.0
    ),
    (
        'Consumo Idrico',
        '2024-12-31',
        'GreenTech SpA',
        11500
    ),
    -- EcoFood 2024
    (
        'Formazione Dipendenti',
        '2024-12-31',
        'EcoFood Ltd',
        2500
    );

-- 8. COLLEGAMENTI
INSERT INTO
    Collegamento (
        Voce,
        DataBil,
        Bilancio,
        Indicatore,
        DataRilevazione,
        ValoreNum,
        Fonte
    )
VALUES (
        'Emissioni GHG',
        '2023-12-31',
        'GreenTech SpA',
        'CO2 Equivalente',
        '2023-12-31',
        500.5,
        'Report Interno'
    ),
    (
        'Consumo Idrico',
        '2023-12-31',
        'GreenTech SpA',
        'Metri Cubi Acqua',
        '2023-12-31',
        12000,
        'Bollette'
    ),
    (
        'Emissioni GHG',
        '2024-12-31',
        'GreenTech SpA',
        'CO2 Equivalente',
        '2024-12-31',
        480.0,
        'Report Interno'
    );

-- 9. REVISIONI
INSERT INTO
    Revisione (
        Revisore,
        DataBil,
        BilancioAz,
        DataGiudizio,
        Esito,
        Rilievi
    )
VALUES (
        'REV001',
        '2023-12-31',
        'GreenTech SpA',
        '2024-03-15',
        'Approvazione',
        'Bilancio conforme agli standard.'
    );

-- Messaggio di conferma
SELECT 'Database popolato con successo!' AS Status;