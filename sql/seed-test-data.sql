USE ESGBALANCE;

-- =====================================================
-- Seed dati di test per viste e flussi logici
-- Eseguire dopo create-db-template.sql (e, opzionalmente, dopo procedure/trigger)
-- Script idempotente: pulisce e reinserisce i dati di test.
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM Nota;
DELETE FROM Revisione;
DELETE FROM Collegamento;
DELETE FROM Ambientale;
DELETE FROM Sociale;
DELETE FROM Indicatore;
DELETE FROM RigaBilancio;
DELETE FROM Bilancio;
DELETE FROM Azienda;
DELETE FROM Appartiene;
DELETE FROM Competenza;
DELETE FROM Voce;
DELETE FROM Administrator;
DELETE FROM Revisore;
DELETE FROM Responsabile;
DELETE FROM Email;
DELETE FROM Utente;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 1) Utenti e ruoli
-- =====================================================
INSERT INTO Utente (Cf, Username, PW, DataNascita, LuogoNascita) VALUES
('ADM0000000000000001', 'admin1', 'admin123', '1985-03-14', 'Roma'),
('REV0000000000000001', 'revisore1', 'rev123', '1990-06-10', 'Milano'),
('REV0000000000000002', 'revisore2', 'rev123', '1988-11-21', 'Torino'),
('REV0000000000000003', 'revisore3', 'rev123', '1992-01-30', 'Bologna'),
('REV0000000000000004', 'revisore4', 'rev123', '1995-09-12', 'Firenze'),
('RSP0000000000000001', 'resp1', 'resp123', '1987-02-05', 'Napoli'),
('RSP0000000000000002', 'resp2', 'resp123', '1983-07-19', 'Genova'),
('RSP0000000000000003', 'resp3', 'resp123', '1991-12-02', 'Bari');

INSERT INTO Email (Utente, Indirizzo) VALUES
('ADM0000000000000001', 'admin1@esg.it'),
('REV0000000000000001', 'rev1@esg.it'),
('REV0000000000000002', 'rev2@esg.it'),
('REV0000000000000003', 'rev3@esg.it'),
('REV0000000000000004', 'rev4@esg.it'),
('RSP0000000000000001', 'resp1@esg.it'),
('RSP0000000000000002', 'resp2@esg.it'),
('RSP0000000000000003', 'resp3@esg.it');

INSERT INTO Administrator (Utente) VALUES
('ADM0000000000000001');

INSERT INTO Revisore (Utente, NRevisioni, IndiceAffidabilita) VALUES
('REV0000000000000001', 3, 0.92),
('REV0000000000000002', 3, 0.85),
('REV0000000000000003', 2, 0.78),
('REV0000000000000004', 1, 0.88);

INSERT INTO Responsabile (Utente, Cv_Path) VALUES
('RSP0000000000000001', 'uploads/cv/resp1.pdf'),
('RSP0000000000000002', 'uploads/cv/resp2.pdf'),
('RSP0000000000000003', 'uploads/cv/resp3.pdf');

-- =====================================================
-- 2) Competenze revisori
-- =====================================================
INSERT INTO Competenza (Nome) VALUES
('Emissioni Scope 1-2-3'),
('Parita di genere'),
('Sicurezza sul lavoro'),
('Governance e compliance'),
('Consumo idrico');

INSERT INTO Appartiene (Competenza, Revisore, Livello) VALUES
('Emissioni Scope 1-2-3', 'REV0000000000000001', 5),
('Consumo idrico', 'REV0000000000000001', 4),
('Parita di genere', 'REV0000000000000002', 5),
('Sicurezza sul lavoro', 'REV0000000000000002', 4),
('Governance e compliance', 'REV0000000000000003', 5),
('Emissioni Scope 1-2-3', 'REV0000000000000004', 3);

-- =====================================================
-- 3) Aziende
-- =====================================================
INSERT INTO Azienda (RagioneSociale, Nome, Settore, NBilanci, NDipendenti, Logo, PartitaIva, Responsabile) VALUES
('EcoPower S.p.A.', 'EcoPower', 'Energia', 2, 430, 'uploads/aziende/ecopower.png', '12345678901', 'RSP0000000000000001'),
('MediCare S.r.l.', 'MediCare', 'Sanita', 2, 180, 'uploads/aziende/medicare.png', '12345678902', 'RSP0000000000000002'),
('LogiTrans S.p.A.', 'LogiTrans', 'Logistica', 1, 320, 'uploads/aziende/logitrans.png', '12345678903', 'RSP0000000000000003');

