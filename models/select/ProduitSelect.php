<?php

/**
 * Requêtes SELECT pour les produits
 * Marché Numérique de Butembo
 */

require_once __DIR__ . '/../../config/database.php';

class ProduitSelect
{

    /**
     * Récupérer tous les produits
     */
    public static function getAll($limit = null, $offset = 0)
    {
        $sql = "SELECT 
                    p.*,
                    c.nom as categorie_nom,
                    u.nom as agriculteur_nom,
                    u.prenom as agriculteur_prenom
                FROM produits p
                JOIN categories c ON p.categorie_id = c.id AND c.supprime = 0
                JOIN agriculteurs a ON p.agriculteur_id = a.id AND a.supprime = 0
                JOIN utilisateurs u ON a.utilisateur_id = u.id AND u.supprime = 0
                WHERE p.supprime = 0
                ORDER BY p.date_creation DESC";

        if ($limit) {
            $sql .= " LIMIT :limit OFFSET :offset";
            return fetchAll($sql, [':limit' => $limit, ':offset' => $offset]);
        }

        return fetchAll($sql);
    }

    /**
     * Récupérer un produit par son ID
     */
    public static function getById($id)
    {
        $sql = "SELECT 
                    p.*,
                    c.nom as categorie_nom,
                    u.nom as agriculteur_nom,
                    u.prenom as agriculteur_prenom,
                    u.telephone as agriculteur_telephone,
                    a.zone_geographique
                FROM produits p
                JOIN categories c ON p.categorie_id = c.id AND c.supprime = 0
                JOIN agriculteurs a ON p.agriculteur_id = a.id AND a.supprime = 0
                JOIN utilisateurs u ON a.utilisateur_id = u.id AND u.supprime = 0
                WHERE p.id = :id AND p.supprime = 0";

        return fetchOne($sql, [':id' => $id]);
    }

    /**
     * Récupérer les produits par catégorie
     */
    public static function getByCategory($categorieId, $limit = null)
    {
        $sql = "SELECT 
                    p.*,
                    c.nom as categorie_nom,
                    u.nom as agriculteur_nom,
                    u.prenom as agriculteur_prenom
                FROM produits p
                JOIN categories c ON p.categorie_id = c.id AND c.supprime = 0
                JOIN agriculteurs a ON p.agriculteur_id = a.id AND a.supprime = 0
                JOIN utilisateurs u ON a.utilisateur_id = u.id AND u.supprime = 0
                WHERE p.categorie_id = :categorie_id 
                    AND p.supprime = 0 
                    AND p.est_disponible = 1
                ORDER BY p.date_creation DESC";

        $params = [':categorie_id' => $categorieId];

        if ($limit) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = $limit;
        }

