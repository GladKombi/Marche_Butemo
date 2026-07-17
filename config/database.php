<?php
/**
 * Configuration de la base de données - Marché Numérique de Butembo
 */

// Paramètres de connexion
define('DB_HOST', 'localhost');
define('DB_NAME', 'marche_butembo');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Fonction pour obtenir la connexion PDO
 */
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log("Erreur de connexion à la base de données : " . $e->getMessage());
        throw $e;
    }
}

// Connexion globale (pour compatibilité avec l'existant)
try {
    $pdo = getDBConnection();
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

/**
 * Fonction pour exécuter les requêtes avec gestion d'erreur
 */
function executeQuery($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Erreur SQL : " . $e->getMessage() . " - Requête: " . $sql);
        return false;
    }
}

/**
 * Fonction pour les requêtes SELECT (tous les résultats)
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

/**
 * Fonction pour les requêtes SELECT (un seul résultat)
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetch() : null;
}

/**
 * Fonction pour les requêtes d'insertion avec retour d'ID
 */
function executeInsert($sql, $params = []) {
    global $pdo;
    $stmt = executeQuery($sql, $params);
    return $stmt ? $pdo->lastInsertId() : false;
}

/**
 * Fonction pour compter le nombre de lignes
 */
function rowCount($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->rowCount() : 0;
}