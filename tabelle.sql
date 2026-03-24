
-- ==========================================
-- 1. TABELLE ANAGRAFICHE 
-- ==========================================

CREATE TABLE CATEGORIA (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE LUOGO (
    id_luogo INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    tipo ENUM('lavorazione', 'conservazione', 'vendita') NOT NULL
);

CREATE TABLE CLIENTE (
    id_cliente     INT AUTO_INCREMENT PRIMARY KEY,
    nominativo     VARCHAR(150),
    nickname       VARCHAR(50) NOT NULL UNIQUE,       -- usato per il login
    dati_contatto  VARCHAR(255),                      -- email o telefono (facoltativo)
    password_hash  VARCHAR(255),                      -- salvataggio password criptata
    totp_secret    VARCHAR(255),                      -- allargato per supportare le chiavi 2FA
    ruolo          ENUM('admin', 'cliente') NOT NULL DEFAULT 'cliente',
    is_occasionale BOOLEAN DEFAULT FALSE
);

-- ==========================================
-- 2. TABELLA PRODOTTO
-- ==========================================

CREATE TABLE PRODOTTO (
    id_prodotto  INT AUTO_INCREMENT PRIMARY KEY,
    nome         VARCHAR(150) NOT NULL,
    tipologia    ENUM('Fresco', 'Lavorato', 'Riserva') NOT NULL,
    unita_misura VARCHAR(20) NOT NULL,
    id_categoria INT NOT NULL,
    FOREIGN KEY (id_categoria) REFERENCES CATEGORIA(id_categoria)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ==========================================
-- 3. TABELLE TRANSAZIONALI E STORICHE
-- ==========================================

CREATE TABLE STORICO_PREZZI (
    id_storico           INT AUTO_INCREMENT PRIMARY KEY,
    id_prodotto          INT NOT NULL,
    prezzo               DECIMAL(10,2) NOT NULL,
    data_inizio_validita DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_fine_validita   DATETIME DEFAULT NULL,   -- NULL = prezzo attuale
    FOREIGN KEY (id_prodotto) REFERENCES PRODOTTO(id_prodotto)
        ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE PRODUZIONE_GIACENZA (
    id_produzione        INT AUTO_INCREMENT PRIMARY KEY,
    id_prodotto          INT NOT NULL,
    id_luogo             INT NOT NULL,
    data_lavorazione     DATE NOT NULL,
    data_confezionamento DATE DEFAULT NULL,
    quantita_iniziale    DECIMAL(10,3) NOT NULL,
    giacenza_attuale     DECIMAL(10,3) NOT NULL CHECK (giacenza_attuale >= 0),
    FOREIGN KEY (id_prodotto) REFERENCES PRODOTTO(id_prodotto) ON UPDATE CASCADE,
    FOREIGN KEY (id_luogo)    REFERENCES LUOGO(id_luogo)       ON UPDATE CASCADE
);

CREATE TABLE VENDITA (
    id_vendita       INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente       INT NOT NULL,
    id_luogo         INT NOT NULL,
    data_acquisto    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    totale_calcolato DECIMAL(10,2) NOT NULL,
    totale_pagato    DECIMAL(10,2) NOT NULL,
    note             TEXT,
    FOREIGN KEY (id_cliente) REFERENCES CLIENTE(id_cliente) ON UPDATE CASCADE,
    FOREIGN KEY (id_luogo)   REFERENCES LUOGO(id_luogo)     ON UPDATE CASCADE
);

CREATE TABLE DETTAGLIO_VENDITA (
    id_dettaglio     INT AUTO_INCREMENT PRIMARY KEY,
    id_vendita       INT NOT NULL,
    id_prodotto      INT NOT NULL,
    quantita         DECIMAL(10,3) NOT NULL,
    prezzo_applicato DECIMAL(10,2) NOT NULL,
    is_omaggio       BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (id_vendita)  REFERENCES VENDITA(id_vendita)   ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (id_prodotto) REFERENCES PRODOTTO(id_prodotto) ON UPDATE CASCADE
);



-- ==========================================
-- 4. DATI INIZIALI E POPOLAMENTO 
-- ==========================================

INSERT INTO LUOGO (nome, tipo) VALUES ('Dispensa',        'conservazione');
INSERT INTO LUOGO (nome, tipo) VALUES ('Sede principale', 'vendita');

-- Popolamento Categorie
INSERT INTO CATEGORIA (nome) VALUES 
('Frutta Fresca'), 
('Miele'), 
('Olio Extravergine'), 
('Marmellate e Confetture'), 
('Erbe Aromatiche');

-- Popolamento Prodotti di prova
INSERT INTO PRODOTTO (nome, tipologia, unita_misura, id_categoria) VALUES 
('Mele Rosse', 'Fresco', 'kg', 1),                                
('Miele di Millefiori (Vasetto 500g)', 'Lavorato', 'pezzo', 2),   
('Miele di Acacia (Vasetto 200g)', 'Lavorato', 'pezzo', 2),            
('Olio EVO (Latta 5L)', 'Lavorato', 'pezzo', 3),                  
('Vino (Bottiglia 750ml)', 'Riserva', 'pezzo', 3),                 
('Marmellata di Fichi (Vasetto)', 'Lavorato', 'pezzo', 4);        

-- Popolamento Storico Prezzi iniziale
INSERT INTO STORICO_PREZZI (id_prodotto, prezzo) VALUES 
(1, 2.50),   
(2, 6.50),   
(3, 11.00),  
(4, 45.00),  
(5, 8.50),   
(6, 4.00);

INSERT INTO PRODUZIONE_GIACENZA (id_prodotto, id_luogo, data_lavorazione, quantita_iniziale, giacenza_attuale) VALUES 
(1, 1, '2025-09-01', 100.000, 100.000),  
(2, 1, '2025-08-15', 50.000, 50.000),    
(3, 1, '2025-07-20', 20.000, 20.000),    
(4, 1, '2025-08-01', 30.000, 30.000),    
(5, 1, '2025-06-10', 40.000, 40.000),    
(6, 1, '2025-09-05', 60.000, 60.000);

INSERT INTO CLIENTE (nominativo, nickname, dati_contatto, password_hash, totp_secret, ruolo) VALUES 
('Amministratore', 'admin', 'admin@gmail.com', '$2y$10$L.Y5Xt9.fS7Qyazfjbiv6.oT76DjQskFAnKNYoni6/smASmGcqg..','5FNTRGFIOFMOA5HLGSEWG7EOCT3VKP552OFB3SLYO6ZUYH4LA4VGLKZFG325OSAMKESV3VKLRUQMWXLM3RNOXJMSUZ2WOLVAIA7KFRI','admin'),
('Antonio Linciano', 'linci00', 'linci@example.it', '$2y$10$UNYn7oZ3HcHme5VnEFdY8eSg6qx0jY.S2qZvAEiha8iCMcisLOKrW','3HYZW6U4UOUUAL5755RJLRRJURP6NQEULKVHD4WLDTI2O4K52GNWVTV6MTSHJSQPGQ5G7IJ3XEF37EFY5XRPAL56QJ64NLBZWFTGV2A=','cliente');