        return fetchAll($sql, $params);
    }

    /**
     * Récupérer les produits d'un agriculteur
     */
    public static function getByAgriculteur($agriculteurId)
    {
        $sql = "SELECT 
                    p.*,
                    c.nom as categorie_nom
                FROM produits p
                JOIN categories c ON p.categorie_id = c.id AND c.supprime = 0
                WHERE p.agriculteur_id = :agriculteur_id 
                    AND p.supprime = 0
                ORDER BY p.date_creation DESC";

        return fetchAll($sql, [':agriculteur_id' => $agriculteurId]);
    }

    public static function getByUtilisateurAgriculteur($utilisateurId)
    {
        $sql = "SELECT p.*, c.nom AS categorie_nom
                FROM produits p
                JOIN agriculteurs a ON a.id = p.agriculteur_id AND a.supprime = 0
                JOIN categories c ON c.id = p.categorie_id AND c.supprime = 0
                WHERE a.utilisateur_id = :utilisateur_id AND p.supprime = 0
                ORDER BY p.date_creation DESC";
        return fetchAll($sql, [':utilisateur_id' => (int) $utilisateurId]);
    }

    /**
     * Rechercher des produits
     */
    public static function search($keyword, $categorieId = null)
    {
        $sql = "SELECT 
                    p.*,
                    c.nom as categorie_nom,
                    u.nom as agriculteur_nom,
                    u.prenom as agriculteur_prenom
                FROM produits p
                JOIN categories c ON p.categorie_id = c.id AND c.supprime = 0
                JOIN agriculteurs a ON p.agriculteur_id = a.id AND a.supprime = 0
                JOIN utilisateurs u ON a.utilisateur_id = u.id AND u.supprime = 0
                WHERE p.supprime = 0 
                    AND p.est_disponible = 1
                    AND (p.nom LIKE :keyword 
                         OR p.description LIKE :keyword 
                         OR p.origine LIKE :keyword)";

        $params = [':keyword' => '%' . $keyword . '%'];

        if ($categorieId) {
            $sql .= " AND p.categorie_id = :categorie_id";
            $params[':categorie_id'] = $categorieId;
        }

        $sql .= " ORDER BY p.date_creation DESC";

        return fetchAll($sql, $params);
    }

    /**
     * Récupérer les produits en vedette (les plus vendus)
     */
    public static function getBestSellers($limit = 10)
    {
        $sql = "SELECT 
                    p.*,
                    c.nom as categorie_nom,
                    u.nom as agriculteur_nom,
                    u.prenom as agriculteur_prenom,
                    COUNT(lc.id) as nb_commandes,
                    SUM(lc.quantite) as quantite_vendue
                FROM produits p
                JOIN categories c ON p.categorie_id = c.id AND c.supprime = 0
                JOIN agriculteurs a ON p.agriculteur_id = a.id AND a.supprime = 0
                JOIN utilisateurs u ON a.utilisateur_id = u.id AND u.supprime = 0
                LEFT JOIN ligne_commandes lc ON p.id = lc.produit_id AND lc.supprime = 0
                LEFT JOIN commandes cmd ON lc.commande_id = cmd.id 
                    AND cmd.supprime = 0
                WHERE p.supprime = 0 AND p.est_disponible = 1
                GROUP BY p.id
                ORDER BY quantite_vendue DESC, nb_commandes DESC
                LIMIT :limit";

        return fetchAll($sql, [':limit' => $limit]);
    }

    /**
     * Compter le nombre total de produits
     */
    public static function countAll()
    {
        $sql = "SELECT COUNT(*) as total FROM produits WHERE supprime = 0";
        $result = fetchOne($sql);
        return $result ? (int)$result->total : 0;
    }

    /**
     * Compter par catégorie
     */
    public static function countByCategory($categorieId)
    {
        $sql = "SELECT COUNT(*) as total FROM produits 
                WHERE categorie_id = :categorie_id 
                    AND supprime = 0 
                    AND est_disponible = 1";
        $result = fetchOne($sql, [':categorie_id' => $categorieId]);
        return $result ? (int)$result->total : 0;
    }

    /**
     * Récupérer les alertes de stock
     */
    public static function getStockAlert($limit = 10)
    {
        $sql = "SELECT * FROM produits 
                WHERE supprime = 0 
                AND quantite_stock <= stock_min_alerte 
                AND quantite_stock > 0
                ORDER BY quantite_stock ASC 
                LIMIT :limit";
        return fetchAll($sql, [':limit' => $limit]);
    }

    /**
     * Alertes de stock des produits appartenant à l'utilisateur agriculteur.
     */
    public static function getStockAlertByAgriculteur($utilisateurId, $limit = 10)
    {
        $sql = "SELECT p.*, c.nom AS categorie_nom
                FROM produits p
                JOIN agriculteurs a ON a.id = p.agriculteur_id AND a.supprime = 0
                JOIN categories c ON c.id = p.categorie_id AND c.supprime = 0
                WHERE a.utilisateur_id = :utilisateur_id
                  AND p.supprime = 0
                  AND p.quantite_stock < 10
                ORDER BY p.quantite_stock ASC
                LIMIT :limit";
        return fetchAll($sql, [':utilisateur_id' => (int) $utilisateurId, ':limit' => max(1, (int) $limit)]);
    }

    /**
     * Récupérer les produits en rupture de stock
     */
    public static function getOutOfStock()
    {
        $sql = "SELECT * FROM produits 
                WHERE supprime = 0 
                AND quantite_stock <= 0
                ORDER BY date_creation DESC";
        return fetchAll($sql);
    }
}