-- =====================================================
-- 4) Bilanci: copertura stati (Bozza / In Revisione / Approvato / Respinto)
-- =====================================================
INSERT INTO Bilancio (Azienda, Data, Stato) VALUES
('EcoPower S.p.A.', '2023-12-31', 'Approvato'),
('EcoPower S.p.A.', '2024-12-31', 'Respinto'),
('MediCare S.r.l.', '2023-12-31', 'Approvato'),
('MediCare S.r.l.', '2024-12-31', 'In Revisione'),
('LogiTrans S.p.A.', '2024-12-31', 'Bozza');

-- =====================================================
-- 5) Template voci contabili
-- =====================================================
INSERT INTO Voce (Nome, Descrizione, Amministratore) VALUES
('Ricavi netti', 'Ricavi annuali netti', 'ADM0000000000000001'),
('Costi energetici', 'Costi energia elettrica e termica', 'ADM0000000000000001'),
('Spese personale', 'Spese legate al personale', 'ADM0000000000000001'),
('Investimenti ESG', 'Investimenti in iniziative ESG', 'ADM0000000000000001'),
('Sanzioni compliance', 'Sanzioni o contenziosi normativi', 'ADM0000000000000001');

-- =====================================================
-- 6) Righe bilancio
-- =====================================================
INSERT INTO RigaBilancio (Voce, DataBil, AziendaBil, Importo) VALUES
('Ricavi netti',      '2023-12-31', 'EcoPower S.p.A.', 12500000),
('Costi energetici',  '2023-12-31', 'EcoPower S.p.A.', 3200000),
('Spese personale',   '2023-12-31', 'EcoPower S.p.A.', 2800000),
('Investimenti ESG',  '2023-12-31', 'EcoPower S.p.A.', 650000),

('Ricavi netti',      '2024-12-31', 'EcoPower S.p.A.', 13200000),
('Costi energetici',  '2024-12-31', 'EcoPower S.p.A.', 4100000),
('Sanzioni compliance','2024-12-31','EcoPower S.p.A.', 120000),
('Investimenti ESG',  '2024-12-31', 'EcoPower S.p.A.', 480000),

('Ricavi netti',      '2023-12-31', 'MediCare S.r.l.', 7800000),
('Spese personale',   '2023-12-31', 'MediCare S.r.l.', 3400000),
('Investimenti ESG',  '2023-12-31', 'MediCare S.r.l.', 520000),

('Ricavi netti',      '2024-12-31', 'MediCare S.r.l.', 8100000),
('Spese personale',   '2024-12-31', 'MediCare S.r.l.', 3550000),
('Investimenti ESG',  '2024-12-31', 'MediCare S.r.l.', 610000),

('Ricavi netti',      '2024-12-31', 'LogiTrans S.p.A.', 9900000),
('Costi energetici',  '2024-12-31', 'LogiTrans S.p.A.', 2700000);

-- =====================================================
-- 7) Indicatori ESG (generico + ambientale + sociale)
-- =====================================================
INSERT INTO Indicatore (Nome, Immagine, Rilevanza, Amministratore) VALUES
('Emissioni CO2 Scope 1', 'co2_scope1.png', 10, 'ADM0000000000000001'),
('Consumo idrico', 'acqua.png', 8, 'ADM0000000000000001'),
('Tasso infortuni', 'infortuni.png', 9, 'ADM0000000000000001'),
('Gender pay gap', 'gender_gap.png', 8, 'ADM0000000000000001'),
('Ore formazione ESG', 'training_esg.png', 7, 'ADM0000000000000001');

INSERT INTO Ambientale (Indicatore, CodNormRile) VALUES
('Emissioni CO2 Scope 1', 'ISO-14064'),
('Consumo idrico', 'ISO-46001');

INSERT INTO Sociale (Indicatore, Frequenza, AmbitoSociale) VALUES
('Tasso infortuni', 12, 'Sicurezza lavoro'),
('Gender pay gap', 12, 'Parita retributiva'),
('Ore formazione ESG', 4, 'Formazione dipendenti');

-- =====================================================
-- 8) Collegamenti ESG: utile per Vista_ClassificaESG
-- =====================================================
INSERT INTO Collegamento (Voce, DataBil, Bilancio, Indicatore, DataRilevazione, ValoreNum, Fonte) VALUES
('Costi energetici', '2023-12-31', 'EcoPower S.p.A.', 'Emissioni CO2 Scope 1', '2023-12-15', 1840, 'Audit interno'),
('Investimenti ESG', '2023-12-31', 'EcoPower S.p.A.', 'Ore formazione ESG', '2023-11-30', 640, 'HR dashboard'),
('Spese personale', '2023-12-31', 'EcoPower S.p.A.', 'Gender pay gap', '2023-12-10', 7.8, 'Payroll report'),
('Costi energetici', '2024-12-31', 'EcoPower S.p.A.', 'Emissioni CO2 Scope 1', '2024-12-01', 2015, 'Audit interno'),
('Sanzioni compliance', '2024-12-31', 'EcoPower S.p.A.', 'Tasso infortuni', '2024-11-20', 3.2, 'HSE report'),

