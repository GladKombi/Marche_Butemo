-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 22, 2026 at 03:58 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `marche_butembo`
--

-- --------------------------------------------------------

--
-- Table structure for table `agriculteurs`
--

CREATE TABLE `agriculteurs` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `raison_sociale` varchar(255) DEFAULT NULL,
  `numero_contribuable` varchar(50) DEFAULT NULL,
  `numero_agrement` varchar(50) DEFAULT NULL,
  `photo_profil` varchar(255) DEFAULT NULL,
  `zone_geographique` varchar(100) DEFAULT NULL COMMENT 'Zone de Butembo',
  `superficie_terrain` decimal(10,2) DEFAULT NULL COMMENT 'Superficie en hectares',
  `certifications` text DEFAULT NULL COMMENT 'Certifications agricoles',
  `supprime` int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `agriculteurs`
--

INSERT INTO `agriculteurs` (`id`, `utilisateur_id`, `raison_sociale`, `numero_contribuable`, `numero_agrement`, `photo_profil`, `zone_geographique`, `superficie_terrain`, `certifications`, `supprime`) VALUES
(1, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(2, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(3, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(4, 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `supprime` int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `nom`, `description`, `parent_id`, `supprime`) VALUES
(1, 'Légume', 'Mboga za majani', NULL, 0),
(2, 'Patisserie', 'mikati', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `chats`
--

CREATE TABLE `chats` (
  `id` int(11) NOT NULL,
  `agriculteur_id` int(11) NOT NULL,
  `acheteur_id` int(11) NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `supprime` int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chats`
--

INSERT INTO `chats` (`id`, `agriculteur_id`, `acheteur_id`, `date_creation`, `supprime`) VALUES
(1, 6, 7, '2026-07-20 16:46:33', 0);

-- --------------------------------------------------------

--
-- Table structure for table `commandes`
--

CREATE TABLE `commandes` (
  `id` int(11) NOT NULL,
  `acheteur_id` int(11) NOT NULL,
  `numero_commande` varchar(50) NOT NULL,
  `date_commande` datetime DEFAULT current_timestamp(),
  `montant_total` decimal(15,2) NOT NULL,
  `date_annulation` datetime DEFAULT NULL,
  `raison_annulation` text DEFAULT NULL,
  `supprime` int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `commandes`
--

INSERT INTO `commandes` (`id`, `acheteur_id`, `numero_commande`, `date_commande`, `montant_total`, `date_annulation`, `raison_annulation`, `supprime`) VALUES
(2, 8, 'CMD-20260717-83078A', '2026-07-17 02:05:44', 2000.00, NULL, NULL, 0),
(3, 8, 'CMD-20260717-164436', '2026-07-17 02:06:09', 2000.00, NULL, NULL, 0),
(4, 7, 'CMD-20260717-073553-7AB2', '2026-07-17 02:35:53', 3500.00, NULL, NULL, 0),
(5, 7, 'CMD-20260721-215325-E20A', '2026-07-21 16:53:25', 6500.00, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `details_livraison`
--

CREATE TABLE `details_livraison` (
  `id` int(11) NOT NULL,
  `id_commande` int(11) NOT NULL,
  `adresse_livraison` text NOT NULL,
  `instructions_specifiques` text DEFAULT NULL,
  `supprime` int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `details_livraison`
--

INSERT INTO `details_livraison` (`id`, `id_commande`, `adresse_livraison`, `instructions_specifiques`, `supprime`) VALUES
(2, 2, 'ssssssssssss', 'sq', 0),
(3, 3, 'ssssssssssss', 'hbnbg', 0),
(4, 4, 'Butembo, Quartier Katwa', NULL, 0),
(5, 5, 'Butembo, Quartier Katwa', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `ligne_commandes`
--

CREATE TABLE `ligne_commandes` (
  `id` int(11) NOT NULL,
  `commande_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `quantite` decimal(15,2) NOT NULL,
  `prix_unitaire` decimal(15,2) NOT NULL,
  `supprime` int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ligne_commandes`
--

INSERT INTO `ligne_commandes` (`id`, `commande_id`, `produit_id`, `quantite`, `prix_unitaire`, `supprime`) VALUES
(1, 2, 1, 1.00, 1500.00, 0),
(2, 3, 1, 1.00, 1500.00, 0),
(3, 4, 1, 2.00, 1500.00, 0),
(4, 5, 1, 4.00, 1500.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `livraisons`
--

CREATE TABLE `livraisons` (
  `id` int(11) NOT NULL,
  `commande_id` int(11) NOT NULL,
  `livreur_id` int(11) NOT NULL,
  `date_assignation` datetime DEFAULT current_timestamp(),
  `date_depart` datetime DEFAULT NULL,
  `date_livraison` datetime DEFAULT NULL,
  `statut_livraison` enum('en_cours','terminee','annulee') DEFAULT 'en_cours',
  `code_suivi` varchar(50) DEFAULT NULL,
  `photo_livraison` varchar(255) DEFAULT NULL,
  `frais_reel` decimal(10,2) DEFAULT NULL,
  `supprime` int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `livraisons`
--

INSERT INTO `livraisons` (`id`, `commande_id`, `livreur_id`, `date_assignation`, `date_depart`, `date_livraison`, `statut_livraison`, `code_suivi`, `photo_livraison`, `frais_reel`, `supprime`) VALUES
(2, 4, 11, '2026-07-17 03:04:04', '2026-07-17 03:13:05', '2026-07-17 03:15:56', 'terminee', 'LIV-20260717-080404-8804', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `chat_id` int(11) NOT NULL,
  `expediteur_id` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `date_envoi` timestamp NOT NULL DEFAULT current_timestamp(),
  `est_lu` tinyint(1) DEFAULT 0,
  `supprime` int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `chat_id`, `expediteur_id`, `contenu`, `date_envoi`, `est_lu`, `supprime`) VALUES
(1, 1, 7, 'Bonjour, je souhaite discuter de votre produit « sombe » : http://localhost/Marche_Butemo/index.php?produit=1#produits', '2026-07-20 16:46:33', 1, 0),
(2, 1, 7, 'Bonjour, je souhaite discuter de votre produit « sombe » : http://localhost/Marche_Butemo/index.php?produit=1#produits', '2026-07-20 16:51:36', 1, 0),
(3, 1, 7, 'c\'est combien', '2026-07-20 16:52:11', 1, 0),
(4, 1, 6, '200 fc zilete nikupeyo', '2026-07-20 16:54:23', 1, 0),
(5, 1, 7, 'Bonjour, je souhaite discuter de votre produit « sombe » : http://localhost/Marche_Butemo/index.php?produit=1#produits', '2026-07-21 19:51:26', 1, 0),
(6, 1, 7, 'nipunguziye', '2026-07-21 19:51:58', 1, 0),
(7, 1, 7, 'Bonjour, je souhaite discuter de votre produit « sombe » : http://localhost/Marche_Butemo/index.php?produit=1#produits', '2026-07-21 19:52:17', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `paiements`
--

CREATE TABLE `paiements` (
  `id` int(11) NOT NULL,
  `commande_id` int(11) NOT NULL,
  `reference_paiement` varchar(100) NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  `mode_paiement` enum('especes','carte','mobile_money','virement','autre') NOT NULL,
  `date_paiement` datetime DEFAULT NULL,
  `date_remboursement` datetime DEFAULT NULL,
  `supprime` int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `paiements`
--

INSERT INTO `paiements` (`id`, `commande_id`, `reference_paiement`, `montant`, `mode_paiement`, `date_paiement`, `date_remboursement`, `supprime`) VALUES
(2, 2, 'PAY-CMD-20260717-83078A', 2000.00, 'especes', NULL, NULL, 0),
(3, 3, 'PAY-CMD-20260717-164436', 2000.00, 'especes', NULL, NULL, 0),
(4, 4, 'PAY-CMD-20260717-073553-7AB2', 3500.00, 'especes', NULL, NULL, 0),
(5, 5, 'PAY-CMD-20260721-215325-E20A', 6500.00, 'especes', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `produits`
--

CREATE TABLE `produits` (
  `id` int(11) NOT NULL,
  `agriculteur_id` int(11) NOT NULL,
  `categorie_id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `prix_unitaire` decimal(15,2) NOT NULL,
  `unite_mesure` enum('kg','g','tonne','piece','douzaine','litre','sac','autre') NOT NULL,
  `quantite_stock` decimal(15,2) NOT NULL DEFAULT 0.00,
  `images` varchar(255) DEFAULT NULL,
  `est_bio` tinyint(1) DEFAULT 0,
  `origine` varchar(255) DEFAULT NULL,
  `est_disponible` tinyint(1) DEFAULT 1,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `supprime` int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `produits`
--

INSERT INTO `produits` (`id`, `agriculteur_id`, `categorie_id`, `nom`, `description`, `prix_unitaire`, `unite_mesure`, `quantite_stock`, `images`, `est_bio`, `origine`, `est_disponible`, `date_creation`, `supprime`) VALUES
(1, 4, 1, 'sombe', 'dieme', 1500.00, 'piece', 7.00, '[\"assets/uploads/produits/a6d800668006f00f08c89837b5e72bfe.jpg\"]', 1, 'isale', 1, '2026-07-17 04:23:31', 0),
(2, 4, 2, 'pain francais', 'mukati ya chuvi', 2000.00, 'piece', 20.00, '[\"assets/uploads/produits/503d5c654815a4bea4b808959c61b8c9.webp\"]', 0, 'paris', 1, '2026-07-21 19:59:00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `type_utilisateur` enum('agriculteur','acheteur','livreur','admin') NOT NULL,
  `adresse` text DEFAULT NULL,
  `est_verifie` tinyint(1) DEFAULT 0,
  `statut` enum('actif','suspendu','bloque') DEFAULT 'actif',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `supprime` int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `email`, `telephone`, `mot_de_passe`, `type_utilisateur`, `adresse`, `est_verifie`, `statut`, `date_creation`, `supprime`) VALUES
(1, 'Bisimwas', 'Jean', 'jean@admin-bbomarcher.com', '0990000001', '$2y$12$k9p03cp0.S51kPPKTLv9u./S2aPGHoNe4Zte8wRh0A/zS9P1RYEge', 'admin', NULL, 1, 'actif', '2026-07-16 20:12:52', 0),
(2, 'Kambale', 'Patrick', 'patrick@admin-bbomarcher.com', '0990000002', '$2y$12$k9p03cp0.S51kPPKTLv9u./S2aPGHoNe4Zte8wRh0A/zS9P1RYEge', 'admin', 'Butembo, Quartier Vungi', 1, 'actif', '2026-07-16 20:12:52', 0),
(3, 'Kasereka', 'Michel', 'michel@agriculteur-bbomarcher.com', '0990000003', '$2y$12$k9p03cp0.S51kPPKTLv9u./S2aPGHoNe4Zte8wRh0A/zS9P1RYEge', 'agriculteur', 'Butembo, Quartier Kyaghala', 1, 'actif', '2026-07-16 20:12:52', 0),
(4, 'Mumbere', 'David', 'david@agriculteur-bbomarcher.com', '0990000004', '$2y$12$k9p03cp0.S51kPPKTLv9u./S2aPGHoNe4Zte8wRh0A/zS9P1RYEge', 'agriculteur', 'Butembo, Quartier Kambali', 1, 'actif', '2026-07-16 20:12:52', 0),
(5, 'Kavira', 'Chantal', 'chantal@agriculteur-bbomarcher.com', '0990000005', '$2y$12$k9p03cp0.S51kPPKTLv9u./S2aPGHoNe4Zte8wRh0A/zS9P1RYEge', 'agriculteur', 'Butembo, Quartier Mukuna', 0, 'actif', '2026-07-16 20:12:52', 0),
(6, 'Masika', 'Jeannette', 'jeannette@agriculteur-bbomarcher.com', '0990000006', '$2y$12$k9p03cp0.S51kPPKTLv9u./S2aPGHoNe4Zte8wRh0A/zS9P1RYEge', 'agriculteur', 'Butembo, Quartier Vulamba', 1, 'actif', '2026-07-16 20:12:52', 0),
(7, 'Muhindo', 'Emmanuel', 'emmanuel@acheteur-bbomarcher.com', '0990000007', '$2y$12$k9p03cp0.S51kPPKTLv9u./S2aPGHoNe4Zte8wRh0A/zS9P1RYEge', 'acheteur', 'Butembo, Quartier Katwa', 1, 'actif', '2026-07-16 20:12:52', 0),
(8, 'Kakule', 'Joseph', 'joseph@acheteur-bbomarcher.com', '0990000008', '$2y$12$k9p03cp0.S51kPPKTLv9u./S2aPGHoNe4Zte8wRh0A/zS9P1RYEge', 'acheteur', 'Butembo, Quartier Mususa', 0, 'actif', '2026-07-16 20:12:52', 0),
(9, 'Vahwere', 'Sarah', 'sarah@acheteur-bbomarcher.com', '0990000009', '$2y$12$k9p03cp0.S51kPPKTLv9u./S2aPGHoNe4Zte8wRh0A/zS9P1RYEge', 'acheteur', 'Butembo, Quartier Kimemi', 1, 'suspendu', '2026-07-16 20:12:52', 0),
(10, 'Paluku', 'Daniel', 'daniel@livreur-bbomarcher.com', '0990000010', '$2y$12$k9p03cp0.S51kPPKTLv9u./S2aPGHoNe4Zte8wRh0A/zS9P1RYEge', 'livreur', 'Butembo, Quartier Wayene', 1, 'actif', '2026-07-16 20:12:52', 0),
(11, 'Kambasu', 'Eric', 'eric@livreur-bbomarcher.com', '0990000011', '$2y$12$k9p03cp0.S51kPPKTLv9u./S2aPGHoNe4Zte8wRh0A/zS9P1RYEge', 'livreur', 'Butembo, Quartier Rughenda', 1, 'actif', '2026-07-16 20:12:52', 0),
(12, 'Mbusa', 'Jonathan', 'jonathan@livreur-bbomarcher.com', '0990000012', '$2y$12$k9p03cp0.S51kPPKTLv9u./S2aPGHoNe4Zte8wRh0A/zS9P1RYEge', 'livreur', 'Butembo, Quartier Malende', 0, 'bloque', '2026-07-16 20:12:52', 0),
(13, 'Glad', 'kombi', 'glad@acheteur-bbomarcher.com', '0997019883', '$2y$10$ioXVyc.1SCK9mzVq7LX7nePVLkuYjn9CtjpfERYazwh1t30krJn4a', 'acheteur', 'Avenue de l\'Independance, Butembo', 0, 'actif', '2026-07-17 12:49:35', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `agriculteurs`
--
ALTER TABLE `agriculteurs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chats`
--
ALTER TABLE `chats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_commande` (`numero_commande`);

--
-- Indexes for table `details_livraison`
--
ALTER TABLE `details_livraison`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ligne_commandes`
--
ALTER TABLE `ligne_commandes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `livraisons`
--
ALTER TABLE `livraisons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code_suivi` (`code_suivi`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `paiements`
--
ALTER TABLE `paiements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_paiement` (`reference_paiement`);

--
-- Indexes for table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `agriculteurs`
--
ALTER TABLE `agriculteurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `chats`
--
ALTER TABLE `chats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `details_livraison`
--
ALTER TABLE `details_livraison`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `ligne_commandes`
--
ALTER TABLE `ligne_commandes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `livraisons`
--
ALTER TABLE `livraisons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `paiements`
--
ALTER TABLE `paiements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `produits`
--
ALTER TABLE `produits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
