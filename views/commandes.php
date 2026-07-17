<?php

/**
 * views/commandes.php
 * Gestion des commandes
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

$user_id = $_SESSION['user_id'];
$user_nom = $_SESSION['user_nom'];
$user_prenom = $_SESSION['user_prenom'];
$user_email = $_SESSION['user_email'];
$user_type = $_SESSION['user_type'];
$user_photo = $_SESSION['user_photo'] ?? null;
$userInitials = strtoupper(substr($user_nom, 0, 1) . substr($user_prenom, 0, 1));

$role_label = 'Administrateur';
$role_color = 'danger';

try {
    $pdo = getDBConnection();

    $commandes = CommandeSelect::getAll();

    $stats = [
        'total' => CommandeSelect::countAll(),
        'en_attente' => CommandeSelect::countByStatus('en_attente'),
        'en_livraison' => CommandeSelect::countByStatus('en_livraison'),
        'livrees' => CommandeSelect::countByStatus('livree'),
        'annulees' => CommandeSelect::countByStatus('annulee'),
        'ca_mois' => CommandeSelect::getTotalVentes('month')
    ];
} catch (PDOException $e) {
    error_log("Erreur: " . $e->getMessage());
    $commandes = [];
    $stats = ['total' => 0, 'en_attente' => 0, 'en_livraison' => 0, 'livrees' => 0, 'annulees' => 0, 'ca_mois' => 0];
}

$toast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commandes - Butembo Marché</title>
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
        }

        .breadcrumb-item:hover {
            color: var(--color-primary);
        }

        /* ===== STATS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 1rem;
        }

        .stat-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-xl);
            padding: 1.25rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-color: #a8d5ba;
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .stat-card .stat-number {
            font-family: var(--font-display);
            font-size: 1.8rem;
            font-weight: 700;
        }

        .stat-card .stat-label {
            font-size: 0.7rem;
            color: var(--color-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 0.2rem;
        }

        .stat-card .stat-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        /* ===== TOOLBAR ===== */
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
            flex: 1;
            max-width: 320px;
        }

        .search-box i {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-text-muted);
        }

        .search-input {
            width: 100%;
            padding: 0.65rem 1rem 0.65rem 2.5rem;
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            font-size: 0.85rem;
            color: var(--color-text);
            outline: none;
            font-family: var(--font-sans);
        }

        .search-input:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(45, 106, 79, 0.15);
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

        /* ===== TABLE ===== */
        .table-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-xl);
            overflow: hidden;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 0.75rem 1.25rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--color-text-muted);
            background: var(--color-bg);
            border-bottom: 1px solid var(--color-border);
        }

        td {
            padding: 0.9rem 1.25rem;
            font-size: 0.85rem;
            border-bottom: 1px solid var(--color-border);
            color: var(--color-text-secondary);
        }

        tr:hover td {
            background: var(--color-primary-soft);
        }

        .badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.3rem 0.75rem;
            border-radius: 999px;
            display: inline-block;
            letter-spacing: 0.03em;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background: #fef2f2;
            color: #991b1b;
        }

        .badge-info {
            background: #dbeafe;
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

        .action-btn {
            background: none;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            padding: 0.3rem 0.5rem;
            cursor: pointer;
            color: var(--color-text-secondary);
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
        }

        .action-btn:hover {
            border-color: var(--color-primary);
            color: var(--color-primary);
        }

        .action-btn.delete:hover {
            border-color: var(--color-danger);
            color: var(--color-danger);
            background: #fef2f2;
        }

        .numero-commande {
            font-weight: 600;
            color: var(--color-primary);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--color-text-muted);
        }

        .empty-state i {
            font-size: 3rem;
            display: block;
            margin-bottom: 1rem;
        }

        .details-modal-overlay { position: fixed; inset: 0; z-index: 200; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgba(15,23,42,.55); opacity: 0; visibility: hidden; transition: .2s ease; backdrop-filter: blur(3px); }
        .details-modal-overlay.active { opacity: 1; visibility: visible; }
        .details-modal { width: 100%; max-width: 760px; max-height: calc(100vh - 2rem); overflow-y: auto; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-xl); box-shadow: var(--shadow-lg); transform: translateY(16px); transition: .2s ease; }
        .details-modal-overlay.active .details-modal { transform: translateY(0); }
        .details-modal-header { position: sticky; top: 0; z-index: 2; display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; background: var(--color-surface); border-bottom: 1px solid var(--color-border); }
        .details-modal-close { border: 0; background: transparent; color: var(--color-text-muted); font-size: 1.5rem; line-height: 1; cursor: pointer; }
        .details-modal-body { padding: 1.5rem; }
        .details-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .details-box { padding: 1rem; background: var(--color-bg); border: 1px solid var(--color-border); border-radius: var(--radius-md); }
        .details-box-label { color: var(--color-text-muted); font-size: .7rem; text-transform: uppercase; font-weight: 700; margin-bottom: .3rem; }
        .details-products { width: 100%; border-collapse: collapse; }
        .details-products th, .details-products td { padding: .7rem; border-bottom: 1px solid var(--color-border); font-size: .8rem; }
        .details-product { display: flex; align-items: center; gap: .65rem; }
        .details-product img { width: 38px; height: 38px; object-fit: cover; border-radius: .4rem; border: 1px solid var(--color-border); }
        .details-loading { padding: 3rem; text-align: center; color: var(--color-text-muted); }
        @media (max-width: 575.98px) { .details-grid { grid-template-columns: 1fr; } .details-modal-body { padding: 1rem; } }

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

            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                max-width: 100%;
            }

            .page-content {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 575.98px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }

            .stat-card .stat-number {
                font-size: 1.3rem;
            }
        }
    </style>