('Spese personale', '2023-12-31', 'MediCare S.r.l.', 'Tasso infortuni', '2023-12-05', 1.4, 'HSE report'),
('Spese personale', '2023-12-31', 'MediCare S.r.l.', 'Gender pay gap', '2023-12-06', 4.1, 'Payroll report'),
('Investimenti ESG', '2023-12-31', 'MediCare S.r.l.', 'Ore formazione ESG', '2023-10-20', 720, 'LMS export'),
('Investimenti ESG', '2023-12-31', 'MediCare S.r.l.', 'Consumo idrico', '2023-12-12', 3200, 'Facility report'),

('Investimenti ESG', '2024-12-31', 'MediCare S.r.l.', 'Ore formazione ESG', '2024-09-30', 810, 'LMS export');

-- =====================================================
-- 9) Revisioni e note
-- - Bilancio approvato: tutti 'Approvazione'
-- - Bilancio respinto: almeno un 'Respingimento'
-- - Bilancio in revisione: assegnazioni con esito NULL
-- =====================================================
INSERT INTO Revisione (Revisore, DataBil, BilancioAz, DataGiudizio, Esito, Rilievi) VALUES
('REV0000000000000001', '2023-12-31', 'EcoPower S.p.A.', '2024-01-20', 'Approvazione', NULL),
('REV0000000000000002', '2023-12-31', 'EcoPower S.p.A.', '2024-01-21', 'Approvazione', NULL),

('REV0000000000000001', '2024-12-31', 'EcoPower S.p.A.', '2025-01-18', 'Approvazione con rilievi', 'Migliorare tracciabilita fonti ESG'),
('REV0000000000000003', '2024-12-31', 'EcoPower S.p.A.', '2025-01-19', 'Respingimento', 'Incoerenza tra KPI e note di supporto'),

('REV0000000000000002', '2023-12-31', 'MediCare S.r.l.', '2024-01-25', 'Approvazione', NULL),
('REV0000000000000004', '2023-12-31', 'MediCare S.r.l.', '2024-01-26', 'Approvazione', NULL),

('REV0000000000000001', '2024-12-31', 'MediCare S.r.l.', NULL, NULL, NULL),
('REV0000000000000003', '2024-12-31', 'MediCare S.r.l.', NULL, NULL, NULL);

INSERT INTO Nota (Revisore, VoceRiga, RigaData, RigaAzienda, TestoNota, DataNota) VALUES
('REV0000000000000001', 'Costi energetici', '2024-12-31', 'EcoPower S.p.A.', 'Verificare riconciliazione con fatture Q4.', '2025-01-15'),
('REV0000000000000003', 'Sanzioni compliance', '2024-12-31', 'EcoPower S.p.A.', 'Documentazione incompleta sulle azioni correttive.', '2025-01-16'),
('REV0000000000000001', 'Investimenti ESG', '2024-12-31', 'MediCare S.r.l.', 'Attesa evidenza su programma formativo.', '2025-01-10');

-- =====================================================
-- 10) Allineamento stato bilanci ai dati revisione (utile se i trigger non sono caricati)
-- =====================================================
UPDATE Bilancio
SET Stato = 'Approvato'
WHERE Azienda = 'EcoPower S.p.A.' AND Data = '2023-12-31';

UPDATE Bilancio
SET Stato = 'Respinto'
WHERE Azienda = 'EcoPower S.p.A.' AND Data = '2024-12-31';

UPDATE Bilancio
SET Stato = 'Approvato'
WHERE Azienda = 'MediCare S.r.l.' AND Data = '2023-12-31';

UPDATE Bilancio
SET Stato = 'In Revisione'
WHERE Azienda = 'MediCare S.r.l.' AND Data = '2024-12-31';

UPDATE Bilancio
SET Stato = 'Bozza'
WHERE Azienda = 'LogiTrans S.p.A.' AND Data = '2024-12-31';

-- =====================================================
-- Query rapide di controllo (facoltative)
-- =====================================================
-- SELECT * FROM NumeroAziende;
-- SELECT * FROM NumeroRevisoriESG;
-- SELECT * FROM AziendaAffidabilitaMaggiore;
-- SELECT * FROM Vista_ClassificaESG;
