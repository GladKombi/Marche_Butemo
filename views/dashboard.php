<?php

/**
 * views/dashboard.php
 * Tableau de bord principal - Version moderne, élégante et épurée
 * Adapté pour Butembo Marché Numérique
 */

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('Location: ../connexion.php');
    exit;
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/database.php';

// Inclure les fichiers Select
$selectFiles = [
    'CommandeSelect' => __DIR__ . '/../models/select/CommandeSelect.php',
    'ProduitSelect' => __DIR__ . '/../models/select/ProduitSelect.php',
    'UtilisateurSelect' => __DIR__ . '/../models/select/UtilisateurSelect.php'
];

foreach ($selectFiles as $name => $path) {
    if (file_exists($path)) {
        require_once $path;
    }
}

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'] ?? 0;
$user_nom = $_SESSION['user_nom'] ?? 'Utilisateur';
$user_prenom = $_SESSION['user_prenom'] ?? '';
$user_email = $_SESSION['user_email'] ?? '';
$user_type = $_SESSION['user_type'] ?? 'acheteur';
$user_photo = $_SESSION['user_photo'] ?? null;
$user_telephone = $_SESSION['user_telephone'] ?? null;
$userInitials = strtoupper(substr($user_nom, 0, 1) . substr($user_prenom, 0, 1));

// Définir le rôle et les permissions
$role_label = '';
$role_color = '';
switch ($user_type) {
    case 'admin':
        $role_label = 'Administrateur';
        $role_color = 'danger';
        break;
    case 'agriculteur':
        $role_label = 'Agriculteur';
        $role_color = 'success';
        break;
    case 'livreur':
        $role_label = 'Livreur';
        $role_color = 'info';
        break;
    case 'acheteur':
    default:
        $role_label = 'Acheteur';
        $role_color = 'secondary';
        break;
}

