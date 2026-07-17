<?php
/**
 * views/produits.php
 * Gestion des produits et catégories - Design cohérent avec le dashboard
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

// Vérification admin - Seul l'administrateur peut accéder à cette page
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/select/ProduitSelect.php';
require_once __DIR__ . '/../models/select/CategorieSelect.php';

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$user_nom = $_SESSION['user_nom'];
$user_prenom = $_SESSION['user_prenom'];
$user_email = $_SESSION['user_email'];
$user_type = $_SESSION['user_type'];
$user_photo = $_SESSION['user_photo'] ?? null;
$userInitials = strtoupper(substr($user_nom, 0, 1) . substr($user_prenom, 0, 1));

// Définir le rôle
$role_label = 'Administrateur';
$role_color = 'danger';

// Récupération des données
try {
    $pdo = getDBConnection();
    
    // Récupérer tous les produits
    $produits = ProduitSelect::getAll();
    
    // Récupérer toutes les catégories
    $categories = CategorieSelect::getAll();
    $agriculteurs = fetchAll("SELECT CASE WHEN a.id IS NULL THEN -u.id ELSE a.id END AS selection_id,
            u.nom, u.prenom, a.raison_sociale
        FROM utilisateurs u
        LEFT JOIN agriculteurs a ON a.utilisateur_id = u.id AND a.supprime = 0
        WHERE u.type_utilisateur = 'agriculteur' AND u.supprime = 0
        ORDER BY u.nom, u.prenom");
    
    // Récupérer les statistiques
    $stats = [
        'total_produits' => ProduitSelect::countAll(),
        'total_categories' => count($categories),
        'produits_disponibles' => 0,
        'produits_en_rupture' => 0
    ];
    
    // Compter les produits disponibles et en rupture
    foreach ($produits as $p) {
        if ($p->est_disponible ?? false) {
            $stats['produits_disponibles']++;
        }
        if (($p->quantite_stock ?? 0) <= 0) {
            $stats['produits_en_rupture']++;
        }
    }
    
} catch (PDOException $e) {
    error_log("Erreur chargement produits: " . $e->getMessage());
    $produits = [];
    $categories = [];
    $agriculteurs = [];
    $stats = ['total_produits' => 0, 'total_categories' => 0, 'produits_disponibles' => 0, 'produits_en_rupture' => 0];
}

// Toast message
$toast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);

// Définir les unités de mesure
$unitesMesure = ['kg', 'g', 'tonne', 'piece', 'douzaine', 'litre', 'sac', 'autre'];

function getProduitImages($value) {
    if (empty($value)) return [];
    $decoded = json_decode($value, true);
    return array_values(array_filter(is_array($decoded) ? $decoded : [$value], 'is_string'));
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produits - Butembo Marché Numérique</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
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
            --shadow-lg: 0 20px 40px -12px rgb(0 0 0 / 0.15);
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
            -webkit-font-smoothing: antialiased;
            transition: background-color 0.3s, color 0.3s;
        }

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

        /* ===== MAIN CONTENT ===== */
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
            color: var(--color-text);
        }

        /* ===== BREADCRUMBS ===== */
        .breadcrumbs {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 0.5rem;
        }

        .breadcrumb-item {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--color-text-muted);
            cursor: pointer;
            transition: all var(--transition);
            border: none;
            background: none;
            padding: 0.3rem 0.6rem;
            border-radius: var(--radius-sm);
            font-family: var(--font-sans);
            white-space: nowrap;
        }

        .breadcrumb-item:hover {
            color: var(--color-primary);
            background: var(--color-primary-soft);
        }

        .breadcrumb-item.active {
            color: var(--color-text);
            font-weight: 600;
            cursor: default;
            background: none;
        }

        .breadcrumb-separator {
            color: var(--color-text-muted);
            font-size: 0.8rem;
            user-select: none;
        }

        /* ===== STATS CARDS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }

        .stat-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-xl);
            padding: 1.25rem;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-color: var(--color-primary-border);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .stat-card .stat-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .stat-card .stat-number {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-text);
        }

        .stat-card .stat-label {
            font-size: 0.75rem;
            color: var(--color-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
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
            transition: all var(--transition);
            font-family: var(--font-sans);
        }

        .search-input:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(45, 106, 79, 0.15);
        }

        .search-input::placeholder {
            color: var(--color-text-muted);
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
            transition: all var(--transition);
            letter-spacing: 0.02em;
            border: none;
            font-family: var(--font-sans);
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--color-primary);
            color: #fff;
            box-shadow: 0 2px 4px rgba(45, 106, 79, 0.2);
        }

        .btn-primary:hover {
            background: var(--color-primary-hover);
            box-shadow: 0 4px 8px rgba(45, 106, 79, 0.3);
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

        .btn-danger {
            background: var(--color-danger);
            color: #fff;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);
        }

        .btn-danger:hover {
            background: #dc2626;
            box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3);
            color: #fff;
        }

        .btn-success {
            background: var(--color-success);
            color: #fff;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
        }

        .btn-success:hover {
            background: #059669;
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
            color: #fff;
        }

        /* ===== TABLEAU ===== */
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
            white-space: nowrap;
        }

        td {
            padding: 0.9rem 1.25rem;
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

        tr.fade-out {
            opacity: 0.3;
            pointer-events: none;
            transition: opacity 0.3s;
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

        .action-btns {
            display: flex;
            gap: 0.35rem;
        }

        .action-btn {
            background: none;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            padding: 0.4rem 0.5rem;
            cursor: pointer;
            color: var(--color-text-secondary);
            transition: all var(--transition);
            display: inline-flex;
            align-items: center;
            line-height: 1;
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

        .product-cell { display: flex; align-items: center; gap: 0.75rem; }
        .product-thumbnail { width: 44px; height: 44px; flex: 0 0 44px; border-radius: var(--radius-sm); object-fit: cover; border: 1px solid var(--color-border); background: var(--color-bg); }
        .image-hint { margin-top: 0.35rem; color: var(--color-text-muted); font-size: 0.72rem; }

        /* ===== MODAL ===== */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.25s ease;
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            padding: 1rem;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            display: block;
            position: relative;
            inset: auto;
            height: auto;
            margin: 0;
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-xl);
            padding: 1.75rem;
            width: 100%;
            max-width: 500px;
            box-shadow: var(--shadow-lg);
            transform: translateY(20px) scale(0.97);
            transition: transform 0.25s ease;
            max-height: calc(100vh - 2rem);
            overflow-y: auto;
        }

        .modal-overlay.active .modal {
            transform: translateY(0) scale(1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-family: var(--font-display);
            font-size: 1.15rem;
            font-weight: 700;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.4rem;
            color: var(--color-text-muted);
            cursor: pointer;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            transition: all var(--transition);
            line-height: 1;
        }

        .modal-close:hover {
            background: var(--color-border);
            color: var(--color-text);
        }

        .modal-body {
            margin-bottom: 1.5rem;
        }

        .modal-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--color-text-secondary);
            margin-bottom: 0.4rem;
            letter-spacing: 0.02em;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            font-family: var(--font-sans);
            font-size: 0.875rem;
            color: var(--color-text);
            outline: none;
            transition: all var(--transition);
        }

        .form-textarea {
            min-height: 80px;
            resize: vertical;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(45, 106, 79, 0.15);
        }

        .form-hint {
            font-size: 0.75rem;
            color: var(--color-text-muted);
            margin-top: 0.3rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* ===== TOAST ===== */
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
            width: auto;
            opacity: 1;
            visibility: visible;
            align-items: center;
            gap: 0.65rem;
            animation: slideInRight 0.35s ease-out;
            max-width: 400px;
        }

        .toast span {
            overflow-wrap: anywhere;
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

        .hidden {
            display: none !important;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--color-text);
            cursor: pointer;
        }

        .confirm-icon {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 50%;
            background: #fef2f2;
            color: var(--color-danger);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .confirm-icon i {
            font-size: 2rem;
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
                grid-template-columns: repeat(2, 1fr);
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .modal-overlay {
                padding: 0.75rem;
            }

            .modal {
                max-width: 100%;
                padding: 1.25rem;
                max-height: calc(100vh - 1.5rem);
            }

            .toast {
                right: 0.75rem;
                bottom: 0.75rem;
                left: 0.75rem;
                max-width: none;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div id="sidebar-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:35;" onclick="toggleSidebar()"></div>

    <!-- ===== SIDEBAR ===== -->
    <aside id="sidebar" class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">
                <i class="bi bi-basket2-fill"></i>
            </div>
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
                    <h2 class="page-title">Produits & Catégories</h2>
                </div>
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <button onclick="toggleDarkMode()" class="btn btn-outline-secondary btn-sm" style="border-radius:var(--radius-md);">
                        <i class="bi bi-moon"></i>
                    </button>
                    <span class="badge bg-danger text-white ms-2"><?= $role_label ?></span>
                </div>
            </div>
        </header>

        <div class="page-content">
            <!-- Breadcrumbs -->
            <nav class="breadcrumbs" id="breadcrumbs">
                <button class="breadcrumb-item active" data-view="produits">Produits</button>
                <span class="breadcrumb-separator">›</span>
                <button class="breadcrumb-item" data-view="categories">Catégories</button>
            </nav>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-number"><?= $stats['total_produits'] ?></div>
                            <div class="stat-label">Total Produits</div>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-box-seam"></i>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-number"><?= $stats['produits_disponibles'] ?></div>
                            <div class="stat-label">Disponibles</div>
                        </div>
                        <div class="stat-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-number" style="color: var(--color-danger);"><?= $stats['produits_en_rupture'] ?></div>
                            <div class="stat-label">En Rupture</div>
                        </div>
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-number"><?= $stats['total_categories'] ?></div>
                            <div class="stat-label">Catégories</div>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-tags"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="toolbar">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" class="search-input" id="tableSearch" placeholder="Rechercher un produit..." onkeyup="filterTable()">
                </div>
                <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                    <button class="btn btn-success" id="btnAddCategorie" onclick="openCategorieModal()" style="display:none;">
                        <i class="bi bi-plus-circle"></i> Ajouter une catégorie
                    </button>
                    <button class="btn btn-primary" id="btnAddProduit" onclick="openProduitModal()">
                        <i class="bi bi-plus-circle"></i> Ajouter un produit
                    </button>
                </div>
            </div>

            <!-- Tableau Produits -->
            <div class="table-card" id="view-produits">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Catégorie</th>
                                <th>Prix (FC)</th>
                                <th>Unité</th>
                                <th>Stock</th>
                                <th>Statut</th>
                                <th style="width:90px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-produits">
                            <?php if (!empty($produits) && count($produits) > 0): ?>
                                <?php foreach ($produits as $p): ?>
                                    <tr id="produit-row-<?= $p->id ?>" data-search="<?= strtolower(htmlspecialchars(($p->nom ?? '') . ' ' . ($p->categorie_nom ?? '') . ' ' . ($p->origine ?? ''))); ?>">
                                        <td>
                                            <?php $produitImages = getProduitImages($p->images ?? null); ?>
                                            <div class="product-cell">
                                                <?php if (!empty($produitImages[0])): ?>
                                                    <img class="product-thumbnail" src="../<?= htmlspecialchars($produitImages[0]) ?>" alt="<?= htmlspecialchars($p->nom ?? '') ?>">
                                                <?php endif; ?>
                                                <div>
                                            <div class="user-cell"><?= htmlspecialchars($p->nom ?? '') ?></div>
                                            <?php if (!empty($p->origine)): ?>
                                                <div style="font-size:0.7rem; color:var(--color-text-muted);">
                                                    <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($p->origine) ?>
                                                </div>
                                            <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($p->categorie_nom ?? 'Non catégorisé') ?></td>
                                        <td style="font-weight:600;"><?= number_format($p->prix_unitaire ?? 0, 0, ',', ' ') ?></td>
                                        <td><?= htmlspecialchars($p->unite_mesure ?? '') ?></td>
                                        <td>
                                            <?php 
                                                $stock = $p->quantite_stock ?? 0;
                                                $stockClass = $stock <= 0 ? 'text-danger' : ($stock < 10 ? 'text-warning' : 'text-success');
                                            ?>
                                            <span class="<?= $stockClass ?> fw-bold"><?= number_format($stock, 0, ',', ' ') ?></span>
                                        </td>
                                        <td>
                                            <?php if (($p->est_disponible ?? false) && ($p->quantite_stock ?? 0) > 0): ?>
                                                <span class="badge badge-success">Disponible</span>
                                            <?php elseif (($p->est_disponible ?? false) && ($p->quantite_stock ?? 0) <= 0): ?>
                                                <span class="badge badge-danger">Rupture</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Indisponible</span>
                                            <?php endif; ?>
                                            <?php if ($p->est_bio ?? false): ?>
                                                <span class="badge badge-info ms-1">Bio</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-btns">
                                                <button class="action-btn" onclick='openEditProduitModal(<?= json_encode($p->id) ?>, <?= json_encode($p->nom ?? '', JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= json_encode($p->description ?? '', JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= json_encode((float) ($p->prix_unitaire ?? 0)) ?>, <?= json_encode($p->unite_mesure ?? '') ?>, <?= json_encode((float) ($p->quantite_stock ?? 0)) ?>, <?= json_encode($p->categorie_id ?? 0) ?>, <?= json_encode($p->agriculteur_id ?? 0) ?>, <?= json_encode($p->origine ?? '', JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= !empty($p->est_bio) ? 'true' : 'false' ?>, <?= !empty($p->est_disponible) ? 'true' : 'false' ?>)' title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="action-btn delete" onclick="confirmDeleteProduit(<?= $p->id ?? 0 ?>, '<?= addslashes($p->nom ?? '') ?>')" title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="empty-state">
                                        <i class="bi bi-box-seam"></i>
                                        <p style="margin-top:0.5rem; font-weight:500;">Aucun produit</p>
                                        <p style="font-size:0.8rem;">Cliquez sur "Ajouter un produit" pour commencer.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tableau Catégories (caché) -->
            <div class="table-card hidden" id="view-categories">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Catégorie</th>
                                <th>Description</th>
                                <th>Produits</th>
                                <th style="width:90px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-categories">
                            <?php if (!empty($categories) && count($categories) > 0): ?>
                                <?php foreach ($categories as $c): ?>
                                    <tr id="categorie-row-<?= $c->id ?>" data-search="<?= strtolower(htmlspecialchars($c->nom ?? '')); ?>">
                                        <td>
                                            <div class="user-cell"><?= htmlspecialchars($c->nom ?? '') ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($c->description ?? '') ?></td>
                                        <td>
                                            <?php 
                                                $nbProduits = 0;
                                                foreach ($produits as $p) {
                                                    if (($p->categorie_id ?? 0) == $c->id) $nbProduits++;
                                                }
                                            ?>
                                            <span class="fw-bold"><?= $nbProduits ?></span>
                                        </td>
                                        <td>
                                            <div class="action-btns">
                                                <button class="action-btn" onclick="openEditCategorieModal(<?= $c->id ?>, '<?= addslashes($c->nom ?? '') ?>', '<?= addslashes($c->description ?? '') ?>')" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="action-btn delete" onclick="confirmDeleteCategorie(<?= $c->id ?? 0 ?>, '<?= addslashes($c->nom ?? '') ?>')" title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="empty-state">
                                        <i class="bi bi-tags"></i>
                                        <p style="margin-top:0.5rem; font-weight:500;">Aucune catégorie</p>
                                        <p style="font-size:0.8rem;">Cliquez sur "Ajouter une catégorie" pour commencer.</p>
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
            <span>Version 1.0 · PHP 8.0+</span>
        </footer>
    </div>

    <!-- ===== MODAL PRODUIT ===== -->
    <div class="modal-overlay" id="produitModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="modalProduitTitle">Ajouter un produit</h3>
                <button type="button" class="modal-close" onclick="closeModal('produitModal')">&times;</button>
            </div>
            <form id="produitForm" onsubmit="saveProduit(event)">
                <input type="hidden" id="produitId" name="id">
                <input type="hidden" name="action" id="produitAction" value="create">

                <div class="form-group">
                    <label class="form-label" for="produit_nom">Nom du produit *</label>
                    <input type="text" class="form-input" id="produit_nom" name="nom" placeholder="Farine de manioc" required minlength="2">
                </div>

                <div class="form-group">
                    <label class="form-label" for="produit_description">Description</label>
                    <textarea class="form-textarea" id="produit_description" name="description" placeholder="Description du produit..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="produit_agriculteur">Agriculteur *</label>
                    <select class="form-select" id="produit_agriculteur" name="agriculteur_id" required>
                        <option value="">Sélectionner un agriculteur</option>
                        <?php foreach ($agriculteurs as $a): ?>
                            <option value="<?= $a->selection_id ?>"><?= htmlspecialchars(trim(($a->prenom ?? '') . ' ' . ($a->nom ?? '')) . (!empty($a->raison_sociale) ? ' — ' . $a->raison_sociale : '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="produit_categorie">Catégorie *</label>
                        <select class="form-select" id="produit_categorie" name="categorie_id" required>
                            <option value="">Sélectionner une catégorie</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c->id ?>"><?= htmlspecialchars($c->nom) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="produit_prix">Prix unitaire (FC) *</label>
                        <input type="number" class="form-input" id="produit_prix" name="prix_unitaire" placeholder="2500" required min="0" step="100">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="produit_unite">Unité de mesure *</label>
                        <select class="form-select" id="produit_unite" name="unite_mesure" required>
                            <option value="">Sélectionner une unité</option>
                            <?php foreach ($unitesMesure as $u): ?>
                                <option value="<?= $u ?>"><?= ucfirst($u) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="produit_stock">Quantité en stock *</label>
                        <input type="number" class="form-input" id="produit_stock" name="quantite_stock" placeholder="100" required min="0" step="1">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="produit_origine">Origine</label>
                    <input type="text" class="form-input" id="produit_origine" name="origine" placeholder="Butembo">
                </div>

                <div class="form-group">
                    <label class="form-label" for="produit_images">Images du produit (maximum 3)</label>
                    <input type="file" class="form-input" id="produit_images" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
                    <p class="image-hint" id="produitImagesHint">JPG, PNG ou WEBP — 5 Mo maximum par image.</p>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="produit_bio">Bio</label>
                        <select class="form-select" id="produit_bio" name="est_bio">
                            <option value="0">Non</option>
                            <option value="1">Oui</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="produit_disponible">Disponible</label>
                        <select class="form-select" id="produit_disponible" name="est_disponible">
                            <option value="1">Oui</option>
                            <option value="0">Non</option>
                        </select>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('produitModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submitProduitBtn">
                        <span id="submitProduitText">Créer le produit</span>
                        <span id="submitProduitSpinner" class="hidden" style="width:16px;height:16px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 0.6s linear infinite;"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== MODAL CATÉGORIE ===== -->
    <div class="modal-overlay" id="categorieModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="modalCategorieTitle">Ajouter une catégorie</h3>
                <button type="button" class="modal-close" onclick="closeModal('categorieModal')">&times;</button>
            </div>
            <form id="categorieForm" onsubmit="saveCategorie(event)">
                <input type="hidden" id="categorieId" name="id">
                <input type="hidden" name="action" id="categorieAction" value="create">

                <div class="form-group">
                    <label class="form-label" for="categorie_nom">Nom de la catégorie *</label>
                    <input type="text" class="form-input" id="categorie_nom" name="nom" placeholder="Légumes" required minlength="2">
                </div>

                <div class="form-group">
                    <label class="form-label" for="categorie_description">Description</label>
                    <textarea class="form-textarea" id="categorie_description" name="description" placeholder="Description de la catégorie..."></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('categorieModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submitCategorieBtn">
                        <span id="submitCategorieText">Créer la catégorie</span>
                        <span id="submitCategorieSpinner" class="hidden" style="width:16px;height:16px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 0.6s linear infinite;"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== MODAL CONFIRMATION SUPPRESSION ===== -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal" style="max-width:420px; text-align:center;">
            <div class="confirm-icon">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="modal-header" style="justify-content:center;">
                <h3 class="modal-title">Confirmer la suppression</h3>
            </div>
            <div class="modal-body">
                <p>Vous êtes sur le point de supprimer :</p>
                <p class="highlight" id="deleteItemName" style="font-size:1.05rem; margin:0.5rem 0;"></p>
                <p style="font-size:0.8rem; color:var(--color-danger);">Cette action est irréversible.</p>
            </div>
            <input type="hidden" id="deleteItemId">
            <input type="hidden" id="deleteItemType">
            <div class="modal-actions" style="justify-content:center;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="executeDelete()">
                    <span id="deleteBtnText">Supprimer</span>
                    <span id="deleteBtnSpinner" class="hidden" style="width:16px;height:16px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 0.6s linear infinite;"></span>
                </button>
            </div>
        </div>
    </div>

    <script>
        // ============================================
        // FONCTIONS GLOBALES
        // ============================================
        
        // ===== MODALS =====
        function openModal(modalId) {
            var modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeModal(modalId) {
            var modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        // ===== PRODUIT MODAL =====
        function openProduitModal() {
            var form = document.getElementById('produitForm');
            if (form) form.reset();
            
            document.getElementById('produitId').value = '';
            document.getElementById('produitAction').value = 'create';
            document.getElementById('modalProduitTitle').textContent = 'Ajouter un produit';
            document.getElementById('submitProduitText').textContent = 'Créer le produit';
            document.getElementById('produit_stock').value = 0;
            document.getElementById('produit_bio').value = 0;
            document.getElementById('produit_disponible').value = 1;
            document.getElementById('produitImagesHint').textContent = 'JPG, PNG ou WEBP — 5 Mo maximum par image.';
            
            openModal('produitModal');
            setTimeout(function() {
                document.getElementById('produit_nom').focus();
            }, 100);
        }

        function openEditProduitModal(id, nom, description, prix, unite, stock, categorie, agriculteur, origine, bio, disponible) {
            document.getElementById('modalProduitTitle').textContent = 'Modifier le produit';
            document.getElementById('produitAction').value = 'update';
            document.getElementById('produitId').value = id || '';
            document.getElementById('produit_nom').value = nom || '';
            document.getElementById('produit_description').value = description || '';
            document.getElementById('produit_prix').value = prix || 0;
            document.getElementById('produit_unite').value = unite || '';
            document.getElementById('produit_stock').value = stock || 0;
            document.getElementById('produit_categorie').value = categorie || '';
            document.getElementById('produit_agriculteur').value = agriculteur || '';
            document.getElementById('produit_origine').value = origine || '';
            document.getElementById('produit_bio').value = bio ? 1 : 0;
            document.getElementById('produit_disponible').value = disponible ? 1 : 0;
            document.getElementById('submitProduitText').textContent = 'Enregistrer les modifications';
            document.getElementById('produit_images').value = '';
            document.getElementById('produitImagesHint').textContent = 'Laissez vide pour conserver les images actuelles, ou choisissez jusqu’à 3 nouvelles images.';
            
            openModal('produitModal');
            setTimeout(function() {
                document.getElementById('produit_nom').focus();
            }, 100);
        }

        async function saveProduit(event) {
            event.preventDefault();

            var form = document.getElementById('produitForm');
            var formData = new FormData(form);
            var files = document.getElementById('produit_images').files;
            if (files.length > 3) {
                showToast('Vous pouvez sélectionner au maximum 3 images.', 'error');
                return;
            }

            toggleProduitLoading(true);

            try {
                var response = await fetch('../models/traitement/produits-post.php', {
                    method: 'POST',
                    body: formData
                });

                var result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    closeModal('produitModal');
                    setTimeout(function() {
                        location.reload();
                    }, 800);
                } else {
                    showToast(result.message || 'Erreur lors de l\'enregistrement', 'error');
                }
            } catch (error) {
                console.error('Erreur:', error);
                showToast('Erreur réseau. Veuillez réessayer.', 'error');
            } finally {
                toggleProduitLoading(false);
            }
        }

        function toggleProduitLoading(loading) {
            document.getElementById('submitProduitText').classList.toggle('hidden', loading);
            document.getElementById('submitProduitSpinner').classList.toggle('hidden', !loading);
            document.getElementById('submitProduitBtn').disabled = loading;
        }

        // ===== CATEGORIE MODAL =====
        function openCategorieModal() {
            var form = document.getElementById('categorieForm');
            if (form) form.reset();
            
            document.getElementById('categorieId').value = '';
            document.getElementById('categorieAction').value = 'create';
            document.getElementById('modalCategorieTitle').textContent = 'Ajouter une catégorie';
            document.getElementById('submitCategorieText').textContent = 'Créer la catégorie';
            
            openModal('categorieModal');
            setTimeout(function() {
                document.getElementById('categorie_nom').focus();
            }, 100);
        }

        function openEditCategorieModal(id, nom, description) {
            document.getElementById('modalCategorieTitle').textContent = 'Modifier la catégorie';
            document.getElementById('categorieAction').value = 'update';
            document.getElementById('categorieId').value = id || '';
            document.getElementById('categorie_nom').value = nom || '';
            document.getElementById('categorie_description').value = description || '';
            document.getElementById('submitCategorieText').textContent = 'Enregistrer les modifications';
            
            openModal('categorieModal');
            setTimeout(function() {
                document.getElementById('categorie_nom').focus();
            }, 100);
        }

        async function saveCategorie(event) {
            event.preventDefault();

            var form = document.getElementById('categorieForm');
            var formData = new FormData(form);
            var data = {};
            formData.forEach(function(value, key) {
                data[key] = value;
            });

            toggleCategorieLoading(true);

            try {
                var response = await fetch('../models/traitement/categories-post.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                var result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    closeModal('categorieModal');
                    setTimeout(function() {
                        location.reload();
                    }, 800);
                } else {
                    showToast(result.message || 'Erreur lors de l\'enregistrement', 'error');
                }
            } catch (error) {
                console.error('Erreur:', error);
                showToast('Erreur réseau. Veuillez réessayer.', 'error');
            } finally {
                toggleCategorieLoading(false);
            }
        }

        function toggleCategorieLoading(loading) {
            document.getElementById('submitCategorieText').classList.toggle('hidden', loading);
            document.getElementById('submitCategorieSpinner').classList.toggle('hidden', !loading);
            document.getElementById('submitCategorieBtn').disabled = loading;
        }

        // ===== DELETE =====
        var deleteItemId = null;
        var deleteItemType = null;

        function confirmDeleteProduit(id, name) {
            deleteItemId = id;
            deleteItemType = 'produit';
            document.getElementById('deleteItemId').value = id;
            document.getElementById('deleteItemName').textContent = name || 'ce produit';
            document.getElementById('deleteItemType').value = 'produit';

            document.getElementById('deleteBtnText').classList.remove('hidden');
            document.getElementById('deleteBtnSpinner').classList.add('hidden');
            document.getElementById('confirmDeleteBtn').disabled = false;

            openModal('deleteModal');
        }

        function confirmDeleteCategorie(id, name) {
            deleteItemId = id;
            deleteItemType = 'categorie';
            document.getElementById('deleteItemId').value = id;
            document.getElementById('deleteItemName').textContent = name || 'cette catégorie';
            document.getElementById('deleteItemType').value = 'categorie';

            document.getElementById('deleteBtnText').classList.remove('hidden');
            document.getElementById('deleteBtnSpinner').classList.add('hidden');
            document.getElementById('confirmDeleteBtn').disabled = false;

            openModal('deleteModal');
        }

        async function executeDelete() {
            if (!deleteItemId) return;

            var id = deleteItemId;
            var type = deleteItemType;

            document.getElementById('deleteBtnText').classList.add('hidden');
            document.getElementById('deleteBtnSpinner').classList.remove('hidden');
            document.getElementById('confirmDeleteBtn').disabled = true;

            try {
                var endpoint = type === 'categorie' 
                    ? '../models/traitement/categories-post.php'
                    : '../models/traitement/produits-post.php';
                
                var response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'delete',
                        id: id
                    })
                });

                var result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    closeModal('deleteModal');

                    var rowId = type === 'categorie' ? 'categorie-row-' + id : 'produit-row-' + id;
                    var row = document.getElementById(rowId);
                    if (row) {
                        row.classList.add('fade-out');
                        setTimeout(function() {
                            location.reload();
                        }, 500);
                    } else {
                        setTimeout(function() {
                            location.reload();
                        }, 400);
                    }
                } else {
                    showToast(result.message || 'Erreur lors de la suppression', 'error');
                    document.getElementById('deleteBtnText').classList.remove('hidden');
                    document.getElementById('deleteBtnSpinner').classList.add('hidden');
                    document.getElementById('confirmDeleteBtn').disabled = false;
                }
            } catch (error) {
                console.error('Erreur:', error);
                showToast('Erreur réseau. Veuillez réessayer.', 'error');
                document.getElementById('deleteBtnText').classList.remove('hidden');
                document.getElementById('deleteBtnSpinner').classList.add('hidden');
                document.getElementById('confirmDeleteBtn').disabled = false;
            }
        }

        // ===== FILTRE TABLEAU =====
        function filterTable() {
            var query = document.getElementById('tableSearch').value.toLowerCase().trim();
            var activeTbody = currentView === 'produits' ? 'tbody-produits' : 'tbody-categories';
            var rows = document.querySelectorAll('#' + activeTbody + ' tr');

            rows.forEach(function(row) {
                if (row.querySelector('.empty-state')) return;
                var searchData = row.dataset.search || '';
                var match = !query || searchData.indexOf(query) !== -1;
                row.style.display = match ? '' : 'none';
            });
        }

        // ===== TOAST =====
        function showToast(message, type) {
            type = type || 'success';
            var existing = document.querySelector('.toast');
            if (existing) existing.remove();

            var toast = document.createElement('div');
            toast.className = 'toast show toast-' + type;

            var icon = document.createElement('i');
            icon.className = 'bi ' + (type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill');
            var text = document.createElement('span');
            text.textContent = message;
            toast.appendChild(icon);
            toast.appendChild(text);
            document.body.appendChild(toast);
            setTimeout(function() {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s';
                setTimeout(function() {
                    toast.remove();
                }, 300);
            }, 3500);
        }

        // ===== DARK MODE =====
        function toggleDarkMode() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
        }

        // ===== SIDEBAR MOBILE =====
        function toggleSidebar() {
            var sidebar = document.getElementById('sidebar');
            var overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.toggle('open');
            overlay.style.display = sidebar.classList.contains('open') ? 'block' : 'none';
        }

        // ============================================
        // INITIALISATION
        // ============================================
        var currentView = 'produits';

        // Restaurer le thème
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }

        // Breadcrumbs
        document.querySelectorAll('.breadcrumb-item').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var view = this.dataset.view;
                if (view === currentView) return;

                document.querySelectorAll('.breadcrumb-item').forEach(function(b) {
                    b.classList.remove('active');
                });
                this.classList.add('active');
                currentView = view;

                document.getElementById('view-produits').classList.toggle('hidden', view !== 'produits');
                document.getElementById('view-categories').classList.toggle('hidden', view !== 'categories');
                document.getElementById('btnAddProduit').style.display = view === 'produits' ? '' : 'none';
                document.getElementById('btnAddCategorie').style.display = view === 'categories' ? '' : 'none';

                document.getElementById('tableSearch').placeholder = view === 'produits' ? 'Rechercher un produit...' : 'Rechercher une catégorie...';
                document.getElementById('tableSearch').value = '';
                filterTable();
            });
        });

        // Fermer les modals avec Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (document.getElementById('deleteModal').classList.contains('active')) closeModal('deleteModal');
                else if (document.getElementById('produitModal').classList.contains('active')) closeModal('produitModal');
                else if (document.getElementById('categorieModal').classList.contains('active')) closeModal('categorieModal');
            }
        });

        // Fermer les modals en cliquant sur l'overlay
        ['produitModal', 'categorieModal', 'deleteModal'].forEach(function(id) {
            document.getElementById(id).addEventListener('click', function(e) {
                if (e.target === this) closeModal(id);
            });
        });

        // Toast PHP
        <?php if ($toast): ?>
            showToast(<?= json_encode($toast['message'] ?? '', JSON_UNESCAPED_UNICODE) ?>, <?= json_encode($toast['type'] ?? 'success') ?>);
        <?php endif; ?>
    </script>
</body>

</html>
