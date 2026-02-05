-- Vista 1: per mostrare il numero di aziende registrate in piattaforma
CREATE OR REPLACE VIEW NumeroAziende AS
SELECT COUNT(*) AS NumeroAziende
FROM Azienda;

-- Vista 2: per mostrare il numero di revisori ESG registrati in piattaforma
CREATE OR REPLACE VIEW NumeroRevisoriESG AS
SELECT COUNT(*) AS NumeroRevisoriESG
FROM REVISORE;

-- Vista 3: per mostrare l'azienda con il valore più alto di affidabilità
CREATE OR REPLACE VIEW AziendaAffidabilitaMaggiore AS
SELECT
    A.RagioneSociale,
    A.Nome AS NomeAzienda,
    A.Settore,
    SUM(
        CASE
            WHEN R.Esito = 'Approvazione' THEN 1
            ELSE 0
        END
    ) AS NumApprovazioni,
    COUNT(*) AS NumTotali,
    (
        SUM(
            CASE
                WHEN R.Esito = 'Approvazione' THEN 1
                ELSE 0
            END
        ) * 100.0 / COUNT(*)
    ) AS Percentuale_Affidabilita
FROM Azienda A
    JOIN Revisione R ON A.RagioneSociale = R.BilancioAz
WHERE
    R.Esito IS NOT NULL
GROUP BY
    A.RagioneSociale,
    A.Nome,
    A.Settore
HAVING
    Percentuale_Affidabilita = (
        -- Subquery per trovare la percentuale massima globale
        SELECT MAX(Percentuale)
        FROM (
                SELECT (
                        SUM(
                            CASE
                                WHEN R2.Esito = 'Approvazione' THEN 1
                                ELSE 0
                            END
                        ) * 100.0 / COUNT(*)
                    ) AS Percentuale
                FROM Azienda A2
                    JOIN Revisione R2 ON A2.RagioneSociale = R2.BilancioAz
                WHERE
                    R2.Esito IS NOT NULL
                GROUP BY
                    A2.RagioneSociale
            ) AS TabellaMassimi
    );

-- Vista 4: per la classifica dei bilanci aziendali, ordinati in base al numero totale di indicatori ESG connessi alle singole voci contabili
CREATE OR REPLACE VIEW Vista_ClassificaESG AS
SELECT
    A.Nome AS NomeAzienda,
    A.Settore,
    B.Data AS DataBilancio,
    COUNT(C.Indicatore) AS Numero_Indicatori_ESG
FROM
    Bilancio B
    JOIN Azienda A ON B.Azienda = A.RagioneSociale
    -- Uniamo il bilancio ai suoi collegamenti ESG
    LEFT JOIN Collegamento C ON B.Azienda = C.Bilancio
    AND B.Data = C.DataBil
GROUP BY
    A.RagioneSociale,
    A.Nome,
    A.Settore,
    B.Data
ORDER BY Numero_Indicatori_ESG DESC;