try {
    // Utiliser la fonction getDBConnection() maintenant disponible
    $pdo = getDBConnection();

    // Récupérer les statistiques selon le rôle
    if ($user_type === 'admin') {
        $statsGlobales = [
            'total_users' => class_exists('UtilisateurSelect') ? UtilisateurSelect::countAll() : 0,
            'total_orders' => class_exists('CommandeSelect') ? CommandeSelect::countAll() : 0,
            'total_products' => class_exists('ProduitSelect') ? ProduitSelect::countAll() : 0,
            'total_revenue' => class_exists('CommandeSelect') ? CommandeSelect::getTotalVentes('month') : 0
        ];
        $dernieresCommandes = class_exists('CommandeSelect') ? CommandeSelect::getRecentWithStatus(10) : [];
        $alertesStock = class_exists('ProduitSelect') ? ProduitSelect::getStockAlert(10) : [];
        $evolutionVentes = class_exists('CommandeSelect') ? CommandeSelect::getDailyStats(7) : [];
    } elseif ($user_type === 'agriculteur') {
        $agriculteur_id = $user_id;
        $produits = class_exists('ProduitSelect') ? ProduitSelect::getByUtilisateurAgriculteur($user_id) : [];
        $commandesAgriculteur = class_exists('CommandeSelect') ? CommandeSelect::getByAgriculteur($user_id) : [];
        $revenuAgriculteur = 0;
        foreach ($commandesAgriculteur as $commandeAgriculteur) {
            $revenuAgriculteur += (float) ($commandeAgriculteur->montant_agriculteur ?? 0);
        }
        $statsGlobales = [
            'total_products' => count($produits),
            'total_orders' => count($commandesAgriculteur),
            'total_revenue' => $revenuAgriculteur
        ];
        $dernieresCommandes = array_slice($commandesAgriculteur, 0, 10);
        $alertesStock = class_exists('ProduitSelect') ? ProduitSelect::getStockAlertByAgriculteur($agriculteur_id, 10) : [];
        $evolutionVentes = [];
    } elseif ($user_type === 'livreur') {
        $statsGlobales = [
            'total_deliveries' => 0,
            'pending_deliveries' => 0,
            'rating' => 4.5
        ];
        $dernieresCommandes = [];
        $alertesStock = [];
        $evolutionVentes = [];
    } else {
        // Acheteur
        $statsGlobales = [
            'total_orders' => class_exists('CommandeSelect') ? CommandeSelect::countByAcheteur($user_id) : 0,
            'pending_orders' => 0,
            'total_spent' => 0
        ];
        $dernieresCommandes = class_exists('CommandeSelect') ? CommandeSelect::getByAcheteur($user_id, 10) : [];
        $alertesStock = [];
        $evolutionVentes = [];
    }

    // Formater les données pour les graphiques
    $joursLabels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
    $ventesData = [];
    if (!empty($evolutionVentes) && is_array($evolutionVentes)) {
        foreach ($evolutionVentes as $item) {
            if (is_object($item) && isset($item->total_ventes)) {
                $ventesData[] = (float)$item->total_ventes;
            } elseif (is_array($item) && isset($item['total_ventes'])) {
                $ventesData[] = (float)$item['total_ventes'];
            }
        }
    }
    if (count($ventesData) < 7) {
        $ventesData = array_pad($ventesData, 7, 0);
    }

    // Catégories pour le graphique
    $categoriesLabels = ['Manioc', 'Maïs', 'Haricots', 'Légumes', 'Fruits'];
    $categoriesData = [35, 25, 20, 12, 8];
} catch (PDOException $e) {
    error_log("Erreur dashboard: " . $e->getMessage());
    $statsGlobales = ['total_users' => 0, 'total_orders' => 0, 'total_products' => 0, 'total_revenue' => 0];
    $dernieresCommandes = [];
    $alertesStock = [];
    $ventesData = [1250000, 980000, 1450000, 1100000, 1350000, 1550000, 1280000];
    $categoriesLabels = ['Manioc', 'Maïs', 'Haricots', 'Légumes', 'Fruits'];
    $categoriesData = [35, 25, 20, 12, 8];
} catch (Exception $e) {
    error_log("Erreur générale dashboard: " . $e->getMessage());
    $statsGlobales = ['total_users' => 0, 'total_orders' => 0, 'total_products' => 0, 'total_revenue' => 0];
    $dernieresCommandes = [];
    $alertesStock = [];
    $ventesData = [1250000, 980000, 1450000, 1100000, 1350000, 1550000, 1280000];
    $categoriesLabels = ['Manioc', 'Maïs', 'Haricots', 'Légumes', 'Fruits'];
    $categoriesData = [35, 25, 20, 12, 8];
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Butembo Marché Numérique</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
        /* ========== RESET & BASE ========== */
        *,
        ::before,
        ::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --color-bg: #fafbfc;
            --color-surface: #ffffff;
            --color-primary: #2d6a4f;
            --color-primary-hover: #1b4332;
            --color-primary-soft: #e8f5ee;
            --color-primary-border: #a8d5ba;
            --color-text: #0f172a;
            --color-text-secondary: #64748b;
            --color-text-muted: #94a3b8;
            --color-border: #e2e8f0;
            --color-success: #10b981;
            --color-warning: #f59e0b;
            --color-danger: #ef4444;
            --color-info: #3b82f6;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --radius-sm: 0.5rem;
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --radius-xl: 1.25rem;
            --font-sans: 'Inter', system-ui, -apple-system, sans-serif;
            --font-display: 'Outfit', sans-serif;
            --transition: 200ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dark {
            --color-bg: #0b1120;
            --color-surface: #1e293b;
            --color-primary-soft: #1e293b;
            --color-primary-border: #334155;
            --color-text: #f1f5f9;
            --color-text-secondary: #94a3b8;
            --color-text-muted: #64748b;
            --color-border: #1e293b;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--color-bg);
            color: var(--color-text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            transition: background-color 0.3s, color 0.3s;
        }

        /* ========== SCROLLBAR ========== */
        ::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--color-text-muted);
            border-radius: 8px;
        }

        /* ========== LAYOUT ========== */
        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* ========== SIDEBAR ========== */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 16rem;
            background-color: var(--color-surface);
            border-right: 1px solid var(--color-border);
            z-index: 40;
            transition: transform var(--transition);
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
            color: var(--color-text);
            letter-spacing: -0.025em;
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
            transition: all var(--transition);
            white-space: nowrap;
        }

        .nav-link:hover {
            background-color: var(--color-primary-soft);
            color: var(--color-text);
        }

        .nav-link.active {
            background-color: var(--color-primary);
            color: #fff;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(45, 106, 79, 0.25);
        }

        .nav-link.active .nav-badge {
            background: rgba(255, 255, 255, 0.25);
            color: #fff;
        }

        .nav-badge {
            margin-left: auto;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.15rem 0.5rem;
            border-radius: 999px;
            background: var(--color-primary-soft);
            color: var(--color-primary);
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
            color: var(--color-text);
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
            transition: color var(--transition);
            display: flex;
            text-decoration: none;
        }

        .logout-btn:hover {
            color: var(--color-danger);
        }

        /* ========== MAIN CONTENT ========== */
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
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--color-border);
            padding: 1rem 2rem;
        }

        .dark .top-bar {
            background: rgba(11, 17, 32, 0.8);
        }

        .dashboard-content {
            flex: 1;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        /* ========== TYPOGRAPHIE ========== */
        .page-title {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            color: var(--color-text);
        }

        .page-subtitle {
            font-size: 0.875rem;
            color: var(--color-text-muted);
            font-weight: 400;
        }

        /* ========== STATS CARDS ========== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }

        .stat-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-xl);
            padding: 1.5rem;
            transition: all 0.3s ease;
            cursor: default;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            border-color: var(--color-primary-border);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-card-title {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--color-text-muted);
        }

        .stat-card-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-card-value {
            font-family: var(--font-display);
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            color: var(--color-text);
            margin-bottom: 0.25rem;
        }

        .stat-card-trend {
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .trend-up {
            color: var(--color-success);
        }

        .trend-down {
            color: var(--color-danger);
        }

        /* ========== CONTENT GRID ========== */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
        }

        .chart-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-xl);
            padding: 1.5rem;
        }

        .chart-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .chart-title {
            font-family: var(--font-display);
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--color-text);
        }

        .chart-subtitle {
            font-size: 0.8rem;
            color: var(--color-text-muted);
        }

        .chart-container {
            position: relative;
            width: 100%;
            height: 280px;
        }

        .legend-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.85rem;
        }

        .legend-dot {
            width: 0.7rem;
            height: 0.7rem;
            border-radius: 50%;
            margin-right: 0.5rem;
        }

        /* ========== TABLEAUX ========== */
        .table-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-xl);
            overflow: hidden;
        }

        .table-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--color-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--color-primary);
            color: #fff;
            border: none;
            padding: 0.6rem 1.25rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all var(--transition);
            letter-spacing: 0.02em;
            box-shadow: 0 2px 4px rgba(45, 106, 79, 0.2);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            background: var(--color-primary-hover);
            box-shadow: 0 4px 8px rgba(45, 106, 79, 0.3);
            color: #fff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 0.75rem 1.5rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--color-text-muted);
            background: var(--color-bg);
            border-bottom: 1px solid var(--color-border);
        }

        td {
            padding: 1rem 1.5rem;
            font-size: 0.85rem;
            border-bottom: 1px solid var(--color-border);
            color: var(--color-text-secondary);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: var(--color-primary-soft);
        }

        .badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            display: inline-block;
            letter-spacing: 0.03em;
        }

        .badge-success {
            background: #ecfdf5;
            color: #065f46;
        }

        .badge-warning {
            background: #fffbeb;
            color: #92400e;
        }

        .badge-danger {
            background: #fef2f2;
            color: #991b1b;
        }

        .badge-info {
            background: #eff6ff;
            color: #1e40af;
        }

        .dark .badge-success {
            background: #064e3b;
            color: #a7f3d0;
        }

        .dark .badge-warning {
            background: #78350f;
            color: #fde68a;
        }

        .dark .badge-danger {
            background: #7f1d1d;
            color: #fecaca;
        }

        .dark .badge-info {
            background: #1e3a5f;
            color: #93c5fd;
        }

        /* ========== ALERTES ========== */
        .alert-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--color-border);
        }

        .alert-item:last-child {
            border-bottom: none;
        }

        .progress-bar {
            width: 100%;
            height: 4px;
            background: var(--color-border);
            border-radius: 4px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            background: var(--color-danger);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--color-text-muted);
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

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
                display: block;
            }
        }

        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .top-bar {
                padding: 0.75rem 1rem;
            }

            .dashboard-content {
                padding: 1rem;
            }

            .table-header {
                flex-direction: column;
                align-items: stretch;
            }

            .page-title {
                font-size: 1.2rem;
            }

            .stat-card-value {
                font-size: 1.4rem;
            }

            .table-responsive {
                overflow-x: auto;
            }
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--color-text);
            cursor: pointer;
            padding: 0.25rem;
        }

        @media (max-width: 1024px) {
            .mobile-menu-btn {
                display: flex;
            }
        }

        /* Overlay mobile */
        #sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 35;
        }

        #sidebar-overlay.active {
            display: block;
        }

        /* Role badge in sidebar */
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

        .role-badge-sidebar.agriculteur {
            background: #28a745;
            color: #fff;
        }

        .role-badge-sidebar.livreur {
            background: #17a2b8;
            color: #fff;
        }

        .role-badge-sidebar.acheteur {
            background: #6c757d;
            color: #fff;
        }
    </style>
