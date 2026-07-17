<?php
/**
 * views/utilisateurs.php
 * Gestion des utilisateurs - Design cohérent avec le dashboard
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
require_once __DIR__ . '/../models/select/UtilisateurSelect.php';

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
    
    // Récupérer tous les utilisateurs
    $utilisateurs = UtilisateurSelect::getAllUtilisateurs();
    
    // Récupérer les statistiques des rôles
    $statsRoles = [
        'admin' => UtilisateurSelect::countByType('admin'),
        'agriculteur' => UtilisateurSelect::countByType('agriculteur'),
        'livreur' => UtilisateurSelect::countByType('livreur'),
        'acheteur' => UtilisateurSelect::countByType('acheteur')
    ];
    
    // Définir les rôles disponibles
    $roles = [
        ['id' => 1, 'code' => 'admin', 'label' => 'Administrateur', 'description' => 'Administrateur système - Accès complet'],
        ['id' => 2, 'code' => 'agriculteur', 'label' => 'Agriculteur', 'description' => 'Gestion des produits et commandes'],
        ['id' => 3, 'code' => 'livreur', 'label' => 'Livreur', 'description' => 'Gestion des livraisons'],
        ['id' => 4, 'code' => 'acheteur', 'label' => 'Acheteur', 'description' => 'Achat de produits vivriers']
    ];
    
} catch (PDOException $e) {
    error_log("Erreur chargement utilisateurs: " . $e->getMessage());
    $utilisateurs = [];
    $roles = [];
    $statsRoles = ['admin' => 0, 'agriculteur' => 0, 'livreur' => 0, 'acheteur' => 0];
}

// Toast message
$toast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilisateurs - Butembo Marché Numérique</title>

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

        /* ===== TOOLBAR ===== */
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 0.5rem;
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

        .badge-admin {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-agriculteur {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-livreur {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-acheteur {
            background: #e5e7eb;
            color: #374151;
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

        .dark .badge-admin {
            background: #78350f;
            color: #fde68a;
        }

        .dark .badge-agriculteur {
            background: #064e3b;
            color: #a7f3d0;
        }

        .dark .badge-livreur {
            background: #1e3a5f;
            color: #93c5fd;
        }

        .dark .badge-acheteur {
            background: #374151;
            color: #9ca3af;
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

        .user-cell {
            font-weight: 600;
            color: var(--color-text);
        }

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
            /* Bootstrap définit .modal avec display:none. Cette page utilise
               son propre overlay, le contenu doit donc rester affichable. */
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

        .modal-body p {
            font-size: 0.9rem;
            color: var(--color-text-secondary);
            line-height: 1.6;
        }

        .modal-body .highlight {
            font-weight: 600;
            color: var(--color-text);
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
            transition: all var(--transition);
        }

        .form-input:focus,
        .form-select:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(45, 106, 79, 0.15);
        }

        .form-hint {
            font-size: 0.75rem;
            color: var(--color-text-muted);
            margin-top: 0.3rem;
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
            /* Bootstrap masque .toast tant qu'elle n'a pas la classe .show. */
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

        /* ===== CONFIRMATION DELETE MODAL ===== */
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
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ===== STATS ROLES ===== */
        .stats-roles {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .stat-role-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            padding: 0.75rem 1rem;
            text-align: center;
            transition: all var(--transition);
        }

        .stat-role-card:hover {
            border-color: var(--color-primary-border);
            transform: translateY(-2px);
        }

        .stat-role-card .number {
            font-family: var(--font-display);
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-text);
        }

        .stat-role-card .label {
            font-size: 0.7rem;
            color: var(--color-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-role-card .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.25rem;
        }

        .dot-admin { background: #dc3545; }
        .dot-agriculteur { background: #28a745; }
        .dot-livreur { background: #17a2b8; }
        .dot-acheteur { background: #6c757d; }
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
                    <h2 class="page-title">Utilisateurs</h2>
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
                <button class="breadcrumb-item active" data-view="utilisateurs">Utilisateurs</button>
                <span class="breadcrumb-separator">›</span>
                <button class="breadcrumb-item" data-view="roles">Rôles & Permissions</button>
            </nav>

            <!-- Stats Rôles -->
            <div class="stats-roles">
                <div class="stat-role-card">
                    <span class="dot dot-admin"></span>
                    <span class="number"><?= $statsRoles['admin'] ?? 0 ?></span>
                    <div class="label">Administrateurs</div>
                </div>
                <div class="stat-role-card">
                    <span class="dot dot-agriculteur"></span>
                    <span class="number"><?= $statsRoles['agriculteur'] ?? 0 ?></span>
                    <div class="label">Agriculteurs</div>
                </div>
                <div class="stat-role-card">
                    <span class="dot dot-livreur"></span>
                    <span class="number"><?= $statsRoles['livreur'] ?? 0 ?></span>
                    <div class="label">Livreurs</div>
                </div>
                <div class="stat-role-card">
                    <span class="dot dot-acheteur"></span>
                    <span class="number"><?= $statsRoles['acheteur'] ?? 0 ?></span>
                    <div class="label">Acheteurs</div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="toolbar">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" class="search-input" id="tableSearch" placeholder="Rechercher..." onkeyup="filterTable()">
                </div>
                <button class="btn btn-primary" id="btnAddUser" onclick="openUserModal()">
                    <i class="bi bi-plus-circle"></i> Ajouter un utilisateur
                </button>
            </div>

            <!-- Tableau Utilisateurs -->
            <div class="table-card" id="view-utilisateurs">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Téléphone</th>
                                <th>Statut</th>
                                <th style="width:90px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-utilisateurs">
                            <?php if (!empty($utilisateurs) && count($utilisateurs) > 0): ?>
                                <?php foreach ($utilisateurs as $u): ?>
                                    <tr id="user-row-<?= $u->id ?>" data-search="<?= strtolower(htmlspecialchars(($u->nom ?? '') . ' ' . ($u->prenom ?? '') . ' ' . ($u->email ?? '') . ' ' . ($u->type_utilisateur ?? ''))); ?>">
                                        <td>
                                            <div class="user-cell"><?= htmlspecialchars(($u->prenom ?? '') . ' ' . ($u->nom ?? '')) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($u->email ?? '') ?></td>
                                        <td>
                                            <span class="badge badge-<?= $u->type_utilisateur ?? 'acheteur' ?>">
                                                <?= ucfirst($u->type_utilisateur ?? 'Acheteur') ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($u->telephone ?? 'Non renseigné') ?></td>
                                        <td>
                                            <?php 
                                                $statut = $u->statut ?? 'actif';
                                                $badgeClass = 'badge-success';
                                                if ($statut === 'suspendu') $badgeClass = 'badge-warning';
                                                elseif ($statut === 'bloque') $badgeClass = 'badge-danger';
                                            ?>
                                            <span class="badge <?= $badgeClass ?>">
                                                <?= ucfirst($statut) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-btns">
                                                <button class="action-btn" onclick="openEditUserModal(<?= $u->id ?>, '<?= addslashes($u->prenom ?? '') ?>', '<?= addslashes($u->nom ?? '') ?>', '<?= addslashes($u->telephone ?? '') ?>', '<?= $u->type_utilisateur ?? 'acheteur' ?>', '<?= $u->statut ?? 'actif' ?>')" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="action-btn delete" onclick="confirmDeleteUser(<?= $u->id ?? 0 ?>, '<?= addslashes(($u->prenom ?? '') . ' ' . ($u->nom ?? '')) ?>')" title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        <i class="bi bi-people"></i>
                                        <p style="margin-top:0.5rem; font-weight:500;">Aucun utilisateur</p>
                                        <p style="font-size:0.8rem;">Cliquez sur "Ajouter un utilisateur" pour commencer.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tableau Rôles (caché) -->
            <div class="table-card hidden" id="view-roles">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Rôle</th>
                                <th>Description</th>
                                <th>Utilisateurs</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-roles">
                            <?php foreach ($roles as $role):
                                $countUsers = $statsRoles[$role['code']] ?? 0;
                            ?>
                                <tr data-search="<?= strtolower($role['code']); ?>">
                                    <td>
                                        <span class="badge badge-<?= $role['code'] ?>">
                                            <?= $role['label'] ?>
                                        </span>
                                    </td>
                                    <td><?= $role['description'] ?></td>
                                    <td><strong><?= $countUsers ?></strong> utilisateur(s)</td>
                                </tr>
                            <?php endforeach; ?>
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

    <!-- ===== MODAL AJOUT/MODIFICATION ===== -->
    <div class="modal-overlay" id="userModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Ajouter un utilisateur</h3>
                <button type="button" class="modal-close" onclick="closeModal('userModal')">&times;</button>
            </div>
            <form id="userForm" onsubmit="saveUser(event)">
                <input type="hidden" id="userId" name="id">
                <input type="hidden" name="action" id="formAction" value="create">

                <div class="form-group">
                    <label class="form-label" for="prenom">Prénom</label>
                    <input type="text" class="form-input" id="prenom" name="prenom" placeholder="Jean" required minlength="2">
                </div>

                <div class="form-group">
                    <label class="form-label" for="nom">Nom</label>
                    <input type="text" class="form-input" id="nom" name="nom" placeholder="Dupont" required minlength="2">
                </div>

                <div class="form-group">
                    <label class="form-label" for="telephone">Téléphone</label>
                    <input type="tel" class="form-input" id="telephone" name="telephone" placeholder="+243 999 999 999">
                </div>

                <div class="form-group">
                    <label class="form-label" for="type_utilisateur">Rôle</label>
                    <select class="form-select" id="type_utilisateur" name="type_utilisateur" required>
                        <option value="">Sélectionner un rôle</option>
                        <option value="admin">Administrateur</option>
                        <option value="agriculteur">Agriculteur</option>
                        <option value="livreur">Livreur</option>
                        <option value="acheteur">Acheteur</option>
                    </select>
                </div>

                <div class="form-group" id="passwordGroup">
                    <label class="form-label" for="mot_de_passe">Mot de passe</label>
                    <input type="password" class="form-input" id="mot_de_passe" name="mot_de_passe" placeholder="Minimum 4 caractères" minlength="4">
                    <p class="form-hint" id="passwordHint"></p>
                </div>

                <div class="form-group">
                    <label class="form-label" for="statut">Statut</label>
                    <select class="form-select" id="statut" name="statut">
                        <option value="actif">Actif</option>
                        <option value="suspendu">Suspendu</option>
                        <option value="bloque">Bloqué</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('userModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span id="submitBtnText">Créer l'utilisateur</span>
                        <span id="submitBtnSpinner" class="hidden" style="width:16px;height:16px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 0.6s linear infinite;"></span>
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
                <p>Vous êtes sur le point de supprimer l'utilisateur :</p>
                <p class="highlight" id="deleteUserName" style="font-size:1.05rem; margin:0.5rem 0;"></p>
                <p style="font-size:0.8rem; color:var(--color-danger);">Cette action est irréversible. L'utilisateur sera désactivé.</p>
            </div>
            <input type="hidden" id="deleteUserId">
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
        // FONCTIONS GLOBALES - DOIVENT ÊTRE ACCESSIBLES
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

        // ===== USER MODAL (AJOUT) =====
        function openUserModal() {
            // Réinitialiser le formulaire
            var form = document.getElementById('userForm');
            if (form) form.reset();
            
            document.getElementById('userId').value = '';
            document.getElementById('formAction').value = 'create';
            document.getElementById('modalTitle').textContent = 'Ajouter un utilisateur';
            document.getElementById('mot_de_passe').required = true;
            document.getElementById('mot_de_passe').placeholder = 'Minimum 4 caractères';
            document.getElementById('passwordHint').textContent = '';
            document.getElementById('submitBtnText').textContent = 'Créer l\'utilisateur';
            document.getElementById('statut').value = 'actif';
            
            openModal('userModal');
            setTimeout(function() {
                document.getElementById('prenom').focus();
            }, 100);
        }

        // ===== USER MODAL (MODIFICATION) =====
        function openEditUserModal(id, prenom, nom, telephone, type, statut) {
            document.getElementById('modalTitle').textContent = 'Modifier l\'utilisateur';
            document.getElementById('formAction').value = 'update';
            document.getElementById('userId').value = id || '';
            document.getElementById('prenom').value = prenom || '';
            document.getElementById('nom').value = nom || '';
            document.getElementById('telephone').value = telephone || '';
            document.getElementById('type_utilisateur').value = type || '';
            document.getElementById('statut').value = statut || 'actif';
            document.getElementById('mot_de_passe').required = false;
            document.getElementById('mot_de_passe').placeholder = 'Laissez vide pour conserver';
            document.getElementById('passwordHint').textContent = 'Laissez vide pour conserver le mot de passe actuel';
            document.getElementById('submitBtnText').textContent = 'Enregistrer les modifications';
            
            openModal('userModal');
            setTimeout(function() {
                document.getElementById('prenom').focus();
            }, 100);
        }

        // ===== SAVE USER =====
        async function saveUser(event) {
            event.preventDefault();

            var form = document.getElementById('userForm');
            var formData = new FormData(form);
            var data = {};
            formData.forEach(function(value, key) {
                data[key] = value;
            });

            // Si c'est une mise à jour et que le mot de passe est vide, on ne l'envoie pas
            if (data.action === 'update' && !data.mot_de_passe) {
                delete data.mot_de_passe;
            }

            // UI loading
            toggleSubmitLoading(true);

            try {
                var response = await fetch('../models/traitement/utilisateurs-post.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                var result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    closeModal('userModal');
                    setTimeout(function() {
                        location.reload();
                    }, 1600);
                } else {
                    showToast(result.message || 'Erreur lors de l\'enregistrement', 'error');
                }
            } catch (error) {
                console.error('Erreur:', error);
                showToast('Erreur réseau. Veuillez réessayer.', 'error');
            } finally {
                toggleSubmitLoading(false);
            }
        }

        function toggleSubmitLoading(loading) {
            document.getElementById('submitBtnText').classList.toggle('hidden', loading);
            document.getElementById('submitBtnSpinner').classList.toggle('hidden', !loading);
            document.getElementById('submitBtn').disabled = loading;
        }

        // ===== DELETE CONFIRMATION MODAL =====
        var userToDelete = null;

        function confirmDeleteUser(id, name) {
            userToDelete = id;
            document.getElementById('deleteUserId').value = id;
            document.getElementById('deleteUserName').textContent = name;

            document.getElementById('deleteBtnText').classList.remove('hidden');
            document.getElementById('deleteBtnSpinner').classList.add('hidden');
            document.getElementById('confirmDeleteBtn').disabled = false;

            openModal('deleteModal');
        }

        async function executeDelete() {
            if (!userToDelete) return;

            var id = userToDelete;

            document.getElementById('deleteBtnText').classList.add('hidden');
            document.getElementById('deleteBtnSpinner').classList.remove('hidden');
            document.getElementById('confirmDeleteBtn').disabled = true;

            try {
                var response = await fetch('../models/traitement/utilisateurs-post.php', {
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

                    var row = document.getElementById('user-row-' + id);
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
            var activeTbody = currentView === 'utilisateurs' ? 'tbody-utilisateurs' : 'tbody-roles';
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
        var currentView = 'utilisateurs';

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

                document.getElementById('view-utilisateurs').classList.toggle('hidden', view !== 'utilisateurs');
                document.getElementById('view-roles').classList.toggle('hidden', view !== 'roles');
                document.getElementById('btnAddUser').style.display = view === 'utilisateurs' ? '' : 'none';

                document.getElementById('tableSearch').value = '';
                filterTable();
            });
        });

        // Fermer les modals avec Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (document.getElementById('deleteModal').classList.contains('active')) closeModal('deleteModal');
                else if (document.getElementById('userModal').classList.contains('active')) closeModal('userModal');
            }
        });

        // Fermer les modals en cliquant sur l'overlay
        ['userModal', 'deleteModal'].forEach(function(id) {
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
