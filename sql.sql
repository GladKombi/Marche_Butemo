-- Création de la base de données
CREATE DATABASE IF NOT EXISTS marche_butembo 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE marche_butembo;

-- --------------------------------------------------------
-- Table: utilisateurs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    type_utilisateur ENUM('agriculteur', 'acheteur', 'livreur', 'admin') NOT NULL,
    adresse TEXT,
    est_verifie BOOLEAN DEFAULT FALSE,
    statut ENUM('actif', 'suspendu', 'bloque') DEFAULT 'actif',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    supprime int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: Profils agriculteurs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS agriculteurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT NOT NULL,
    raison_sociale VARCHAR(255),
    numero_contribuable VARCHAR(50),
    numero_agrement VARCHAR(50),
    photo_profil VARCHAR(255),
    zone_geographique VARCHAR(100) COMMENT 'Zone de Butembo',
    superficie_terrain DECIMAL(10,2) COMMENT 'Superficie en hectares',
    certifications TEXT COMMENT 'Certifications agricoles',
    supprime int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------
-- Table: categories
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    parent_id INT DEFAULT NULL,
    supprime int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: produits
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS produits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    agriculteur_id INT NOT NULL,
    categorie_id INT NOT NULL,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    prix_unitaire DECIMAL(15,2) NOT NULL,
    unite_mesure ENUM('kg', 'g', 'tonne', 'piece', 'douzaine', 'litre', 'sac', 'autre') NOT NULL,
    quantite_stock DECIMAL(15,2) NOT NULL DEFAULT 0,
    images VARCHAR(255) ,
    est_bio BOOLEAN DEFAULT FALSE,
    origine VARCHAR(255) ,
    est_disponible BOOLEAN DEFAULT TRUE,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    supprime int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: commandes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS commandes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    acheteur_id INT NOT NULL,
    numero_commande VARCHAR(50) UNIQUE NOT NULL,
    date_commande DATETIME DEFAULT CURRENT_TIMESTAMP,
    montant_total DECIMAL(15,2) NOT NULL,    
    date_annulation DATETIME,
    raison_annulation TEXT,
    supprime int(1) DEFAULT 0 -- soft delete flag
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: details_livraison
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS details_livraison (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_commande INT NOT NULL,
    adresse_livraison TEXT NOT NULL,
    instructions_specifiques TEXT,
    supprime int(1) DEFAULT 0 -- soft delete flag
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: ligne_commandes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS ligne_commandes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite DECIMAL(15,2) NOT NULL,
    prix_unitaire DECIMAL(15,2) NOT NULL,
    supprime int(1) DEFAULT 0 -- soft delete flag
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: livraisons
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS livraisons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commande_id INT NOT NULL,
    livreur_id INT NOT NULL,
    date_assignation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_depart DATETIME,
    date_livraison DATETIME,
    statut_livraison ENUM('en_cours', 'terminee', 'annulee') DEFAULT 'en_cours',
    code_suivi VARCHAR(50) UNIQUE,
    photo_livraison VARCHAR(255),
    frais_reel DECIMAL(10,2),
    supprime int(1) DEFAULT 0 -- soft delete flag
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- --------------------------------------------------------
-- Table: paiements
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS paiements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commande_id INT NOT NULL,
    reference_paiement VARCHAR(100) UNIQUE NOT NULL,
    montant DECIMAL(15,2) NOT NULL,
    mode_paiement ENUM('especes', 'carte', 'mobile_money', 'virement', 'autre') NOT NULL,
    date_paiement DATETIME,
    date_remboursement DATETIME,
    supprime int(1) DEFAULT 0 -- soft delete flag
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: chats
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS chats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    agriculteur_id INT NOT NULL,
    acheteur_id INT NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    supprime int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: messages
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    chat_id INT NOT NULL,
    expediteur_id INT NOT NULL,
    contenu TEXT NOT NULL,
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    est_lu BOOLEAN DEFAULT FALSE,
    supprime int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

