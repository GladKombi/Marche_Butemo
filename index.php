<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$registrationEmail = $_SESSION['registration_email'] ?? null;
unset($_SESSION['registration_email']);

/**
 * Page d'accueil dynamique - Marché Numérique de Butembo
 * Version avec données réelles provenant de la base de données
 */

// Inclusion des modèles - Vérification des chemins
require_once __DIR__ . '/config/database.php';

// Vérifier si les fichiers existent avant de les inclure
$modelFiles = [
    'ProduitSelect' => __DIR__ . '/models/select/ProduitSelect.php',
    'CategorieSelect' => __DIR__ . '/models/select/CategorieSelect.php',
    'UtilisateurSelect' => __DIR__ . '/models/select/UtilisateurSelect.php',
    'CommandeSelect' => __DIR__ . '/models/select/CommandeSelect.php'
];

foreach ($modelFiles as $name => $path) {
    if (file_exists($path)) {
        require_once $path;
    } else {
        // Créer un fichier temporaire avec une classe minimale si le fichier n'existe pas
        if ($name === 'CommandeSelect') {
            // Définir une classe temporaire
            class CommandeSelect
            {
                public static function countAll()
                {
                    return 0;
                }
                public static function getRecentWithStatus($limit)
                {
                    return [];
                }
            }
        }
        if ($name === 'UtilisateurSelect') {
            // Définir une classe temporaire
            class UtilisateurSelect
            {
                public static function countByType($type)
                {
                    return 0;
                }
                public static function getTestimonials($limit)
                {
                    return [];
                }
                public static function getStats()
                {
                    return (object)['total' => 0];
                }
            }
        }
        if ($name === 'CategorieSelect') {
            class CategorieSelect
            {
                public static function getAllWithCount()
                {
                    return [];
                }
            }
        }
        if ($name === 'ProduitSelect') {
            class ProduitSelect
            {
                public static function getBestSellers($limit)
                {
                    return [];
                }
            }
        }
    }
}

// Récupération des données
$produitsVedette = [];
$categories = [];
$temoignages = [];
$stats = [
    'clients' => 0,
    'commandes' => 0,
    'agriculteurs' => 0
];

// Vérifier si les classes existent avant de les utiliser
if (class_exists('ProduitSelect')) {
    $produitsVedette = ProduitSelect::getBestSellers(8);
}

if (class_exists('CategorieSelect')) {
    $categories = CategorieSelect::getAllWithCount();
}

if (class_exists('UtilisateurSelect')) {
    $temoignages = UtilisateurSelect::getTestimonials(3);
    $stats['clients'] = UtilisateurSelect::countByType('acheteur');
    $stats['agriculteurs'] = UtilisateurSelect::countByType('agriculteur');
}

if (class_exists('CommandeSelect')) {
    $stats['commandes'] = CommandeSelect::countAll();
}

// Si aucune donnée n'est disponible, utiliser des données de démonstration
if (empty($categories)) {
    $categories = [
        (object) ['id' => 1, 'nom' => 'Manioc', 'nb_produits' => 12],
        (object) ['id' => 2, 'nom' => 'Maïs', 'nb_produits' => 8],
        (object) ['id' => 3, 'nom' => 'Haricots', 'nb_produits' => 6],
        (object) ['id' => 4, 'nom' => 'Légumes frais', 'nb_produits' => 15],
        (object) ['id' => 5, 'nom' => 'Fruits', 'nb_produits' => 10],
        (object) ['id' => 6, 'nom' => 'Poisson / Viande', 'nb_produits' => 7]
    ];
}

if (empty($produitsVedette)) {
    $produitsVedette = [
        (object) ['id' => 1, 'nom' => 'Farine de manioc', 'prix_unitaire' => 2500, 'origine' => 'Butembo', 'note_moyenne' => 4.5, 'nb_avis' => 24, 'images' => null, 'categorie_nom' => 'Manioc'],
        (object) ['id' => 2, 'nom' => 'Maïs frais', 'prix_unitaire' => 1800, 'origine' => 'Butembo', 'note_moyenne' => 4.0, 'nb_avis' => 18, 'images' => null, 'categorie_nom' => 'Maïs'],
        (object) ['id' => 3, 'nom' => 'Haricots rouges', 'prix_unitaire' => 3200, 'origine' => 'Butembo', 'note_moyenne' => 4.8, 'nb_avis' => 31, 'images' => null, 'categorie_nom' => 'Haricots'],
        (object) ['id' => 4, 'nom' => 'Légumes variés', 'prix_unitaire' => 1200, 'origine' => 'Butembo', 'note_moyenne' => 4.2, 'nb_avis' => 22, 'images' => null, 'categorie_nom' => 'Légumes frais'],
        (object) ['id' => 5, 'nom' => 'Bananes plantain', 'prix_unitaire' => 1500, 'origine' => 'Butembo', 'note_moyenne' => 4.1, 'nb_avis' => 15, 'images' => null, 'categorie_nom' => 'Fruits'],
        (object) ['id' => 6, 'nom' => 'Poisson frais', 'prix_unitaire' => 4500, 'origine' => 'Butembo', 'note_moyenne' => 4.7, 'nb_avis' => 28, 'images' => null, 'categorie_nom' => 'Poisson / Viande'],
        (object) ['id' => 7, 'nom' => 'Œufs frais', 'prix_unitaire' => 1000, 'origine' => 'Butembo', 'note_moyenne' => 4.3, 'nb_avis' => 19, 'images' => null, 'categorie_nom' => 'Poisson / Viande'],
        (object) ['id' => 8, 'nom' => 'Huile de palme', 'prix_unitaire' => 3800, 'origine' => 'Butembo', 'note_moyenne' => 4.6, 'nb_avis' => 33, 'images' => null, 'categorie_nom' => 'Produits transformés']
    ];
}

