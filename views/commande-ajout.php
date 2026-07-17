<?php

/**
 * views/commande-ajout.php
 * Ajout d'une nouvelle commande
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('Location: ../connexion.php');
    exit;
}

if ($_SESSION['user_type'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/select/CommandeSelect.php';
require_once __DIR__ . '/../models/select/UtilisateurSelect.php';
require_once __DIR__ . '/../models/select/ProduitSelect.php';

$user_id = $_SESSION['user_id'];
$user_nom = $_SESSION['user_nom'];
$user_prenom = $_SESSION['user_prenom'];
$user_email = $_SESSION['user_email'];
$user_type = $_SESSION['user_type'];
$user_photo = $_SESSION['user_photo'] ?? null;
$userInitials = strtoupper(substr($user_nom, 0, 1) . substr($user_prenom, 0, 1));

$role_label = 'Administrateur';
$role_color = 'danger';

// Récupération des données
try {
    $pdo = getDBConnection();

    // Récupérer les acheteurs
    $acheteurs = fetchAll("SELECT id, nom, prenom, email, telephone FROM utilisateurs WHERE type_utilisateur = 'acheteur' AND supprime = 0 AND statut = 'actif' ORDER BY nom, prenom");

    // Récupérer les produits disponibles
    $produits = fetchAll("SELECT p.*, c.nom as categorie_nom FROM produits p JOIN categories c ON p.categorie_id = c.id AND c.supprime = 0 WHERE p.supprime = 0 AND p.est_disponible = 1 AND p.quantite_stock > 0 ORDER BY p.nom");
} catch (PDOException $e) {
    error_log("Erreur chargement données: " . $e->getMessage());
    $acheteurs = [];
    $produits = [];
}

// Traitement du formulaire
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acheteur_id = (int)($_POST['acheteur_id'] ?? 0);
    $adresse_livraison = trim($_POST['adresse_livraison'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    $mode_paiement = $_POST['mode_paiement'] ?? 'mobile_money';
    $produits_data = json_decode($_POST['produits_data'] ?? '[]', true);
    $modes_paiement_autorises = ['especes', 'carte', 'mobile_money', 'virement', 'autre'];

    // Validation
    if ($acheteur_id <= 0) {
        $error = 'Veuillez sélectionner un acheteur.';
    } elseif (empty($adresse_livraison)) {
        $error = 'Veuillez saisir une adresse de livraison.';
    } elseif (empty($produits_data)) {
        $error = 'Veuillez ajouter au moins un produit.';
    } elseif (!in_array($mode_paiement, $modes_paiement_autorises, true)) {
        $error = 'Le mode de paiement sélectionné est invalide.';
    } else {
        try {
            $pdo->beginTransaction();

            // Générer un numéro de commande unique
            $numero_commande = 'CMD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            // Calculer le montant total
            $montant_total = 0;
            $frais_livraison = 500;

            foreach ($produits_data as $item) {
                $montant_total += $item['prix'] * $item['quantite'];
            }
            $montant_total += $frais_livraison;

            // La table commandes ne contient que les informations principales.
            $sql = "INSERT INTO commandes (
                acheteur_id, numero_commande, date_commande, montant_total, supprime
            ) VALUES (
                :acheteur_id, :numero_commande, NOW(), :montant_total, 0
            )";

            $params = [
                ':acheteur_id' => $acheteur_id,
                ':numero_commande' => $numero_commande,
                ':montant_total' => $montant_total
            ];

            $commande_id = executeInsert($sql, $params);

            if (!$commande_id) {
                throw new Exception('Erreur lors de l\'insertion de la commande.');
            }

            // Insérer les lignes de commande et mettre à jour les stocks
            foreach ($produits_data as $item) {
                // Vérifier le stock
                $sql_check = "SELECT quantite_stock FROM produits WHERE id = :id AND supprime = 0";
                $stock = fetchOne($sql_check, [':id' => $item['id']]);

                if (!$stock) {
                    throw new Exception('Produit #' . $item['id'] . ' introuvable.');
                }

                if ($stock->quantite_stock < $item['quantite']) {
                    throw new Exception('Stock insuffisant pour le produit "' . $item['nom'] . '". Disponible: ' . $stock->quantite_stock);
                }

                // Insérer la ligne
                $sql_ligne = "INSERT INTO ligne_commandes (commande_id, produit_id, quantite, prix_unitaire) 
                              VALUES (:commande_id, :produit_id, :quantite, :prix_unitaire)";
                $result_ligne = executeQuery($sql_ligne, [
                    ':commande_id' => $commande_id,
                    ':produit_id' => $item['id'],
                    ':quantite' => $item['quantite'],
                    ':prix_unitaire' => $item['prix']
                ]);

                if (!$result_ligne) {
                    throw new Exception('Erreur lors de l\'insertion de la ligne de commande pour le produit "' . $item['nom'] . '".');
                }

                // Mettre à jour le stock
                $sql_update = "UPDATE produits SET quantite_stock = quantite_stock - :quantite WHERE id = :id";
                $result_update = executeQuery($sql_update, [
                    ':id' => $item['id'],
                    ':quantite' => $item['quantite']
                ]);

                if (!$result_update) {
                    throw new Exception('Erreur lors de la mise à jour du stock pour le produit "' . $item['nom'] . '".');
                }
            }

            // L'adresse et les instructions appartiennent à details_livraison.
            $result_details = executeQuery(
                "INSERT INTO details_livraison (id_commande, adresse_livraison, instructions_specifiques, supprime)
                 VALUES (:commande_id, :adresse, :instructions, 0)",
                [':commande_id' => $commande_id, ':adresse' => $adresse_livraison, ':instructions' => $instructions ?: null]
            );
            if (!$result_details) {
                throw new Exception('Erreur lors de l\'enregistrement des informations de livraison.');
            }

            // Conserver le mode de paiement choisi dans la table paiements.
            $result_paiement = executeQuery(
                "INSERT INTO paiements (commande_id, reference_paiement, montant, mode_paiement, supprime)
                 VALUES (:commande_id, :reference, :montant, :mode, 0)",
                [
                    ':commande_id' => $commande_id,
                    ':reference' => 'PAY-' . $numero_commande,
                    ':montant' => $montant_total,
                    ':mode' => $mode_paiement
                ]
            );
            if (!$result_paiement) {
                throw new Exception('Erreur lors de l\'enregistrement du mode de paiement.');
            }

            $pdo->commit();

            $_SESSION['toast'] = [
                'message' => 'Commande #' . $numero_commande . ' créée avec succès !',
                'type' => 'success'
            ];

            header('Location: commandes.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Erreur création commande: " . $e->getMessage());
            $error = 'Erreur: ' . $e->getMessage();
        }
    }
}

$toast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle commande - Butembo Marché</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --color-bg: #fafbfc;
            --color-surface: #ffffff;
            --color-primary: #2d6a4f;
            --color-primary-hover: #1b4332;
            --color-primary-soft: #e8f5ee;
            --color-text: #0f172a;
            --color-text-secondary: #64748b;
            --color-text-muted: #94a3b8;
            --color-border: #e2e8f0;
            --color-success: #10b981;
            --color-warning: #f59e0b;
            --color-danger: #ef4444;
            --color-info: #3b82f6;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 20px 40px -12px rgb(0 0 0 / 0.15);
            --radius-sm: 0.5rem;
            --radius-md: 0.75rem;
            --radius-xl: 1.25rem;
            --font-sans: 'Inter', sans-serif;
            --font-display: 'Outfit', sans-serif;
        }

        .dark {
            --color-bg: #0b1120;
            --color-surface: #1e293b;
            --color-text: #f1f5f9;
            --color-text-secondary: #94a3b8;
            --color-text-muted: #64748b;
            --color-border: #334155;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-sans);
            background: var(--color-bg);
            color: var(--color-text);
            transition: background 0.3s, color 0.3s;
        }

        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 16rem;
            background: var(--color-surface);
            border-right: 1px solid var(--color-border);
            z-index: 40;
            transition: transform 0.2s ease;
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2.5rem;
        }

        .sidebar-logo .logo-icon {
            width: 2.5rem;
            height: 2.5rem;
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-hover));
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 4px 10px rgba(45, 106, 79, 0.3);
        }

        .sidebar-logo h1 {
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 700;
        }

        .sidebar-logo span {
            font-size: 0.65rem;
            font-weight: 500;
            color: var(--color-text-muted);
            display: block;
        }

        .sidebar-nav {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .nav-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--color-text-muted);
            padding: 0.5rem 0.75rem;
            margin-top: 1rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem 0.75rem;
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--color-text-secondary);
            text-decoration: none;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .nav-link:hover {
            background: var(--color-primary-soft);
            color: var(--color-text);
        }

        .nav-link.active {
            background: var(--color-primary);
            color: #fff;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(45, 106, 79, 0.25);
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding-top: 1rem;
            border-top: 1px solid var(--color-border);
            margin-top: auto;
        }

        .user-avatar {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: var(--radius-md);
            background: var(--color-primary-soft);
            color: var(--color-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: var(--radius-md);
            object-fit: cover;
        }

        .user-info p {
            font-size: 0.8rem;
            font-weight: 600;
            line-height: 1.3;
        }

        .user-info span {
            font-size: 0.7rem;
            color: var(--color-text-muted);
        }

        .logout-btn {
            margin-left: auto;
            color: var(--color-text-muted);
            background: none;
            border: none;
            cursor: pointer;
            transition: color 0.2s;
            display: flex;
            text-decoration: none;
        }

        .logout-btn:hover {
            color: var(--color-danger);
        }

        .role-badge-sidebar {
            font-size: 0.6rem;
            padding: 0.15rem 0.5rem;
            border-radius: 999px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .role-badge-sidebar.admin {
            background: #dc3545;
            color: #fff;
        }

        /* ===== MAIN ===== */
        .main-content {
            flex: 1;
            margin-left: 16rem;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .top-bar {
            position: sticky;
            top: 0;
            z-index: 30;
            background: rgba(250, 251, 252, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--color-border);
            padding: 1rem 2rem;
        }

        .dark .top-bar {
            background: rgba(11, 17, 32, 0.8);
        }

        .page-content {
            flex: 1;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .page-title {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.025em;
        }

        .breadcrumb-item {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--color-text-muted);
            text-decoration: none;
            transition: color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .breadcrumb-item:hover {
            color: var(--color-primary);
        }

        /* ===== FORM ===== */
        .form-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-xl);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
        }

        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--color-text-secondary);
            margin-bottom: 0.4rem;
        }

        .form-label .required {
            color: var(--color-danger);
            margin-left: 0.2rem;
        }

        .form-control,
        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            font-family: var(--font-sans);
            font-size: 0.875rem;
            color: var(--color-text);
            outline: none;
            transition: all 0.2s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(45, 106, 79, 0.15);
        }

        .form-control.is-invalid {
            border-color: var(--color-danger);
        }

        .form-control.is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.25rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            font-family: var(--font-sans);
        }

        .btn-primary {
            background: var(--color-primary);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--color-primary-hover);
            transform: translateY(-1px);
            color: #fff;
        }

        .btn-secondary {
            background: var(--color-bg);
            color: var(--color-text);
            border: 1px solid var(--color-border);
        }

        .btn-secondary:hover {
            background: var(--color-border);
        }

        .btn-success {
            background: var(--color-success);
            color: #fff;
        }

        .btn-success:hover {
            background: #059669;
            color: #fff;
        }

        .btn-danger {
            background: var(--color-danger);
            color: #fff;
        }

        .btn-danger:hover {
            background: #dc2626;
            color: #fff;
        }

        /* ===== PRODUCT LIST ===== */
        .product-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            margin-bottom: 0.5rem;
            transition: all 0.2s ease;
        }

        .product-item:hover {
            border-color: #a8d5ba;
            background: var(--color-primary-soft);
        }

        .product-item .product-info {
            flex: 1;
        }

        .product-item .product-name {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .product-item .product-detail {
            font-size: 0.8rem;
            color: var(--color-text-muted);
        }

        .product-item .product-quantity {
            width: 80px;
            margin: 0 1rem;
        }

        .product-item .product-quantity input {
            text-align: center;
            padding: 0.3rem 0.5rem;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            width: 100%;
            font-size: 0.9rem;
            background: var(--color-bg);
            color: var(--color-text);
        }

        .product-item .product-price {
            font-weight: 700;
            color: var(--color-primary);
            min-width: 80px;
            text-align: right;
        }

        .btn-remove {
            background: none;
            border: none;
            color: var(--color-text-muted);
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            transition: color 0.2s;
        }

        .btn-remove:hover {
            color: var(--color-danger);
        }

        .empty-products {
            text-align: center;
            padding: 2rem;
            color: var(--color-text-muted);
        }

        .empty-products i {
            font-size: 2.5rem;
            display: block;
            margin-bottom: 0.5rem;
        }

        .toast {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 300;
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            padding: 0.9rem 1.25rem;
            box-shadow: var(--shadow-lg);
            font-size: 0.85rem;
            font-weight: 500;
            display: flex !important;
            align-items: center;
            gap: 0.65rem;
            animation: slideInRight 0.35s ease-out;
            max-width: 400px;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(120%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .toast-success {
            border-left: 3px solid var(--color-success);
        }

        .toast-error {
            border-left: 3px solid var(--color-danger);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--color-text);
            cursor: pointer;
        }

        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
                box-shadow: var(--shadow-lg);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: flex;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .page-content {
                padding: 1rem;
            }
        }

        @media (max-width: 575.98px) {
            .form-card {
                padding: 1rem;
            }

            .product-item {
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .product-item .product-quantity {
                width: 60px;
                margin: 0 0.5rem;
            }

            .product-item .product-price {
                min-width: 60px;
            }
        }
    </style>
</head>

<body>
    <div id="sidebar-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:35;" onclick="toggleSidebar()"></div>

    <!-- ===== SIDEBAR ===== -->
    <aside id="sidebar" class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon"><i class="bi bi-basket2-fill"></i></div>
            <div>
                <h1>Butembo Marché</h1>
                <span>Gestion Commerciale</span>
            </div>
        </div>
        <?php require __DIR__ . '/partials/sidebar-nav.php'; ?>
        <div class="sidebar-user">
            <div class="user-avatar">
                <?php if ($user_photo): ?>
                    <img src="<?= htmlspecialchars($user_photo) ?>" alt="Avatar">
                <?php else: ?>
                    <?= htmlspecialchars($userInitials) ?>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <p><?= htmlspecialchars($user_prenom) ?> <?= htmlspecialchars($user_nom) ?></p>
                <span><?= htmlspecialchars($user_email) ?></span>
                <span class="role-badge-sidebar <?= $user_type ?>"><?= $role_label ?></span>
            </div>
            <a href="../models/logout.php" class="logout-btn" title="Déconnexion" aria-label="Se déconnecter">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main-content">
        <header class="top-bar">
            <div style="display:flex; align-items:center; justify-content:space-between; width:100%; flex-wrap:wrap; gap:0.5rem;">
                <div style="display:flex; align-items:center; gap:1rem;">
                    <button class="mobile-menu-btn" onclick="toggleSidebar()">
                        <i class="bi bi-list" style="font-size:1.5rem;"></i>
                    </button>
                    <h2 class="page-title">Nouvelle commande</h2>
                </div>
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <button onclick="toggleDarkMode()" class="btn btn-secondary btn-sm" style="border-radius:var(--radius-md);">
                        <i class="bi bi-moon"></i>
                    </button>
                    <span class="badge bg-danger text-white ms-2"><?= $role_label ?></span>
                </div>
            </div>
        </header>

        <div class="page-content">
            <!-- Breadcrumbs -->
            <nav>
                <a href="commandes.php" class="breadcrumb-item">
                    <i class="bi bi-arrow-left"></i> Retour aux commandes
                </a>
            </nav>

            <!-- Messages -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Formulaire -->
            <form method="POST" action="" class="form-card" id="commandeForm">
                <!-- Informations client -->
                <h5 class="fw-bold mb-3"><i class="bi bi-person me-2"></i>Informations client</h5>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="acheteur_id">Acheteur <span class="required">*</span></label>
                        <select class="form-select <?= (!empty($error) && empty($_POST['acheteur_id'])) ? 'is-invalid' : '' ?>" id="acheteur_id" name="acheteur_id" required>
                            <option value="">Sélectionner un acheteur</option>
                            <?php foreach ($acheteurs as $a): ?>
                                <option value="<?= $a->id ?>" <?= (isset($_POST['acheteur_id']) && $_POST['acheteur_id'] == $a->id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($a->prenom . ' ' . $a->nom) ?> - <?= htmlspecialchars($a->email) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="mode_paiement">Mode de paiement <span class="required">*</span></label>
                        <select class="form-select" id="mode_paiement" name="mode_paiement" required>
                            <option value="mobile_money" <?= (isset($_POST['mode_paiement']) && $_POST['mode_paiement'] == 'mobile_money') ? 'selected' : '' ?>>Mobile Money</option>
                            <option value="especes" <?= (isset($_POST['mode_paiement']) && $_POST['mode_paiement'] == 'especes') ? 'selected' : '' ?>>Espèces</option>
                            <option value="carte" <?= (isset($_POST['mode_paiement']) && $_POST['mode_paiement'] == 'carte') ? 'selected' : '' ?>>Carte bancaire</option>
                            <option value="virement" <?= (isset($_POST['mode_paiement']) && $_POST['mode_paiement'] == 'virement') ? 'selected' : '' ?>>Virement</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="adresse_livraison">Adresse de livraison <span class="required">*</span></label>
                    <input type="text" class="form-control <?= (!empty($error) && empty($_POST['adresse_livraison'])) ? 'is-invalid' : '' ?>" id="adresse_livraison" name="adresse_livraison" placeholder="Quartier, avenue, numéro..." value="<?= htmlspecialchars($_POST['adresse_livraison'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="instructions">Instructions spécifiques</label>
                    <textarea class="form-control" id="instructions" name="instructions" rows="2" placeholder="Instructions pour le livreur..."><?= htmlspecialchars($_POST['instructions'] ?? '') ?></textarea>
                </div>

                <hr class="my-4">

                <!-- Produits -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-box-seam me-2"></i>Produits</h5>
                    <span class="badge bg-primary" id="productCount">0 produit(s)</span>
                </div>

                <div class="mb-3">
                    <div class="input-group">
                        <select class="form-select" id="productSelect">
                            <option value="">Ajouter un produit...</option>
                            <?php foreach ($produits as $p): ?>
                                <option value="<?= $p->id ?>" data-prix="<?= $p->prix_unitaire ?>" data-stock="<?= $p->quantite_stock ?>" data-unite="<?= $p->unite_mesure ?>">
                                    <?= htmlspecialchars($p->nom) ?> (<?= number_format($p->prix_unitaire, 0, ',', ' ') ?> FC - Stock: <?= $p->quantite_stock ?> <?= $p->unite_mesure ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" class="form-control" id="productQuantity" placeholder="Qté" min="1" value="1" style="max-width:100px;">
                        <button class="btn btn-primary" type="button" onclick="addProduct()">
                            <i class="bi bi-plus-circle"></i> Ajouter
                        </button>
                    </div>
                </div>

                <div id="productsList" class="mb-3">
                    <div class="empty-products" id="emptyProducts">
                        <i class="bi bi-box"></i>
                        <p>Aucun produit ajouté</p>
                    </div>
                </div>

                <!-- Résumé -->
                <div class="border-top pt-3 mt-3">
                    <div class="d-flex justify-content-end align-items-center gap-4 flex-wrap">
                        <div>
                            <span class="text-muted">Total produits :</span>
                            <span class="fw-bold" id="totalProducts">0</span>
                        </div>
                        <div>
                            <span class="text-muted">Sous-total :</span>
                            <span class="fw-bold" id="subTotal">0 FC</span>
                        </div>
                        <div>
                            <span class="text-muted">Frais de livraison :</span>
                            <span class="fw-bold">500 FC</span>
                        </div>
                        <div>
                            <span class="text-muted h6">Total :</span>
                            <span class="fw-bold text-primary h5" id="grandTotal">500 FC</span>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Actions -->
                <div class="d-flex gap-2 justify-content-end flex-wrap">
                    <a href="commandes.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Annuler
                    </a>
                    <button type="submit" class="btn btn-success" id="submitBtn">
                        <i class="bi bi-check-circle"></i> Créer la commande
                    </button>
                </div>
            </form>
        </div>

        <footer style="padding:1rem 2rem; border-top:1px solid var(--color-border); font-size:0.8rem; color:var(--color-text-muted); display:flex; justify-content:space-between; flex-wrap:wrap; gap:0.5rem;">
            <span>© <?= date('Y') ?> Butembo Marché Numérique</span>
            <span>Version 1.0</span>
        </footer>
    </div>

    <script>
        // ============================================
        // GESTION DES PRODUITS
        // ============================================
        let products = [];

        function addProduct() {
            const select = document.getElementById('productSelect');
            const quantityInput = document.getElementById('productQuantity');
            const option = select.options[select.selectedIndex];

            if (!option || !option.value) {
                showToast('Veuillez sélectionner un produit.', 'error');
                return;
            }

            const id = parseInt(option.value);
            const nom = option.text.split('(')[0].trim();
            const prix = parseFloat(option.dataset.prix);
            const stock = parseInt(option.dataset.stock);
            const unite = option.dataset.unite || '';
            const quantite = parseInt(quantityInput.value) || 1;

            if (quantite <= 0) {
                showToast('La quantité doit être supérieure à 0.', 'error');
                return;
            }

            if (quantite > stock) {
                showToast('Stock insuffisant. Disponible: ' + stock + ' ' + unite, 'error');
                return;
            }

            // Vérifier si le produit est déjà ajouté
            const existing = products.find(p => p.id === id);
            if (existing) {
                if (existing.quantite + quantite > stock) {
                    showToast('Stock insuffisant. Disponible: ' + stock + ' ' + unite, 'error');
                    return;
                }
                existing.quantite += quantite;
            } else {
                products.push({
                    id: id,
                    nom: nom,
                    prix: prix,
                    stock: stock,
                    unite: unite,
                    quantite: quantite
                });
            }

            updateProductsList();
            updateSummary();
            quantityInput.value = 1;
            select.value = '';
            showToast('Produit ajouté : ' + nom, 'success');
        }

        function removeProduct(index) {
            products.splice(index, 1);
            updateProductsList();
            updateSummary();
        }

        function updateQuantity(index, newQuantity) {
            const product = products[index];
            if (!product) return;

            newQuantity = parseInt(newQuantity) || 0;
            if (newQuantity <= 0) {
                removeProduct(index);
                return;
            }

            if (newQuantity > product.stock) {
                showToast('Stock insuffisant. Disponible: ' + product.stock + ' ' + product.unite, 'error');
                document.getElementById('qty-' + index).value = product.quantite;
                return;
            }

            product.quantite = newQuantity;
            updateSummary();
        }

        function updateProductsList() {
            const container = document.getElementById('productsList');

            if (products.length === 0) {
                container.innerHTML = `
                    <div class="empty-products" id="emptyProducts">
                        <i class="bi bi-box"></i>
                        <p>Aucun produit ajouté</p>
                    </div>
                `;
                return;
            }

            let html = '';
            products.forEach((p, index) => {
                const total = p.prix * p.quantite;
                html += `
                    <div class="product-item">
                        <div class="product-info">
                            <div class="product-name">${p.nom}</div>
                            <div class="product-detail">${p.prix.toLocaleString()} FC / ${p.unite} · Stock: ${p.stock}</div>
                        </div>
                        <div class="product-quantity">
                            <input type="number" id="qty-${index}" value="${p.quantite}" min="1" max="${p.stock}" onchange="updateQuantity(${index}, this.value)">
                        </div>
                        <div class="product-price">${total.toLocaleString()} FC</div>
                        <button type="button" class="btn-remove" onclick="removeProduct(${index})">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        function updateSummary() {
            let totalProducts = 0;
            let subTotal = 0;

            products.forEach(p => {
                totalProducts += p.quantite;
                subTotal += p.prix * p.quantite;
            });

            const frais = 500;
            const grandTotal = subTotal + frais;

            document.getElementById('productCount').textContent = totalProducts + ' produit(s)';
            document.getElementById('totalProducts').textContent = totalProducts;
            document.getElementById('subTotal').textContent = subTotal.toLocaleString() + ' FC';
            document.getElementById('grandTotal').textContent = grandTotal.toLocaleString() + ' FC';

            // Mettre à jour le champ caché des produits
            let productsInput = document.getElementById('productsInput');
            if (!productsInput) {
                productsInput = document.createElement('input');
                productsInput.type = 'hidden';
                productsInput.id = 'productsInput';
                productsInput.name = 'produits_data';
                document.getElementById('commandeForm').appendChild(productsInput);
            }
            productsInput.value = JSON.stringify(products);
        }

        // ============================================
        // VALIDATION FORMULAIRE
        // ============================================
        document.getElementById('commandeForm').addEventListener('submit', function(e) {
            if (products.length === 0) {
                e.preventDefault();
                showToast('Veuillez ajouter au moins un produit à la commande.', 'error');
                return false;
            }
        });

        // ============================================
        // TOAST
        // ============================================
        function showToast(message, type) {
            type = type || 'success';
            const existing = document.querySelector('.toast');
            if (existing) existing.remove();

            const toast = document.createElement('div');
            toast.className = 'toast toast-' + type;
            toast.innerHTML = `
                <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'}"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s';
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }

        // ============================================
        // DARK MODE & SIDEBAR
        // ============================================
        function toggleDarkMode() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
        }

        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.toggle('open');
            overlay.style.display = sidebar.classList.contains('open') ? 'block' : 'none';
        }

        // ============================================
        // TOAST PHP
        // ============================================
        <?php if ($toast): ?>
            showToast(<?= json_encode($toast['message'] ?? '') ?>, <?= json_encode($toast['type'] ?? 'success') ?>);
        <?php endif; ?>
    </script>
</body>

</html>