</head>

<body>
    <div id="sidebar-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:35;" onclick="toggleSidebar()"></div>

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

    <div class="main-content">
        <header class="top-bar">
            <div style="display:flex; align-items:center; justify-content:space-between; width:100%; flex-wrap:wrap; gap:0.5rem;">
                <div style="display:flex; align-items:center; gap:1rem;">
                    <button class="mobile-menu-btn" onclick="toggleSidebar()">
                        <i class="bi bi-list" style="font-size:1.5rem;"></i>
                    </button>
                    <h2 class="page-title">Commandes</h2>
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
            <nav class="breadcrumbs">
                <span class="breadcrumb-item active">Toutes les commandes</span>
            </nav>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon text-primary"><i class="bi bi-cart3"></i></div>
                    <div class="stat-number"><?= $stats['total'] ?></div>
                    <div class="stat-label">Total</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon text-warning"><i class="bi bi-clock"></i></div>
                    <div class="stat-number"><?= $stats['en_attente'] ?></div>
                    <div class="stat-label">En attente</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon text-info"><i class="bi bi-truck"></i></div>
                    <div class="stat-number"><?= $stats['en_livraison'] ?></div>
                    <div class="stat-label">En livraison</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon text-success"><i class="bi bi-check-circle"></i></div>
                    <div class="stat-number"><?= $stats['livrees'] ?></div>
                    <div class="stat-label">Livrées</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon text-danger"><i class="bi bi-x-circle"></i></div>
                    <div class="stat-number"><?= $stats['annulees'] ?></div>
                    <div class="stat-label">Annulées</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon text-success"><i class="bi bi-currency-dollar"></i></div>
                    <div class="stat-number"><?= number_format($stats['ca_mois'], 0, ',', ' ') ?></div>
                    <div class="stat-label">CA du mois</div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="toolbar">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" class="search-input" id="tableSearch" placeholder="Rechercher une commande..." onkeyup="filterTable()">
                </div>
                <a href="commande-ajout.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nouvelle commande
                </a>
            </div>

            <!-- Tableau -->
            <div class="table-card">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>N° Commande</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>Statut</th>
                                <th style="width:90px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-commandes">
                            <?php if (!empty($commandes) && count($commandes) > 0): ?>
                                <?php foreach ($commandes as $c): ?>
                                    <?php
                                    $statut = $c->statut_commande ?? 'en_attente';
                                    $statutLabels = [
                                        'en_attente' => ['label' => 'En attente', 'color' => 'warning'],
                                        'en_livraison' => ['label' => 'En livraison', 'color' => 'info'],
                                        'livree' => ['label' => 'Livrée', 'color' => 'success'],
                                        'annulee' => ['label' => 'Annulée', 'color' => 'danger']
                                    ];
                                    $s = $statutLabels[$statut] ?? ['label' => ucfirst($statut), 'color' => 'secondary'];
                                    ?>
                                    <tr data-search="<?= strtolower(htmlspecialchars(($c->numero_commande ?? '') . ' ' . ($c->acheteur_nom ?? '') . ' ' . ($c->acheteur_prenom ?? ''))); ?>">
                                        <td><span class="numero-commande">#<?= htmlspecialchars($c->numero_commande ?? 'N/A') ?></span></td>
                                        <td><?= htmlspecialchars(($c->acheteur_prenom ?? '') . ' ' . ($c->acheteur_nom ?? '')) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($c->date_commande ?? '')) ?></td>
                                        <td style="font-weight:600;"><?= number_format($c->montant_total ?? 0, 0, ',', ' ') ?> FC</td>
                                        <td><span class="badge badge-<?= $s['color'] ?>"><?= $s['label'] ?></span></td>
                                        <td>
                                            <div style="display:flex; gap:0.3rem;">
                                                <button class="action-btn" onclick="viewCommande(<?= $c->id ?>)" title="Voir">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="action-btn delete" onclick="confirmDelete(<?= $c->id ?>, '<?= addslashes($c->numero_commande ?? '') ?>')" title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        <i class="bi bi-cart3"></i>
                                        <p style="font-weight:500;">Aucune commande</p>
                                        <p style="font-size:0.8rem;">Cliquez sur "Nouvelle commande" pour commencer.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <footer style="padding:1rem 2rem; border-top:1px solid var(--color-border); font-size:0.8rem; color:var(--color-text-muted); display:flex; justify-content:space-between; flex-wrap:wrap; gap:0.5rem;">
            <span>© <?= date('Y') ?> Butembo Marché Numérique</span>
            <span>Version 1.0</span>
        </footer>
    </div>

    <div class="details-modal-overlay" id="commandeDetailsModal" aria-hidden="true">
        <div class="details-modal" role="dialog" aria-modal="true" aria-labelledby="commandeDetailsTitle">
            <div class="details-modal-header">
                <h3 class="page-title mb-0" id="commandeDetailsTitle">Détails de la commande</h3>
                <button type="button" class="details-modal-close" onclick="closeCommandeModal()" aria-label="Fermer">&times;</button>
            </div>
            <div class="details-modal-body" id="commandeDetailsBody"><div class="details-loading">Chargement…</div></div>
        </div>
    </div>

    <script>
        function filterTable() {
            const query = document.getElementById('tableSearch').value.toLowerCase().trim();
            const rows = document.querySelectorAll('#tbody-commandes tr');
            rows.forEach(row => {
                if (row.querySelector('.empty-state')) return;
                const search = row.dataset.search || '';
                row.style.display = !query || search.includes(query) ? '' : 'none';
            });
        }

        async function viewCommande(id) {
            const modal = document.getElementById('commandeDetailsModal');
            const body = document.getElementById('commandeDetailsBody');
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            body.innerHTML = '<div class="details-loading"><span class="spinner-border spinner-border-sm me-2"></span>Chargement…</div>';

            try {
                const response = await fetch('../models/traitement/commandes-post.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'details', id: id})
                });
                const result = await response.json();
                if (!result.success) throw new Error(result.message || 'Impossible de charger la commande.');
                renderCommandeDetails(result.commande, result.lignes || []);
            } catch (error) {
                body.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(error.message) + '</div>';
            }
        }

        function closeCommandeModal() {
            const modal = document.getElementById('commandeDetailsModal');
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        document.getElementById('commandeDetailsModal').addEventListener('click', function (event) {
            if (event.target === this) closeCommandeModal();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && document.getElementById('commandeDetailsModal').classList.contains('active')) {
                closeCommandeModal();
            }
        });

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value == null ? '' : String(value);
            return div.innerHTML;
        }

        function renderCommandeDetails(c, lignes) {
            const statusLabels = {en_attente: 'En attente', en_livraison: 'En livraison', livree: 'Livrée', annulee: 'Annulée'};
            const paymentLabels = {en_attente: 'En attente', paye: 'Payé'};
            const date = c.date_commande ? new Date(c.date_commande.replace(' ', 'T')).toLocaleString('fr-FR') : '—';
            const rows = lignes.map(l => {
                let image = '';
                if (l.images) {
                    try { const parsed = JSON.parse(l.images); image = Array.isArray(parsed) ? (parsed[0] || '') : l.images; } catch (e) { image = l.images; }
                }
                const imageHtml = image ? '<img src="../' + escapeHtml(image) + '" alt="">' : '';
                const total = Number(l.prix_unitaire) * Number(l.quantite);
                return '<tr><td><div class="details-product">' + imageHtml + '<span>' + escapeHtml(l.produit_nom) + '</span></div></td><td>' + escapeHtml(l.quantite) + ' ' + escapeHtml(l.unite_mesure) + '</td><td>' + Number(l.prix_unitaire).toLocaleString('fr-FR') + ' FC</td><td><strong>' + total.toLocaleString('fr-FR') + ' FC</strong></td></tr>';
            }).join('');

            document.getElementById('commandeDetailsTitle').textContent = 'Commande #' + c.numero_commande;
            document.getElementById('commandeDetailsBody').innerHTML =
                '<div class="details-grid">' +
                '<div class="details-box"><div class="details-box-label">Client</div><strong>' + escapeHtml(c.acheteur_prenom + ' ' + c.acheteur_nom) + '</strong><div>' + escapeHtml(c.acheteur_email || '') + '</div><div>' + escapeHtml(c.acheteur_telephone || '') + '</div></div>' +
                '<div class="details-box"><div class="details-box-label">Commande</div><div>Date : ' + escapeHtml(date) + '</div><div>Statut : <strong>' + escapeHtml(statusLabels[c.statut_commande] || c.statut_commande) + '</strong></div><div>Paiement : <strong>' + escapeHtml(paymentLabels[c.statut_paiement] || c.statut_paiement) + '</strong></div></div>' +
                '<div class="details-box"><div class="details-box-label">Livraison</div><div>' + escapeHtml(c.adresse_livraison || 'Non renseignée') + '</div><small>' + escapeHtml(c.instructions_specifiques || 'Aucune instruction') + '</small></div>' +
                '<div class="details-box"><div class="details-box-label">Total</div><strong style="font-size:1.2rem">' + Number(c.montant_total).toLocaleString('fr-FR') + ' FC</strong><div>' + escapeHtml(c.mode_paiement || 'Mode non renseigné') + '</div></div>' +
                '</div><h5 class="fw-bold mb-3">Produits</h5><div class="table-responsive"><table class="details-products"><thead><tr><th>Produit</th><th>Quantité</th><th>Prix</th><th>Total</th></tr></thead><tbody>' + (rows || '<tr><td colspan="4">Aucun produit</td></tr>') + '</tbody></table></div>';
        }

        function confirmDelete(id, numero) {
            if (confirm('Supprimer la commande #' + numero + ' ? Cette action est irréversible.')) {
                deleteCommande(id);
            }
        }

        async function deleteCommande(id) {
            try {
                const response = await fetch('../models/traitement/commandes-post.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'delete',
                        id: id
                    })
                });
                const result = await response.json();
                showToast(result.message, result.success ? 'success' : 'error');
                if (result.success) setTimeout(() => location.reload(), 500);
            } catch (error) {
                showToast('Erreur réseau', 'error');
            }
        }

        function showToast(message, type) {
            const existing = document.querySelector('.toast');
            if (existing) existing.remove();
            const toast = document.createElement('div');
            toast.className = 'toast toast-' + (type || 'success');
            toast.innerHTML = '<i class="bi ' + (type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill') + '"></i><span>' + message + '</span>';
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }

        function toggleDarkMode() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
        }
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebar-overlay').style.display = document.getElementById('sidebar').classList.contains('open') ? 'block' : 'none';
        }

        <?php if ($toast): ?>
            showToast(<?= json_encode($toast['message'] ?? '') ?>, <?= json_encode($toast['type'] ?? 'success') ?>);
        <?php endif; ?>
    </script>
</body>

</html>
