<?php

declare(strict_types=1);

/**
 * Générateur de la structure du projet Marche_Butembo.
 */

$projectRoot = __DIR__ . DIRECTORY_SEPARATOR . 'Marche_Butembo';

/**
 * Dossiers à créer.
 */
$directories = [
    'config',
    'views',
    'models',
    'models/traitement',
    'models/select',
    'assets',
    'assets/images',
    'assets/uploads',
    'assets/uploads/produits',
    'assets/uploads/profiles',
    'error_pages',
];

/**
 * Fichiers à créer.
 */
$files = [
    'index.php' => <<<'PHP'
<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Marché Butembo</title>

    <link rel="icon" href="assets/images/favicon.ico">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: Arial, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
        }

        .container {
            width: 100%;
            max-width: 750px;
            padding: 40px;
            text-align: center;
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 15px 40px rgba(15, 23, 42, 0.12);
        }

        h1 {
            color: #0369a1;
        }

        p {
            color: #475569;
            line-height: 1.7;
        }
    </style>
</head>

<body>
    <main class="container">
        <h1>Marché Butembo</h1>

        <p>
            Bienvenue sur la plateforme de gestion du Marché de Butembo.
        </p>
    </main>
</body>
</html>
PHP,

    'config/database.php' => <<<'PHP'
<?php

declare(strict_types=1);

const DB_HOST = 'localhost';
const DB_NAME = 'marche_butembo';
const DB_USER = 'root';
const DB_PASSWORD = '';
const DB_CHARSET = 'utf8mb4';

try {
    $dsn = 'mysql:host=' . DB_HOST
        . ';dbname=' . DB_NAME
        . ';charset=' . DB_CHARSET;

    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASSWORD,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    error_log($exception->getMessage());

    http_response_code(500);

    $errorPage = dirname(__DIR__) . '/error_pages/500.php';

    if (file_exists($errorPage)) {
        require $errorPage;
        exit;
    }

    exit('Erreur interne du serveur.');
}
PHP,

    'README.md' => <<<'MARKDOWN'
# Marché Butembo

Application web de gestion du Marché de Butembo.

## Technologies utilisées

- PHP 8 ou supérieur
- MySQL ou MariaDB
- HTML5
- CSS3
- JavaScript

## Structure

Marche_Butembo/

- index.php
- README.md
- config/
  - database.php
- views/
- models/
  - traitement/
  - select/
- assets/
  - images/
  - uploads/
    - produits/
    - profiles/
- error_pages/
  - 404.php
  - 403.php
  - 500.php

## Installation avec XAMPP

Placez le projet dans :

C:\xampp\htdocs\Marche_Butembo

Créez ensuite la base de données :

marche_butembo

Ouvrez le projet à l'adresse :

http://localhost/Marche_Butembo/
MARKDOWN,

    'error_pages/404.php' => <<<'PHP'
<?php

declare(strict_types=1);

http_response_code(404);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Erreur 404</title>
</head>

<body>
    <h1>Erreur 404</h1>

    <p>La page demandée est introuvable.</p>

    <a href="../index.php">Retour à l'accueil</a>
</body>
</html>
PHP,

    'error_pages/403.php' => <<<'PHP'
<?php

declare(strict_types=1);

http_response_code(403);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Erreur 403</title>
</head>

<body>
    <h1>Erreur 403</h1>

    <p>Vous n'avez pas l'autorisation d'accéder à cette page.</p>

    <a href="../index.php">Retour à l'accueil</a>
</body>
</html>
PHP,

    'error_pages/500.php' => <<<'PHP'
<?php

declare(strict_types=1);

http_response_code(500);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Erreur 500</title>
</head>

<body>
    <h1>Erreur 500</h1>

    <p>Une erreur interne est survenue sur le serveur.</p>

    <a href="../index.php">Retour à l'accueil</a>
</body>
</html>
PHP,

    'views/.gitkeep' => '',

    'models/traitement/.gitkeep' => '',

    'models/select/.gitkeep' => '',

    'assets/uploads/produits/.gitkeep' => '',

    'assets/uploads/profiles/.gitkeep' => '',

    'assets/images/logo.png' => '',

    'assets/images/favicon.ico' => '',

    'assets/images/default-avatar.png' => '',

    'assets/uploads/.htaccess' => <<<'HTACCESS'
Options -Indexes

<FilesMatch "\.(php|php3|php4|php5|php7|php8|phtml|phar)$">
    Require all denied
</FilesMatch>
HTACCESS,
];

/**
 * Création d'un dossier.
 */
function createDirectory(string $path): bool
{
    if (is_dir($path)) {
        echo 'Dossier déjà existant : ' . htmlspecialchars($path) . '<br>';

        return true;
    }

    if (!mkdir($path, 0775, true) && !is_dir($path)) {
        echo '<strong>Erreur :</strong> impossible de créer le dossier : '
            . htmlspecialchars($path)
            . '<br>';

        return false;
    }

    echo 'Dossier créé : ' . htmlspecialchars($path) . '<br>';

    return true;
}

/**
 * Création d'un fichier.
 */
function createFile(string $path, string $content): bool
{
    $parentDirectory = dirname($path);

    if (!is_dir($parentDirectory)) {
        createDirectory($parentDirectory);
    }

    if (file_exists($path)) {
        echo 'Fichier déjà existant : ' . htmlspecialchars($path) . '<br>';

        return true;
    }

    if (file_put_contents($path, $content, LOCK_EX) === false) {
        echo '<strong>Erreur :</strong> impossible de créer le fichier : '
            . htmlspecialchars($path)
            . '<br>';

        return false;
    }

    echo 'Fichier créé : ' . htmlspecialchars($path) . '<br>';

    return true;
}

echo '<!DOCTYPE html>';
echo '<html lang="fr">';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>Création de Marche Butembo</title>';
echo '</head>';

echo '<body style="font-family:Arial;padding:30px;background:#f1f5f9;">';

echo '<main style="
    max-width:900px;
    margin:auto;
    padding:30px;
    background:white;
    border-radius:15px;
">';

echo '<h1>Création du projet Marche_Butembo</h1>';

createDirectory($projectRoot);

foreach ($directories as $directory) {
    $directoryPath = $projectRoot
        . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $directory);

    createDirectory($directoryPath);
}

foreach ($files as $relativePath => $content) {
    $filePath = $projectRoot
        . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    createFile($filePath, $content);
}

echo '<hr>';

echo '<h2 style="color:green;">Projet créé avec succès.</h2>';

echo '<p>';
echo 'Emplacement : <strong>';
echo htmlspecialchars($projectRoot);
echo '</strong>';
echo '</p>';

echo '<p>';
echo 'Supprimez maintenant le fichier ';
echo '<strong>create_project.php</strong> pour des raisons de sécurité.';
echo '</p>';

echo '</main>';
echo '</body>';
echo '</html>';