if (empty($temoignages)) {
    $temoignages = [
        (object) ['nom' => 'Jean', 'prenom' => 'M.', 'photo_profil' => null, 'type_utilisateur' => 'acheteur', 'commentaire' => 'La qualité des produits est exceptionnelle. Je reçois mes commandes en moins de 24h. Le marché numérique de Butembo a changé ma façon de faire mes courses !'],
        (object) ['nom' => 'Marie', 'prenom' => 'K.', 'photo_profil' => null, 'type_utilisateur' => 'agriculteur', 'commentaire' => 'Grâce à cette plateforme, je vends mes produits directement aux consommateurs. C\'est simple, rapide et je gagne mieux ma vie. Une vraie révolution pour nous agriculteurs !'],
        (object) ['nom' => 'Pierre', 'prenom' => 'L.', 'photo_profil' => null, 'type_utilisateur' => 'livreur', 'commentaire' => 'Je suis livreur sur la plateforme et c\'est une expérience enrichissante. Les clients sont satisfaits et je contribue à dynamiser l\'économie locale de Butembo.']
    ];
}

// Définition des icônes pour les catégories
$categoryIcons = [
    'Manioc' => 'bi-cup-straw',
    'Maïs' => 'bi-seedling',
    'Haricots' => 'bi-droplet',
    'Légumes frais' => 'bi-tree',
    'Fruits' => 'bi-apple',
    'Poisson' => 'bi-fish',
    'Viande' => 'bi-egg-fried',
    'default' => 'bi-box'
];

// ===== DÉFINITION DES COULEURS =====
$productColors = [
    '#E8DDD2',
    '#D4E2D4',
    '#E8D5C4',
    '#C4D4C4',
    '#E8C8B8',
    '#D4E8D4',
    '#E8DDD2',
    '#C8D4C8',
    '#F0E8DF',
    '#D4E8E0',
    '#E8E0D4',
    '#C8D4C8'
];

