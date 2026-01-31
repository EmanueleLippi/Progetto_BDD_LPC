DROP DATABASE IF EXISTS ESGBALANCE;

CREATE DATABASE if not exists ESGBALANCE DEFAULT CHARACTER SET = 'utf8mb4';

USE ESGBALANCE;

CREATE TABLE if NOT Exists UTENTE (
    Cf varchar(20) PRIMARY KEY,
    Username VARCHAR(20) UNIQUE NOT NULL,
    PW VARCHAR(20) NOT NULL,
    DataNascita DATE,
    LuogoNascita VARCHAR(20)
) engine = InnoDB;

CREATE TABLE if NOT Exists EMAIL (
    Utente varchar(20),
    Indirizzo VARCHAR(30),
    PRIMARY KEY (Utente, Indirizzo),
    Foreign Key (Utente) REFERENCES Utente (CF) on delete CASCADE
) engine = InnoDB;

CREATE TABLE if NOT Exists ADMINISTRATOR (
    Utente VARCHAR(20) PRIMARY KEY,
    Foreign Key (Utente) REFERENCES Utente (CF) on delete CASCADE
) engine = InnoDB;

CREATE TABLE if NOT Exists REVISORE (
    Utente VARCHAR(20) PRIMARY KEY,
    NRevisioni INT DEFAULT 0,
    IndiceAffidabilita FLOAT DEFAULT 0,
    Foreign Key (Utente) REFERENCES Utente (CF) on delete CASCADE
) engine = InnoDB;

CREATE TABLE if NOT Exists Responsabile (
    Utente VARCHAR(20) PRIMARY KEY,
    Cv_Path VARCHAR(255),
    Foreign Key (Utente) REFERENCES Utente (CF) on delete CASCADE
) engine = InnoDB;

CREATE TABLE if NOT Exists Competenza (Nome varchar(200) PRIMARY KEY) engine = InnoDB;

CREATE TABLE if NOT Exists Appartiene (
    Competenza VARCHAR(200),
    Revisore VARCHAR(20),
    Livello INT,
    CHECK (
        Livello >= 0
        AND Livello <= 5
    ),
    PRIMARY KEY (Competenza, Revisore),
    Foreign Key (Competenza) REFERENCES Competenza (Nome) ON DELETE CASCADE,
    Foreign Key (Revisore) REFERENCES Revisore (Utente) ON DELETE CASCADE
) engine = InnoDB;

CREATE TABLE if NOT Exists Azienda (
    RagioneSociale VARCHAR(255) PRIMARY KEY,
    Nome VARCHAR(200),
    Settore VARCHAR(200),
    NBilanci INT DEFAULT 0,
    NDipendenti INT,
    Logo VARCHAR(255),
    PartitaIva VARCHAR(11) UNIQUE,
    Responsabile VARCHAR(20),
    Foreign Key (Responsabile) REFERENCES Responsabile (Utente)
) engine = InnoDB;

CREATE TABLE if NOT Exists Bilancio (
    Azienda VARCHAR(255),
    Data DATE,
    Stato ENUM(
        'Bozza',
        'In Revisione',
        'Approvato',
        'Respinto'
    ) DEFAULT 'Bozza',
    PRIMARY KEY (Azienda, Data),
    Foreign Key (Azienda) REFERENCES Azienda (RagioneSociale) ON DELETE CASCADE
) engine = InnoDB;

CREATE TABLE if NOT Exists Voce (
    Nome VARCHAR(100) PRIMARY KEY,
    Descrizione VARCHAR(300),
    Amministratore VARCHAR(20),
    Foreign Key (Amministratore) REFERENCES Administrator (Utente)
) engine = InnoDB;

CREATE TABLE if NOT Exists RigaBilancio (
    Voce VARCHAR(100),
    DataBil DATE,
    AziendaBil VARCHAR(255),
    Importo FLOAT NOT NULL,
    PRIMARY KEY (Voce, DataBil, AziendaBil),
    Foreign Key (Voce) REFERENCES Voce (Nome),
    Foreign Key (AziendaBil, DataBil) REFERENCES Bilancio (Azienda, Data) ON DELETE CASCADE
) engine = InnoDB;

CREATE TABLE if NOT Exists Indicatore (
    Nome VARCHAR(200) PRIMARY KEY,
    Immagine VARCHAR(200),
    Rilevanza INT,
    check (
        rilevanza >= 0
        AND rilevanza <= 10
    ),
    Amministratore VARCHAR(20),
    Foreign Key (Amministratore) REFERENCES Administrator (Utente)
) engine = InnoDB;

CREATE TABLE if NOT Exists Ambientale (
    Indicatore VARCHAR(200) PRIMARY KEY,
    CodNormRile VARCHAR(100),
    Foreign Key (Indicatore) REFERENCES Indicatore (Nome) ON DELETE CASCADE
) engine = InnoDB;

CREATE TABLE if NOT Exists Sociale (
    Indicatore VARCHAR(200) PRIMARY KEY,
    Frequenza INT,
    AmbitoSociale VARCHAR(100),
    Foreign Key (Indicatore) REFERENCES Indicatore (Nome) ON DELETE CASCADE
) engine = InnoDB;

CREATE TABLE if NOT Exists Collegamento (
    Voce VARCHAR(100),
    DataBil DATE,
    Bilancio VARCHAR(255),
    Indicatore VARCHAR(200),
    DataRilevazione DATE,
    ValoreNum FLOAT,
    Fonte VARCHAR(100),
    PRIMARY KEY (
        Voce,
        DataBil,
        Bilancio,
        Indicatore,
        DataRilevazione
    ),
    Foreign Key (Voce, DataBil, Bilancio) REFERENCES RigaBilancio (Voce, DataBil, AziendaBil) ON DELETE CASCADE,
    Foreign Key (Indicatore) REFERENCES Indicatore (Nome)
) engine = InnoDB;

CREATE Table if NOT Exists Revisione (
    Revisore VARCHAR(20),
    DataBil DATE,
    BilancioAz VARCHAR(255),
    DataGiudizio DATE,
    Esito ENUM(
        'Approvazione',
        'Approvazione con rilievi',
        'Respingimento'
    ),
    Rilievi VARCHAR(300),
    PRIMARY KEY (Revisore, DataBil, BilancioAz),
    Foreign Key (Revisore) REFERENCES Revisore (Utente),
    Foreign Key (BilancioAz, DataBil) REFERENCES Bilancio (Azienda, Data) ON DELETE CASCADE
) engine = InnoDB;

CREATE TABLE if not Exists Nota (
    Revisore VARCHAR(20),
    VoceRiga VARCHAR(100),
    RigaData DATE,
    RigaAzienda VARCHAR(255),
    TestoNota TEXT,
    DataNota DATE,
    PRIMARY KEY (
        Revisore,
        VoceRiga,
        RigaData,
        RigaAzienda
    ),
    FOREIGN KEY (Revisore) REFERENCES Revisore (Utente),
    FOREIGN KEY (
        VoceRiga,
        RigaData,
        RigaAzienda
    ) REFERENCES RigaBilancio (Voce, DataBil, AziendaBil) ON DELETE CASCADE
) engine = InnoDB;