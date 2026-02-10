-- Vista 1: per mostrare il numero di aziende registrate in piattaforma
CREATE OR REPLACE VIEW NumeroAziende AS
SELECT COUNT(*) AS NumeroAziende
FROM Azienda;

-- Vista 2: per mostrare il numero di revisori ESG registrati in piattaforma
CREATE OR REPLACE VIEW NumeroRevisoriESG AS
SELECT COUNT(*) AS NumeroRevisoriESG
FROM Revisore;

-- Vista 3: per mostrare l'azienda con il valore più alto di affidabilità
CREATE OR REPLACE VIEW AziendaAffidabilitaMaggiore AS
SELECT
    A.RagioneSociale,
    A.Nome AS NomeAzienda,
    A.Settore,
    SUM(CASE WHEN B.ApprovatoSenzaRilievi = 1 THEN 1 ELSE 0 END) AS NumApprovazioni,
    COUNT(*) AS NumTotali,
    (
        SUM(CASE WHEN B.ApprovatoSenzaRilievi = 1 THEN 1 ELSE 0 END) * 100.0
        / NULLIF(COUNT(*), 0)
    ) AS Percentuale_Affidabilita
FROM Azienda A
    JOIN (
        SELECT
            R.BilancioAz,
            R.DataBil,
            CASE
                WHEN COUNT(*) > 0
                    AND SUM(CASE WHEN R.Esito = 'Approvazione' THEN 1 ELSE 0 END) = COUNT(*)
                THEN 1
                ELSE 0
            END AS ApprovatoSenzaRilievi
        FROM Revisione R
        WHERE R.Esito IS NOT NULL
        GROUP BY R.BilancioAz, R.DataBil
    ) AS B ON A.RagioneSociale = B.BilancioAz
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
                        SUM(CASE WHEN B2.ApprovatoSenzaRilievi = 1 THEN 1 ELSE 0 END) * 100.0
                        / NULLIF(COUNT(*), 0)
                    ) AS Percentuale
                FROM Azienda A2
                    JOIN (
                        SELECT
                            R2.BilancioAz,
                            R2.DataBil,
                            CASE
                                WHEN COUNT(*) > 0
                                    AND SUM(CASE WHEN R2.Esito = 'Approvazione' THEN 1 ELSE 0 END) = COUNT(*)
                                THEN 1
                                ELSE 0
                            END AS ApprovatoSenzaRilievi
                        FROM Revisione R2
                        WHERE R2.Esito IS NOT NULL
                        GROUP BY R2.BilancioAz, R2.DataBil
                    ) AS B2 ON A2.RagioneSociale = B2.BilancioAz
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