// Fonction pour générer les étoiles
function renderStars($rating, $count = 0)
{
    $full = floor($rating);
    $half = ($rating - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;

    $html = '';
    for ($i = 0; $i < $full; $i++) $html .= '★';
    if ($half) $html .= '☆';
    for ($i = 0; $i < $empty; $i++) $html .= '☆';

    if ($count > 0) {
        $html .= ' <span>(' . $count . ')</span>';
    }

    return $html;
}

// Fonction pour générer les initiales
function getInitials($nom, $prenom)
{
    return strtoupper(substr($nom, 0, 1) . substr($prenom, 0, 1));
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Butembo Marché Numérique - Produits Vivriers</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ===== VARIABLES & RESET ===== */
        :root {
            --primary-green: #2d6a4f;
            --primary-green-light: #40916c;
            --primary-green-dark: #1b4332;
            --earth-brown: #8d6e4e;
            --earth-light: #d4b896;
            --cream: #f8f3ee;
            --warm-white: #fefcf9;
            --dark-text: #2d2a24;
            --shadow-sm: 0 2px 8px rgba(45, 42, 36, 0.06);
            --shadow-md: 0 4px 20px rgba(45, 42, 36, 0.08);
            --shadow-lg: 0 8px 40px rgba(45, 42, 36, 0.12);
            --radius-sm: 12px;
            --radius-md: 16px;
            --radius-lg: 24px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--warm-white);
            color: var(--dark-text);
            padding-top: 72px;
            line-height: 1.6;
        }

        /* ===== SCROLLBAR ===== */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--cream);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-green-light);
            border-radius: 10px;
        }

        /* ===== BUTTONS ===== */
        .btn {
            border-radius: 50px;
            padding: 10px 28px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.25s ease;
            letter-spacing: 0.01em;
        }

        .btn-primary {
            background: var(--primary-green);
            border-color: var(--primary-green);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--primary-green-dark);
            border-color: var(--primary-green-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-outline-primary {
            border-color: var(--primary-green);
            color: var(--primary-green);
        }

        .btn-outline-primary:hover {
            background: var(--primary-green);
            border-color: var(--primary-green);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-success {
            background: var(--primary-green-light);
            border-color: var(--primary-green-light);
        }

        .btn-success:hover {
            background: var(--primary-green);
            border-color: var(--primary-green);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-outline-secondary {
            border-color: var(--earth-brown);
            color: var(--earth-brown);
        }

        .btn-outline-secondary:hover {
            background: var(--earth-brown);
            border-color: var(--earth-brown);
            color: #fff;
            transform: translateY(-2px);
        }

        /* ===== NAVBAR ===== */
        .navbar {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 1px 20px rgba(0, 0, 0, 0.04);
            padding: 12px 0;
            transition: all 0.3s ease;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.25rem;
            color: var(--primary-green) !important;
            letter-spacing: -0.02em;
        }

        .navbar-brand i {
            color: var(--earth-brown);
            margin-right: 8px;
        }

        .navbar .badge-cart {
            background: var(--primary-green);
            color: #fff;
            border-radius: 50px;
            padding: 6px 14px;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            cursor: pointer;
        }

        .navbar .badge-cart:hover {
            transform: scale(1.05);
            background: var(--primary-green-dark);
        }

        .navbar .badge-cart i {
            margin-right: 4px;
        }

        .nav-link {
            font-weight: 500;
            color: var(--dark-text) !important;
            opacity: 0.7;
            transition: opacity 0.2s ease;
        }

        .nav-link:hover {
            opacity: 1;
            color: var(--primary-green) !important;
        }

        /* ===== HERO ===== */
        .hero {
            background: linear-gradient(145deg, #f8f3ee 0%, #f0e8df 100%);
            padding: 60px 0 70px;
            border-radius: 0 0 40px 40px;
            position: relative;
            overflow: hidden;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -40px;
            right: -40px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(45, 106, 79, 0.04) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero h1 {
            font-weight: 800;
            font-size: clamp(2.2rem, 5vw, 3.8rem);
            line-height: 1.1;
            letter-spacing: -0.03em;
            color: var(--primary-green-dark);
        }

        .hero h1 .highlight {
            color: var(--earth-brown);
            position: relative;
        }

        .hero .lead {
            font-size: 1.1rem;
            color: #5a524a;
            max-width: 500px;
            font-weight: 400;
        }

        .hero .btn-group {
            gap: 12px;
            flex-wrap: wrap;
        }

        .hero-illustration {
            max-width: 100%;
            height: auto;
            filter: drop-shadow(0 8px 30px rgba(45, 42, 36, 0.08));
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-12px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        /* ===== SEARCH BAR ===== */
        .search-section {
            margin-top: -30px;
            position: relative;
            z-index: 10;
        }

        .search-wrapper {
            background: #fff;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            padding: 8px;
            transition: box-shadow 0.3s ease;
        }

        .search-wrapper:focus-within {
            box-shadow: 0 8px 40px rgba(45, 106, 79, 0.15);
        }

        .search-wrapper .form-control,
        .search-wrapper .form-select {
            border: none;
            padding: 12px 16px;
            font-size: 0.95rem;
            background: transparent;
            border-radius: var(--radius-sm);
        }

        .search-wrapper .form-control:focus,
        .search-wrapper .form-select:focus {
            box-shadow: none;
            background: var(--cream);
        }

        .search-wrapper .btn-search {
            background: var(--primary-green);
            color: #fff;
            border-radius: var(--radius-sm);
            padding: 12px 28px;
            font-weight: 600;
            border: none;
            transition: all 0.25s ease;
        }

        .search-wrapper .btn-search:hover {
            background: var(--primary-green-dark);
            transform: scale(1.02);
        }

        /* ===== TRUST BADGES ===== */
        .trust-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 20px 40px;
            justify-content: center;
            margin: 20px 0 10px;
        }

        .trust-badges .badge-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: var(--dark-text);
            opacity: 0.75;
            font-weight: 500;
        }

        .trust-badges .badge-item i {
            color: var(--primary-green);
            font-size: 1.2rem;
        }

        /* ===== SECTION TITLES ===== */
        .section-title {
            font-weight: 700;
            font-size: clamp(1.6rem, 3vw, 2.4rem);
            letter-spacing: -0.02em;
            color: var(--primary-green-dark);
        }

        .section-subtitle {
            color: #6b6259;
            font-weight: 400;
            font-size: 1.05rem;
        }

        /* ===== CATEGORY CARDS ===== */
        .category-card {
            background: #fff;
            border-radius: var(--radius-sm);
            padding: 24px 16px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(45, 42, 36, 0.04);
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            height: 100%;
        }

        .category-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-green-light);
        }

        .category-card .icon-circle {
            width: 64px;
            height: 64px;
            background: var(--cream);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 1.8rem;
            color: var(--primary-green);
            transition: all 0.3s ease;
        }

        .category-card:hover .icon-circle {
            background: var(--primary-green);
            color: #fff;
            transform: scale(1.05);
        }

        .category-card h6 {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 4px;
        }

        .category-card small {
            color: #8a7f74;
            font-size: 0.8rem;
        }

        /* ===== PRODUCT CARDS ===== */
        .product-card {
            background: #fff;
            border-radius: var(--radius-sm);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(45, 42, 36, 0.04);
            box-shadow: var(--shadow-sm);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-green-light);
        }

        .product-card .product-img {
            height: 180px;
            background: var(--cream);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            color: var(--earth-brown);
            transition: all 0.4s ease;
        }

        .product-card:hover .product-img {
            background: #e8ddd2;
        }

        .product-card .product-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-card .product-body {
            padding: 16px 18px 18px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-card .product-name {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 2px;
        }

        .product-card .product-origin {
            font-size: 0.8rem;
            color: #8a7f74;
        }

        .product-card .product-price {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--primary-green);
            margin: 6px 0 4px;
        }

        .product-card .product-rating {
            font-size: 0.85rem;
            color: #f5a623;
        }

        .product-card .product-rating span {
            color: #8a7f74;
            font-weight: 400;
            margin-left: 4px;
        }

        .product-card .btn-add {
            border-radius: 50px;
            padding: 8px 18px;
            font-size: 0.85rem;
            font-weight: 600;
            width: 100%;
            margin-top: 10px;
            transition: all 0.25s ease;
        }

        .product-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 10px;
        }

        .product-actions .btn {
            border-radius: 50px;
            padding: 8px 6px;
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .product-actions .btn-add {
            margin-top: 0;
        }

        /* ===== TESTIMONIALS ===== */
        .testimonial-card {
            background: #fff;
            border-radius: var(--radius-sm);
            padding: 28px 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(45, 42, 36, 0.04);
            height: 100%;
            transition: all 0.3s ease;
        }

        .testimonial-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-3px);
        }

        .testimonial-card .quote {
            font-size: 1.8rem;
            color: var(--earth-light);
            line-height: 1;
            margin-bottom: 8px;
        }

        .testimonial-card p {
            font-size: 0.95rem;
            color: #3d3832;
            font-style: italic;
        }

        .testimonial-card .client-name {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .testimonial-card .client-role {
            font-size: 0.8rem;
            color: #8a7f74;
        }

        .testimonial-card .client-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--cream);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--primary-green);
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .testimonial-card .client-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        /* ===== STEPS ===== */
        .step-item {
            text-align: center;
            padding: 20px 12px;
        }

        .step-number {
            width: 64px;
            height: 64px;
            background: var(--primary-green);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0 auto 16px;
            box-shadow: 0 4px 16px rgba(45, 106, 79, 0.25);
            transition: all 0.3s ease;
        }

        .step-item:hover .step-number {
            transform: scale(1.06);
            box-shadow: 0 6px 24px rgba(45, 106, 79, 0.35);
        }

        .step-item h6 {
            font-weight: 600;
            font-size: 1rem;
        }

        .step-item p {
            font-size: 0.9rem;
            color: #6b6259;
            max-width: 220px;
            margin: 0 auto;
        }

        /* ===== FOOTER ===== */
        .footer {
            background: var(--primary-green-dark);
            color: rgba(255, 255, 255, 0.8);
            padding: 48px 0 24px;
            border-radius: 40px 40px 0 0;
            margin-top: 40px;
        }

        .footer a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .footer a:hover {
            color: #fff;
        }

        .footer h6 {
            color: #fff;
            font-weight: 600;
            margin-bottom: 16px;
            font-size: 0.95rem;
        }

        .footer .social-icons a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            transition: all 0.25s ease;
            margin-right: 8px;
        }

        .footer .social-icons a:hover {
            background: var(--primary-green-light);
            transform: translateY(-2px);
        }

        .footer .footer-divider {
            border-color: rgba(255, 255, 255, 0.08);
            margin: 24px 0;
        }

        /* ===== MODAL ===== */
        .modal-content {
            border-radius: var(--radius-md);
            border: none;
            box-shadow: var(--shadow-lg);
        }

        .modal-header {
            border-bottom: 1px solid rgba(45, 42, 36, 0.06);
            padding: 20px 24px;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-footer {
            border-top: 1px solid rgba(45, 42, 36, 0.06);
            padding: 16px 24px;
        }

        /* ===== RESPONSIVE TWEAKS ===== */
        @media (max-width: 768px) {
            body {
                padding-top: 64px;
            }

            .hero {
                padding: 40px 0 50px;
            }

            .hero .btn-group .btn {
                width: 100%;
            }

            .search-wrapper {
                padding: 12px;
            }

            .search-wrapper .btn-search {
                width: 100%;
                margin-top: 8px;
            }

            .trust-badges {
                gap: 12px 24px;
            }

            .trust-badges .badge-item {
                font-size: 0.75rem;
            }

            .navbar-brand {
                font-size: 1rem;
            }

            .footer {
                padding: 32px 0 16px;
                border-radius: 24px 24px 0 0;
            }
        }

        @media (max-width: 576px) {
            .hero h1 {
                font-size: 1.9rem;
            }

            .section-title {
                font-size: 1.4rem;
            }

            .product-card .product-img {
                height: 140px;
                font-size: 2.8rem;
            }

            .category-card {
                padding: 16px 12px;
            }

            .category-card .icon-circle {
                width: 52px;
                height: 52px;
                font-size: 1.4rem;
            }
        }

        /* ===== ACCESSIBILITY: FOCUS ===== */
        a:focus-visible,
        button:focus-visible,
        input:focus-visible,
        select:focus-visible {
            outline: 3px solid var(--primary-green);
            outline-offset: 2px;
        }

        /* ===== UTILITY ===== */
        .bg-cream {
            background-color: var(--cream);
        }

        .text-earth {
            color: var(--earth-brown);
        }

        .text-green-dark {
            color: var(--primary-green-dark);
        }

        .gap-8 {
            gap: 8px;
        }

        .gap-12 {
            gap: 12px;
        }

        .gap-16 {
            gap: 16px;
        }

        .rounded-4 {
            border-radius: var(--radius-sm);
        }

        .shadow-hover {
            transition: box-shadow 0.3s ease;
        }

        .shadow-hover:hover {
            box-shadow: var(--shadow-md);
        }

        /* Animations d'apparition */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-on-load {
            animation: fadeInUp 0.6s ease forwards;
        }

        .delay-1 {
            animation-delay: 0.1s;
            opacity: 0;
        }

        .delay-2 {
            animation-delay: 0.2s;
            opacity: 0;
        }

        .delay-3 {
            animation-delay: 0.3s;
            opacity: 0;
        }

        .delay-4 {
            animation-delay: 0.4s;
            opacity: 0;
        }

        .delay-5 {
            animation-delay: 0.5s;
            opacity: 0;
        }

        .delay-6 {
            animation-delay: 0.6s;
            opacity: 0;
        }
    </style>
</head>

<body>

    <?php if ($registrationEmail): ?>
        <div class="alert alert-success alert-dismissible fade show position-fixed start-50 translate-middle-x shadow" style="top:85px;z-index:1100;max-width:620px;width:calc(100% - 32px)" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            Compte créé. Votre email de connexion est <strong><?= htmlspecialchars($registrationEmail) ?></strong>. Conservez-le avec votre mot de passe.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    <?php endif; ?>

    <!-- ================================ -->
    <!-- NAVBAR -->
    <!-- ================================ -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-basket2-fill"></i> Butembo Marché
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-2 gap-lg-3">
                    <li class="nav-item"><a class="nav-link" href="#categories">Catégories</a></li>
                    <li class="nav-item"><a class="nav-link" href="#produits">Produits</a></li>
                    <li class="nav-item"><a class="nav-link" href="#temoignages">Avis</a></li>
                    <li class="nav-item">
                        <a class="nav-link badge-cart" href="#" data-bs-toggle="modal" data-bs-target="#panierModal">
                            <i class="bi bi-cart3"></i> Articles: <span id="cartCount">0</span>
                        </a>
                    </li>
                    <?php if (!empty($_SESSION['is_logged_in']) && ($_SESSION['user_type'] ?? '') === 'acheteur'): ?>
                        <li class="nav-item">
                            <a href="views/mes-commandes.php" class="btn btn-outline-success btn-sm rounded-pill px-3">
                                <i class="bi bi-bag-check me-1"></i> Mes commandes
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <?php if (!empty($_SESSION['is_logged_in'])): ?>
                            <a href="<?= ($_SESSION['user_type'] ?? '') === 'acheteur' ? '#panierModal' : 'views/dashboard.php' ?>" class="btn btn-primary btn-sm rounded-pill px-4" <?= ($_SESSION['user_type'] ?? '') === 'acheteur' ? 'data-bs-toggle="modal" data-bs-target="#panierModal"' : '' ?>>
                                <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['user_prenom'] ?? 'Mon compte') ?>
                            </a>
                            <a href="models/logout.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 ms-1" title="Déconnexion"><i class="bi bi-box-arrow-right"></i></a>
                        <?php else: ?>
                            <a href="connexion.php" class="btn btn-primary btn-sm rounded-pill px-4">Connexion</a>
                            <a href="inscription.php" class="btn btn-outline-success btn-sm rounded-pill px-3 ms-1">S’inscrire</a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ================================ -->
    <!-- HERO -->
    <!-- ================================ -->
    <section class="hero">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-6">
                    <h1>
                        Des produits vivriers <span class="highlight">frais</span><br>
                        directement de <span class="highlight">Butembo</span>
                    </h1>
                    <p class="lead mt-3">
                        Commandez en ligne et recevez vos produits en ville,
                        en toute confiance. Fraîcheur et qualité garanties.
                    </p>
                    <div class="btn-group mt-4">
                        <a href="#produits" class="btn btn-primary">Commander maintenant</a>
                        <a href="#categories" class="btn btn-outline-primary">Voir les catégories</a>
                    </div>
                    <!-- Trust badges -->
                    <div class="trust-badges mt-4">
                        <span class="badge-item"><i class="bi bi-phone"></i> Paiement mobile</span>
                        <span class="badge-item"><i class="bi bi-truck"></i> Livraison en ville</span>
                        <span class="badge-item"><i class="bi bi-flower2"></i> Fraîcheur garantie</span>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <svg class="hero-illustration" width="400" height="300" viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="40" y="40" width="320" height="220" rx="24" fill="#E8DDD2" />
                        <rect x="60" y="60" width="140" height="140" rx="16" fill="#40916C" opacity="0.15" />
                        <rect x="220" y="60" width="120" height="80" rx="12" fill="#8D6E4E" opacity="0.12" />
                        <rect x="220" y="156" width="120" height="44" rx="10" fill="#2D6A4F" opacity="0.10" />
                        <circle cx="130" cy="180" r="28" fill="#2D6A4F" opacity="0.08" />
                        <circle cx="310" cy="130" r="18" fill="#D4B896" opacity="0.25" />
                        <path d="M80 240 L320 240" stroke="#8D6E4E" stroke-width="3" stroke-dasharray="8 8" opacity="0.3" />
                        <text x="120" y="270" font-family="Inter, sans-serif" font-size="14" fill="#5A524A" opacity="0.5">Produits vivriers · Butembo</text>
                        <circle cx="340" cy="40" r="6" fill="#F5A623" opacity="0.3" />
                        <circle cx="360" cy="60" r="4" fill="#2D6A4F" opacity="0.2" />
                    </svg>
                </div>
            </div>
        </div>
    </section>

    <!-- ================================ -->
    <!-- SEARCH BAR -->
    <!-- ================================ -->
    <section class="search-section">
        <div class="container">
            <div class="search-wrapper">
                <form action="recherche.php" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-0"><i class="bi bi-geo-alt text-earth"></i></span>
                            <input type="text" class="form-control" placeholder="Butembo" value="Butembo" readonly>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-earth"></i></span>
                            <input type="text" name="q" class="form-control" placeholder="Rechercher un produit...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="categorie" class="form-select">
                            <option value="">Toutes les catégories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat->id ?>"><?= htmlspecialchars($cat->nom) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn-search w-100">OK</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- ================================ -->
    <!-- CATEGORIES -->
    <!-- ================================ -->
    <section id="categories" class="py-5">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="section-title">Nos catégories</h2>
                <p class="section-subtitle">Retrouvez l'essentiel des produits vivriers de Butembo</p>
            </div>
            <div class="row g-3 g-md-4">
                <?php
                $delay = 1;
                foreach ($categories as $cat):
                    $icon = $categoryIcons[$cat->nom] ?? $categoryIcons['default'];
                ?>
                    <div class="col-6 col-md-4 col-lg-2 animate-on-load delay-<?= $delay ?>">
                        <div class="category-card">
                            <div class="icon-circle"><i class="bi <?= $icon ?>"></i></div>
                            <h6><?= htmlspecialchars($cat->nom) ?></h6>
                            <small><?= $cat->nb_produits ?? 0 ?> produits</small>
                        </div>
                    </div>
                <?php
                    $delay++;
                    if ($delay > 6) $delay = 1;
                endforeach;
                ?>
            </div>
        </div>
    </section>

    <!-- ================================ -->
    <!-- PRODUCTS (FEATURED) -->
    <!-- ================================ -->
    <section id="produits" class="py-4 pb-5 bg-cream">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="section-title">Produits en vedette</h2>
                <p class="section-subtitle">Les meilleurs produits du marché de Butembo</p>
            </div>
            <div class="row g-4">
                <?php
                $delay = 1;
                $colorIndex = 0;
                foreach ($produitsVedette as $produit):
                    // Utiliser une couleur différente pour chaque produit
                    $color = isset($productColors[$colorIndex % count($productColors)]) ? $productColors[$colorIndex % count($productColors)] : '#E8DDD2';
                    $colorIndex++;
                ?>
                    <div class="col-6 col-md-4 col-lg-3 animate-on-load delay-<?= $delay ?>">
                        <div class="product-card">
                            <div class="product-img" style="background: <?= $color ?>;">
                                <?php
                                    $imagesProduit = json_decode($produit->images ?? '', true);
                                    $imageProduit = is_array($imagesProduit) ? ($imagesProduit[0] ?? null) : ($produit->images ?? null);
                                ?>
                                <?php if (!empty($imageProduit)): ?>
                                    <img src="<?= htmlspecialchars($imageProduit) ?>" alt="<?= htmlspecialchars($produit->nom) ?>" loading="lazy">
                                <?php else: ?>
                                    <i class="bi <?= $categoryIcons[$produit->categorie_nom ?? 'default'] ?? 'bi-box' ?>"></i>
                                <?php endif; ?>
                            </div>
                            <div class="product-body">
                                <div class="product-name"><?= htmlspecialchars($produit->nom) ?></div>
                                <div class="product-origin">
                                    <i class="bi bi-geo-alt-fill me-1" style="font-size:0.7rem;"></i>
                                    <?= htmlspecialchars($produit->origine ?? 'Butembo') ?>
                                </div>
                                <div class="product-price"><?= number_format($produit->prix_unitaire ?? 0, 0, ',', ' ') ?> FC</div>
                                <div class="product-rating">
                                    <?= renderStars($produit->note_moyenne ?? 4.5, $produit->nb_avis ?? 0) ?>
                                </div>
                                <div class="product-actions">
                                <button class="btn btn-success btn-add"
                                    data-produit-id="<?= (int) ($produit->id ?? 0) ?>"
                                    data-nom="<?= htmlspecialchars($produit->nom ?? '', ENT_QUOTES) ?>"
                                    data-prix="<?= (float) ($produit->prix_unitaire ?? 0) ?>"
                                    data-stock="<?= (float) ($produit->quantite_stock ?? 0) ?>"
                                    data-unite="<?= htmlspecialchars($produit->unite_mesure ?? 'unité', ENT_QUOTES) ?>">
                                    <i class="bi bi-plus-circle me-1"></i> Ajouter
                                </button>
                                <button type="button" class="btn btn-outline-success btn-discuter"
                                    data-produit-id="<?= (int) ($produit->id ?? 0) ?>"
                                    data-agriculteur-id="<?= (int) ($produit->agriculteur_utilisateur_id ?? 0) ?>"
                                    data-nom="<?= htmlspecialchars($produit->nom ?? '', ENT_QUOTES) ?>">
                                    <i class="bi bi-chat-dots me-1"></i> Discuter
                                </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                    $delay++;
                    if ($delay > 6) $delay = 1;
                endforeach;
                ?>
            </div>
        </div>
    </section>

    <!-- ================================ -->
    <!-- TESTIMONIALS -->
    <!-- ================================ -->
    <section id="temoignages" class="py-5">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="section-title">Ce que disent nos clients</h2>
                <p class="section-subtitle">Des retours authentiques de la communauté de Butembo</p>
            </div>
            <div class="row g-4">
                <?php foreach ($temoignages as $temoignage): ?>
                    <div class="col-md-4">
                        <div class="testimonial-card">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="client-avatar">
                                    <?php if (!empty($temoignage->photo_profil)): ?>
                                        <img src="<?= htmlspecialchars($temoignage->photo_profil) ?>" alt="Avatar">
                                    <?php else: ?>
                                        <?= getInitials($temoignage->nom ?? 'U', $temoignage->prenom ?? '') ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="client-name"><?= htmlspecialchars($temoignage->nom ?? '') ?> <?= htmlspecialchars($temoignage->prenom ?? '') ?></div>
                                    <div class="client-role"><?= ucfirst($temoignage->type_utilisateur ?? 'Client') ?></div>
                                </div>
                            </div>
                            <div class="quote">"</div>
                            <p><?= htmlspecialchars($temoignage->commentaire ?? 'Expérience exceptionnelle avec ce marché numérique !') ?></p>
                            <div class="text-warning">★★★★★</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ================================ -->
    <!-- STEPS -->
    <!-- ================================ -->
    <section class="py-5 bg-cream">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="section-title">Comment commander ?</h2>
                <p class="section-subtitle">3 étapes simples pour recevoir vos produits vivriers</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="step-item">
                        <div class="step-number">1</div>
                        <h6>Choisissez vos produits</h6>
                        <p>Parcourez notre catalogue et sélectionnez les produits vivriers de votre choix</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step-item">
                        <div class="step-number">2</div>
                        <h6>Passez votre commande</h6>
                        <p>Validez votre panier, choisissez votre mode de paiement et votre adresse de livraison</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step-item">
                        <div class="step-number">3</div>
                        <h6>Recevez en ville</h6>
                        <p>Un livreur vous apporte vos produits frais directement à Butembo</p>
                    </div>
                </div>
            </div>
            <!-- Statistiques -->
            <div class="row text-center mt-5 pt-3 g-4">
                <div class="col-4">
                    <h3 class="text-green-dark fw-bold"><?= $stats['clients'] + 50 ?>+</h3>
                    <p class="text-muted small">Clients satisfaits</p>
                </div>
                <div class="col-4">
                    <h3 class="text-green-dark fw-bold"><?= $stats['commandes'] + 100 ?>+</h3>
                    <p class="text-muted small">Commandes livrées</p>
                </div>
                <div class="col-4">
                    <h3 class="text-green-dark fw-bold"><?= $stats['agriculteurs'] + 10 ?>+</h3>
                    <p class="text-muted small">Agriculteurs partenaires</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ================================ -->
    <!-- FOOTER -->
    <!-- ================================ -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <!-- Colonne 1: Logo & Description -->
                <div class="col-lg-4">
                    <h5 class="text-white fw-bold mb-3">
                        <i class="bi bi-basket2-fill me-2"></i>Butembo Marché
                    </h5>
                    <p class="small opacity-75">
                        Le premier marché numérique dédié aux produits vivriers de Butembo.
                        Fraîcheur, qualité et proximité au service de notre communauté.
                    </p>
                    <div class="social-icons mt-3">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-whatsapp"></i></a>
                        <a href="#"><i class="bi bi-instagram"></i></a>
                        <a href="#"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>
                <!-- Colonne 2: Liens rapides -->
                <div class="col-lg-2 col-md-4">
                    <h6>Liens rapides</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="#produits">Produits</a></li>
                        <li class="mb-2"><a href="#categories">Catégories</a></li>
                        <li class="mb-2"><a href="#">Devenir vendeur</a></li>
                        <li class="mb-2"><a href="#">Devenir livreur</a></li>
                    </ul>
                </div>
                <!-- Colonne 3: Horaires & Contact -->
                <div class="col-lg-3 col-md-4">
                    <h6>Horaires & Contact</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><i class="bi bi-clock me-2"></i>Lun-Sam: 7h - 20h</li>
                        <li class="mb-2"><i class="bi bi-clock me-2"></i>Dim: 8h - 14h</li>
                        <li class="mb-2"><i class="bi bi-telephone me-2"></i>+243 999 999 999</li>
                        <li class="mb-2"><i class="bi bi-envelope me-2"></i>contact@butembomarche.com</li>
                    </ul>
                </div>
                <!-- Colonne 4: Newsletter -->
                <div class="col-lg-3 col-md-4">
                    <h6>Restez informé</h6>
                    <p class="small opacity-75">Recevez nos offres et nouveautés</p>
                    <div class="input-group">
                        <input type="email" class="form-control form-control-sm" placeholder="Votre email" style="border-radius: 50px 0 0 50px;">
                        <button class="btn btn-success btn-sm" style="border-radius: 0 50px 50px 0;">OK</button>
                    </div>
                </div>
            </div>
            <hr class="footer-divider">
            <div class="d-flex flex-wrap justify-content-between align-items-center small opacity-75">
                <span>&copy; <?= date('Y') ?> Butembo Marché Numérique. Tous droits réservés.</span>
                <span>
                    <a href="#" class="me-3">Mentions légales</a>
                    <a href="#" class="me-3">Confidentialité</a>
                    <a href="#">CGV</a>
                </span>
            </div>
        </div>
    </footer>

    <!-- ================================ -->
    <!-- MODAL: DÉTAILS PRODUIT -->
    <!-- ================================ -->
    <div class="modal fade" id="produitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Détails du produit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-md-5">
                            <div class="bg-cream rounded-4 p-4 text-center" style="height: 250px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-cup-straw" style="font-size: 5rem; color: var(--earth-brown);"></i>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <h3 class="fw-bold">Farine de manioc</h3>
                            <div class="text-warning mb-2">★★★★★ <span class="text-muted">(24 avis)</span></div>
                            <p class="text-muted">Farine de manioc de première qualité, issue de l'agriculture locale à Butembo.</p>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="fw-bold text-primary-green" style="font-size: 1.8rem;">2 500 FC</span>
                                <span class="badge bg-success">En stock</span>
                            </div>
                            <div class="d-flex gap-2 mb-3">
                                <span class="badge bg-light text-dark"><i class="bi bi-geo-alt me-1"></i> Butembo</span>
                                <span class="badge bg-light text-dark"><i class="bi bi-flower2 me-1"></i> Bio</span>
                            </div>
                            <div class="d-flex gap-2">
                                <input type="number" class="form-control" value="1" min="1" style="width: 80px; border-radius: 50px;">
                                <button class="btn btn-success flex-grow-1" style="border-radius: 50px;">
                                    <i class="bi bi-cart-plus me-2"></i> Ajouter au panier
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================ -->
    <!-- MODAL: PANIER -->
    <!-- ================================ -->
    <div class="modal fade" id="panierModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cart3 me-2"></i>Votre panier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center py-4" id="panierVide">
                        <i class="bi bi-cart3" style="font-size: 3rem; color: #ddd;"></i>
                        <p class="mt-3 text-muted">Votre panier est vide</p>
                        <a href="#produits" class="btn btn-primary btn-sm rounded-pill" data-bs-dismiss="modal">Découvrir nos produits</a>
                    </div>
                    <div id="panierItems" style="display: none;">
                        <div id="panierListe"></div>
                        <div class="border-top pt-3 mt-2">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Sous-total</span>
                                <span class="fw-bold" id="panierSousTotal">0 FC</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Frais de livraison</span>
                                <span class="fw-bold" id="panierFrais">500 FC</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="fw-bold">Total</span>
                                <span class="fw-bold text-primary-green" style="font-size: 1.4rem;" id="panierTotal">500 FC</span>
                            </div>
                            <div class="mb-3">
                                <label for="commandeAdresse" class="form-label fw-semibold">Adresse de livraison *</label>
                                <input type="text" class="form-control" id="commandeAdresse" maxlength="500" placeholder="Quartier, avenue, numéro…" value="<?= htmlspecialchars($_SESSION['user_adresse'] ?? '', ENT_QUOTES) ?>">
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-sm-6">
                                    <label for="commandePaiement" class="form-label fw-semibold">Mode de paiement *</label>
                                    <select class="form-select" id="commandePaiement">
                                        <option value="especes">Espèces</option>
                                        <option value="mobile_money">Mobile Money</option>
                                        <option value="carte">Carte</option>
                                        <option value="virement">Virement</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label for="commandeInstructions" class="form-label fw-semibold">Instructions</label>
                                    <input type="text" class="form-control" id="commandeInstructions" maxlength="500" placeholder="Facultatif">
                                </div>
                            </div>
                            <div class="alert d-none" id="commandeMessage" role="alert"></div>
                            <button class="btn btn-primary w-100 rounded-pill py-3" id="panierCommander">
                                <i class="bi bi-check-circle me-2"></i> Passer la commande
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($_SESSION['is_logged_in']) && ($_SESSION['user_type'] ?? '') === 'acheteur'): ?>
        <?php $chatApiPath = 'models/traitement/chat-post.php'; require __DIR__ . '/views/partials/chat-widget.php'; ?>
    <?php endif; ?>

    <!-- ================================ -->
    <!-- SCRIPTS -->
    <!-- ================================ -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.querySelectorAll('.btn-discuter').forEach(function(button) {
            button.addEventListener('click', function() {
                <?php if (empty($_SESSION['is_logged_in']) || ($_SESSION['user_type'] ?? '') !== 'acheteur'): ?>
                    window.location.href = 'connexion.php';
                <?php else: ?>
                    window.openProductChat({
                        farmerId: this.dataset.agriculteurId,
                        name: this.dataset.nom,
                        url: 'index.php?produit=' + encodeURIComponent(this.dataset.produitId) + '#produits'
                    });
                <?php endif; ?>
            });
        });

        // ============================================
        // GESTION DYNAMIQUE DU PANIER
        // ============================================
        function escapeCartText(value) {
            const element = document.createElement('div');
            element.textContent = value == null ? '' : String(value);
            return element.innerHTML;
        }

        const panier = {
            items: (() => {
                try {
                    const saved = JSON.parse(localStorage.getItem('bbomarcher_panier') || '[]');
                    return Array.isArray(saved) ? saved : [];
                } catch (error) {
                    return [];
                }
            })(),
            total: 0,

            add(produitId, nom, prix, quantite = 1, stock = 0, unite = 'unité') {
                const existing = this.items.find(item => item.id === produitId);
                if (existing) {
                    if (existing.quantite + quantite > existing.stock) {
                        alert('Stock maximum disponible : ' + existing.stock + ' ' + existing.unite);
                        return;
                    }
                    existing.quantite += quantite;
                } else {
                    if (stock <= 0) return;
                    this.items.push({
                        id: produitId,
                        nom,
                        prix,
                        quantite,
                        stock,
                        unite
                    });
                }
                this.updateUI();
            },

            changeQuantity(produitId, quantity) {
                const item = this.items.find(item => item.id === produitId);
                if (!item) return;
                quantity = Math.max(1, Math.min(parseFloat(quantity) || 1, item.stock));
                item.quantite = quantity;
                this.updateUI();
            },

            remove(produitId) {
                this.items = this.items.filter(item => item.id !== produitId);
                this.updateUI();
            },

            updateUI() {
                localStorage.setItem('bbomarcher_panier', JSON.stringify(this.items));
                const count = this.items.reduce((sum, item) => sum + item.quantite, 0);
                const badge = document.getElementById('cartCount');
                badge.textContent = count;

                const panierVide = document.getElementById('panierVide');
                const panierItems = document.getElementById('panierItems');
                const liste = document.getElementById('panierListe');

                if (count === 0) {
                    panierVide.style.display = 'block';
                    panierItems.style.display = 'none';
                    return;
                }

                panierVide.style.display = 'none';
                panierItems.style.display = 'block';

                let html = '';
                let sousTotal = 0;

                this.items.forEach(item => {
                    const totalItem = item.prix * item.quantite;
                    sousTotal += totalItem;
                    html += `
                        <div class="d-flex align-items-center gap-3 border-bottom pb-3 mb-3">
                            <div class="bg-cream rounded-3 p-2" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-box" style="font-size: 1.5rem; color: var(--earth-brown);"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-semibold">${escapeCartText(item.nom)}</h6>
                                <small class="text-muted">${item.prix.toLocaleString()} FC / ${escapeCartText(item.unite)}</small>
                                <input class="form-control form-control-sm mt-1 panier-quantite" type="number" min="1" max="${item.stock}" value="${item.quantite}" data-id="${item.id}" style="width:80px">
                            </div>
                            <span class="fw-bold">${totalItem.toLocaleString()} FC</span>
                            <button class="btn btn-sm btn-outline-danger rounded-circle panier-supprimer" style="width: 30px; height: 30px; padding: 0;" data-id="${item.id}">×</button>
                        </div>
                    `;
                });

                liste.innerHTML = html;

                const frais = sousTotal > 0 ? 500 : 0;
                const total = sousTotal + frais;

                document.getElementById('panierSousTotal').textContent = sousTotal.toLocaleString() + ' FC';
                document.getElementById('panierFrais').textContent = frais.toLocaleString() + ' FC';
                document.getElementById('panierTotal').textContent = total.toLocaleString() + ' FC';

                document.querySelectorAll('.panier-supprimer').forEach(btn => {
                    btn.addEventListener('click', function() {
                        panier.remove(parseInt(this.dataset.id));
                    });
                });
                document.querySelectorAll('.panier-quantite').forEach(input => {
                    input.addEventListener('change', function() {
                        panier.changeQuantity(parseInt(this.dataset.id), this.value);
                    });
                });
            }
        };

        // ============================================
        // AJOUT AU PANIER
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.btn-add').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const nom = this.dataset.nom;
                    const prix = parseFloat(this.dataset.prix);
                    const id = parseInt(this.dataset.produitId);
                    const stock = parseFloat(this.dataset.stock);
                    panier.add(id, nom, prix, 1, stock, this.dataset.unite);

                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="bi bi-check-circle me-1"></i> Ajouté !';
                    this.classList.remove('btn-success');
                    this.classList.add('btn-outline-success');

                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.classList.remove('btn-outline-success');
                        this.classList.add('btn-success');
                    }, 1500);
                });
            });

            document.getElementById('panierCommander')?.addEventListener('click', async function() {
                if (panier.items.length === 0) {
                    alert('Votre panier est vide !');
                    return;
                }
                const address = document.getElementById('commandeAdresse').value.trim();
                const message = document.getElementById('commandeMessage');
                if (!address) {
                    message.className = 'alert alert-danger';
                    message.textContent = 'Veuillez renseigner votre adresse de livraison.';
                    return;
                }
                this.disabled = true;
                const original = this.innerHTML;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement…';
                message.className = 'alert d-none';
                try {
                    const response = await fetch('models/traitement/client-commandes-post.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            items: panier.items.map(item => ({id: item.id, quantite: item.quantite})),
                            adresse_livraison: address,
                            instructions: document.getElementById('commandeInstructions').value.trim(),
                            mode_paiement: document.getElementById('commandePaiement').value
                        })
                    });
                    const result = await response.json();
                    if (!result.success) {
                        if (result.login_required) {
                            localStorage.setItem('bbomarcher_redirect', 'panier');
                            window.location.href = 'connexion.php';
                            return;
                        }
                        throw new Error(result.message || 'Impossible de passer la commande.');
                    }
                    panier.items = [];
                    panier.updateUI();
                    message.className = 'alert alert-success';
                    message.textContent = result.message;
                    this.classList.add('d-none');
                } catch (error) {
                    message.className = 'alert alert-danger';
                    message.textContent = error.message;
                } finally {
                    this.disabled = false;
                    this.innerHTML = original;
                }
            });

            panier.updateUI();

            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        e.preventDefault();
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            const navbar = document.querySelector('.navbar');
            let lastScroll = 0;
            window.addEventListener('scroll', function() {
                const currentScroll = window.pageYOffset;
                if (currentScroll > lastScroll && currentScroll > 100) {
                    navbar.style.transform = 'translateY(-100%)';
                } else {
                    navbar.style.transform = 'translateY(0)';
                }
                lastScroll = currentScroll;
            });
        });
    </script>
</body>

</html>