</head>

<body>
    <!-- Bannière hors ligne -->
    <div id="offline-banner" style="display:none; position:fixed; top:0; left:0; right:0; background:#ef4444; color:white; text-align:center; padding:6px; font-size:0.8rem; z-index:1000;">
        <i class="bi bi-wifi-off me-2"></i> Mode hors ligne - Données potentiellement obsolètes
    </div>

    <div class="app-container">
        <!-- Overlay mobile -->
        <div id="sidebar-overlay" onclick="toggleSidebar()"></div>

        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar">
            <div class="sidebar-logo">
                <div class="logo-icon">
                    <i class="bi bi-basket2-fill"></i>
                </div>
                <div>
                    <h1>Butembo Marché</h1>
                    <span>Tableau de bord</span>
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

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <header class="top-bar">
                <div style="display:flex; align-items:center; justify-content:space-between; width:100%; flex-wrap:wrap; gap:0.5rem;">
                    <div style="display:flex; align-items:center; gap:1rem;">
                        <button class="mobile-menu-btn" onclick="toggleSidebar()">
                            <i class="bi bi-list" style="font-size:1.5rem;"></i>
                        </button>
                        <div>
                            <h2 class="page-title">Bonjour, <?= htmlspecialchars($user_prenom) ?> 👋</h2>
                            <p class="page-subtitle">
                                <i class="bi bi-calendar3 me-1"></i> <?= date('l d F Y') ?> ·
                                <span id="last-update"><i class="bi bi-clock me-1"></i> <?= date('H:i') ?></span>
                            </p>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:0.5rem;">
                        <button onclick="refreshData()" class="btn btn-outline-secondary btn-sm" style="border-radius:var(--radius-md);">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                        <button onclick="toggleDarkMode()" class="btn btn-outline-secondary btn-sm" style="border-radius:var(--radius-md);">
                            <i class="bi bi-moon"></i>
                        </button>
                        <span class="badge bg-<?= $role_color ?> text-white ms-2"><?= $role_label ?></span>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Cartes Statistiques -->
                <div class="stats-grid">
                    <?php if ($user_type === 'admin'): ?>
                        <!-- Stats Admin -->
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Utilisateurs</span>
                                <div class="stat-card-icon" style="background:#e8f5ee; color:#2d6a4f;">
                                    <i class="bi bi-people"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?= $statsGlobales['total_users'] ?? 0 ?></div>
                            <div style="font-size:0.8rem; color:var(--color-text-muted);">Inscrits</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Commandes</span>
                                <div class="stat-card-icon" style="background:#eff6ff; color:#3b82f6;">
                                    <i class="bi bi-cart3"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?= $statsGlobales['total_orders'] ?? 0 ?></div>
                            <div style="font-size:0.8rem; color:var(--color-text-muted);">Total</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Produits</span>
                                <div class="stat-card-icon" style="background:#fef3c7; color:#f59e0b;">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?= $statsGlobales['total_products'] ?? 0 ?></div>
                            <div style="font-size:0.8rem; color:var(--color-text-muted);">En catalogue</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Chiffre d'affaires</span>
                                <div class="stat-card-icon" style="background:#f0fdf4; color:#10b981;">
                                    <i class="bi bi-currency-dollar"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?= number_format($statsGlobales['total_revenue'] ?? 0, 0, ',', ' ') ?> FC</div>
                            <div style="font-size:0.8rem; color:var(--color-text-muted);">Ce mois</div>
                        </div>

                    <?php elseif ($user_type === 'agriculteur'): ?>
                        <!-- Stats Agriculteur -->
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Mes Produits</span>
                                <div class="stat-card-icon" style="background:#e8f5ee; color:#2d6a4f;">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?= $statsGlobales['total_products'] ?? 0 ?></div>
                            <div style="font-size:0.8rem; color:var(--color-text-muted);">En vente</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Commandes</span>
                                <div class="stat-card-icon" style="background:#eff6ff; color:#3b82f6;">
                                    <i class="bi bi-cart3"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?= $statsGlobales['total_orders'] ?? 0 ?></div>
                            <div style="font-size:0.8rem; color:var(--color-text-muted);">Reçues</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Revenus</span>
                                <div class="stat-card-icon" style="background:#fef3c7; color:#f59e0b;">
                                    <i class="bi bi-currency-dollar"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?= number_format($statsGlobales['total_revenue'] ?? 0, 0, ',', ' ') ?> FC</div>
                            <div style="font-size:0.8rem; color:var(--color-text-muted);">Total</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Alertes Stock</span>
                                <div class="stat-card-icon" style="background:#fef2f2; color:#ef4444;">
                                    <i class="bi bi-exclamation-triangle"></i>
                                </div>
                            </div>
                            <div class="stat-card-value" style="color:#ef4444;"><?= count($alertesStock) ?></div>
                            <div style="font-size:0.8rem; color:var(--color-text-muted);">Produits critiques</div>
                        </div>

                    <?php elseif ($user_type === 'livreur'): ?>
                        <!-- Stats Livreur -->
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Livraisons</span>
                                <div class="stat-card-icon" style="background:#e8f5ee; color:#2d6a4f;">
                                    <i class="bi bi-truck"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?= $statsGlobales['total_deliveries'] ?? 0 ?></div>
                            <div style="font-size:0.8rem; color:var(--color-text-muted);">Total</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">En attente</span>
                                <div class="stat-card-icon" style="background:#fef3c7; color:#f59e0b;">
                                    <i class="bi bi-clock"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?= $statsGlobales['pending_deliveries'] ?? 0 ?></div>
                            <div style="font-size:0.8rem; color:var(--color-text-muted);">À livrer</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Note moyenne</span>
                                <div class="stat-card-icon" style="background:#fef3c7; color:#f59e0b;">
                                    <i class="bi bi-star"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?= number_format($statsGlobales['rating'] ?? 0, 1) ?> ★</div>
                            <div style="font-size:0.8rem; color:var(--color-text-muted);">Clients satisfaits</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Gain estimé</span>
                                <div class="stat-card-icon" style="background:#f0fdf4; color:#10b981;">
                                    <i class="bi bi-currency-dollar"></i>
                                </div>
                            </div>
                            <div class="stat-card-value">0 FC</div>
                            <div style="font-size:0.8rem; color:var(--color-text-muted);">Ce mois</div>
                        </div>

                    <?php else: ?>
                        <!-- Stats Acheteur -->
                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Mes Commandes</span>
                                <div class="stat-card-icon" style="background:#e8f5ee; color:#2d6a4f;">
                                    <i class="bi bi-cart3"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?= $statsGlobales['total_orders'] ?? 0 ?></div>
                            <div style="font-size:0.8rem; color:var(--color-text-muted);">Total</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">En cours</span>
                                <div class="stat-card-icon" style="background:#fef3c7; color:#f59e0b;">
                                    <i class="bi bi-clock"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?= $statsGlobales['pending_orders'] ?? 0 ?></div>
                            <div style="font-size:0.8rem; color:var(--color-text-muted);">À recevoir</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Dépensé</span>
                                <div class="stat-card-icon" style="background:#f0fdf4; color:#10b981;">
                                    <i class="bi bi-currency-dollar"></i>
                                </div>
                            </div>
                            <div class="stat-card-value"><?= number_format($statsGlobales['total_spent'] ?? 0, 0, ',', ' ') ?> FC</div>
                            <div style="font-size:0.8rem; color:var(--color-text-muted);">Total</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Livraisons</span>
                                <div class="stat-card-icon" style="background:#eff6ff; color:#3b82f6;">
                                    <i class="bi bi-truck"></i>
                                </div>
                            </div>
                            <div class="stat-card-value">0</div>
                            <div style="font-size:0.8rem; color:var(--color-text-muted);">Effectuées</div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Graphiques (uniquement pour Admin) -->
                <?php if ($user_type === 'admin'): ?>
                    <div class="content-grid">
                        <div class="chart-card">
                            <div class="chart-card-header">
                                <div>
                                    <div class="chart-title">Évolution des Ventes</div>
                                    <div class="chart-subtitle">7 derniers jours (Francs Congolais)</div>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="sales-chart"></canvas>
                            </div>
                        </div>
                        <div class="chart-card">
                            <div class="chart-card-header">
                                <div>
                                    <div class="chart-title">Par Catégorie</div>
                                    <div class="chart-subtitle">Répartition des ventes</div>
                                </div>
                            </div>
                            <div class="chart-container" style="height:220px;">
                                <canvas id="category-chart"></canvas>
                            </div>
                            <div id="category-legend" class="legend-list"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Alertes Stock (Admin & Agriculteur) -->
                <?php if (($user_type === 'admin' || $user_type === 'agriculteur') && !empty($alertesStock)): ?>
                    <div class="table-card">
                        <div class="table-header">
                            <div>
                                <div class="chart-title" style="margin-bottom:0;">Alertes Stock Critique</div>
                                <div class="chart-subtitle"><?= count($alertesStock) ?> produit(s) sous le seuil</div>
                            </div>
                        </div>
                        <?php foreach ($alertesStock as $alerte): ?>
                            <?php $alerte = (array) $alerte; ?>
                            <div class="alert-item">
                                <div style="flex:1;">
                                    <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:0.5rem;">
                                        <span style="font-weight:600; font-size:0.9rem;"><?= htmlspecialchars($alerte['nom'] ?? $alerte['designation'] ?? 'Produit') ?></span>
                                        <span style="font-weight:700; color:var(--color-danger);"><?= $alerte['quantite_stock'] ?? $alerte['stock_actuel'] ?? 0 ?> unités</span>
                                    </div>
                                    <?php
                                    $stock = $alerte['quantite_stock'] ?? $alerte['stock_actuel'] ?? 0;
                                    $seuil = $alerte['stock_min_alerte'] ?? $alerte['seuil_critique'] ?? 10;
                                    $pourcentage = $seuil > 0 ? min(($stock / $seuil) * 100, 100) : 0;
                                    ?>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width:<?= $pourcentage ?>%;"></div>
                                    </div>
                                    <div style="font-size:0.7rem; color:var(--color-text-muted); margin-top:0.25rem;">
                                        Seuil critique : <?= $seuil ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Dernières Commandes / Transactions -->
                <?php if (!empty($dernieresCommandes)): ?>
                    <div class="table-card">
                        <div class="table-header">
                            <div>
                                <div class="chart-title" style="margin-bottom:0;">Dernières Transactions</div>
                                <div class="chart-subtitle">Les plus récentes</div>
                            </div>
                            <?php if ($user_type === 'admin'): ?>
                                <a href="#" class="btn-primary">
                                    <i class="bi bi-plus-circle"></i> Nouvelle Vente
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>N° Commande</th>
                                        <th>Client</th>
                                        <th>Date</th>
                                        <th style="text-align:right;">Montant</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dernieresCommandes as $commande): ?>
                                        <tr>
                                            <td style="font-weight:600; color:var(--color-primary);">
                                                #<?= htmlspecialchars($commande->numero_commande ?? 'N/A') ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($commande->acheteur_nom ?? '') ?>
                                                <?= htmlspecialchars($commande->acheteur_prenom ?? '') ?>
                                            </td>
                                            <td><?= date('d/m H:i', strtotime($commande->date_commande ?? '')) ?></td>
                                            <td style="text-align:right; font-weight:600;">
                                                <?= number_format($commande->montant_total ?? 0, 0, ',', ' ') ?> FC
                                            </td>
                                            <td>
                                                <?php
                                                $statut = $commande->statut_commande ?? '';
                                                $badgeClass = 'badge-secondary';
                                                if ($statut === 'livree') $badgeClass = 'badge-success';
                                                elseif ($statut === 'en_livraison') $badgeClass = 'badge-info';
                                                elseif ($statut === 'en_attente') $badgeClass = 'badge-warning';
                                                elseif ($statut === 'annulee') $badgeClass = 'badge-danger';
                                                ?>
                                                <span class="badge <?= $badgeClass ?>">
                                                    <?= str_replace('_', ' ', $statut ?: 'N/A') ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Footer -->
            <footer style="padding:1rem 2rem; border-top:1px solid var(--color-border); font-size:0.8rem; color:var(--color-text-muted); display:flex; justify-content:space-between; flex-wrap:wrap; gap:0.5rem;">
                <span>© <?= date('Y') ?> Butembo Marché Numérique</span>
                <span>Version 1.0 · PHP 8.0+</span>
            </footer>
        </div>
    </div>

    <script>
        // Données PHP -> JS
        const ventesData = <?= json_encode($ventesData) ?: '[1250000, 980000, 1450000, 1100000, 1350000, 1550000, 1280000]' ?>;
        const joursLabels = <?= json_encode($joursLabels) ?: '["Lun","Mar","Mer","Jeu","Ven","Sam","Dim"]' ?>;
        const catLabels = <?= json_encode($categoriesLabels) ?: '["Manioc","Maïs","Haricots","Légumes","Fruits"]' ?>;
        const catData = <?= json_encode($categoriesData) ?: '[35,25,20,12,8]' ?>;
        const catColors = ['#2d6a4f', '#40916c', '#52b788', '#74c69d', '#95d5b2'];

        // Initialisation des graphiques
        function initCharts() {
            const isDark = document.body.classList.contains('dark');
            const textColor = isDark ? '#94a3b8' : '#64748b';
            const gridColor = isDark ? 'rgba(51,65,85,0.5)' : 'rgba(226,232,240,0.8)';

            // Graphique Ventes (ligne)
            const salesCtx = document.getElementById('sales-chart');
            if (salesCtx) {
                const ctx = salesCtx.getContext('2d');
                const gradient = ctx.createLinearGradient(0, 0, 0, 280);
                gradient.addColorStop(0, 'rgba(45,106,79,0.2)');
                gradient.addColorStop(1, 'rgba(45,106,79,0)');

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: joursLabels,
                        datasets: [{
                            data: ventesData,
                            borderColor: '#2d6a4f',
                            backgroundColor: gradient,
                            borderWidth: 2.5,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#2d6a4f',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: textColor,
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            y: {
                                grid: {
                                    color: gridColor,
                                    borderDash: [4, 4]
                                },
                                ticks: {
                                    color: textColor,
                                    callback: v => (v / 1000).toFixed(0) + 'k',
                                    font: {
                                        size: 11
                                    }
                                }
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        }
                    }
                });
            }

            // Graphique Catégories (anneau)
            const catCtx = document.getElementById('category-chart');
            if (catCtx) {
                const ctx = catCtx.getContext('2d');
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: catLabels,
                        datasets: [{
                            data: catData,
                            backgroundColor: catColors,
                            borderWidth: 0,
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '75%',
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });

                // Légende catégories
                const total = catData.reduce((a, b) => a + b, 0);
                const legendHtml = catLabels.map((label, i) => {
                    const pct = total > 0 ? ((catData[i] / total) * 100).toFixed(1) : 0;
                    return `
                        <div class="legend-item">
                            <span style="display:flex; align-items:center; gap:0.5rem;">
                                <span class="legend-dot" style="background:${catColors[i]};"></span> ${label}
                            </span>
                            <span style="font-weight:600;">${pct}%</span>
                        </div>`;
                }).join('');
                const legendContainer = document.getElementById('category-legend');
                if (legendContainer) legendContainer.innerHTML = legendHtml;
            }
        }

        // Thème sombre
        function toggleDarkMode() {
            document.body.classList.toggle('dark');
            localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
            setTimeout(initCharts, 100);
        }

        if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark');

        // Sidebar mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        }

        // Actualisation
        async function refreshData() {
            const btn = event?.target?.closest('button');
            if (btn) {
                btn.innerHTML = '<i class="bi bi-arrow-clockwise spinner-border spinner-border-sm"></i>';
                btn.disabled = true;
            }
            try {
                const res = await fetch('../api/dashboard_data.php');
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur lors du rafraîchissement des données');
                }
            } catch (e) {
                console.error('Erreur de rafraîchissement', e);
                alert('Impossible de rafraîchir les données');
            }
            if (btn) {
                btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
                btn.disabled = false;
            }
        }

        // Hors ligne
        window.addEventListener('online', () => document.getElementById('offline-banner').style.display = 'none');
        window.addEventListener('offline', () => document.getElementById('offline-banner').style.display = 'block');
        if (!navigator.onLine) document.getElementById('offline-banner').style.display = 'block';

        // Initialisation au chargement
        document.addEventListener('DOMContentLoaded', function() {
            initCharts();
            setInterval(() => {
                const now = new Date();
                document.getElementById('last-update').innerHTML =
                    '<i class="bi bi-clock me-1"></i> ' +
                    now.getHours().toString().padStart(2, '0') + ':' +
                    now.getMinutes().toString().padStart(2, '0');
            }, 60000);
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
