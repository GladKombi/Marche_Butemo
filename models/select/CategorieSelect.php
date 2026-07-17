<?php
require_once __DIR__ . '/../../config/database.php';

class CategorieSelect
{
    public static function getAll()
    {
        $sql = "SELECT * FROM categories WHERE supprime = 0 ORDER BY nom";
        return fetchAll($sql);
    }

    public static function getById($id)
    {
        $sql = "SELECT * FROM categories WHERE id = :id AND supprime = 0";
        return fetchOne($sql, [':id' => $id]);
    }

    public static function countAll()
    {
        $sql = "SELECT COUNT(*) as total FROM categories WHERE supprime = 0";
        $result = fetchOne($sql);
        return $result ? (int)$result->total : 0;
    }

    /**
     * Récupérer les catégories avec le nombre de produits disponibles.
     */
    public static function getAllWithCount()
    {
        $sql = "SELECT c.*,
                    COUNT(p.id) AS nb_produits
                FROM categories c
                LEFT JOIN produits p
                    ON p.categorie_id = c.id
                    AND p.supprime = 0
                    AND p.est_disponible = 1
                WHERE c.supprime = 0
                GROUP BY c.id, c.nom, c.description, c.parent_id, c.supprime
                ORDER BY c.nom";
        return fetchAll($sql);
    }
}
