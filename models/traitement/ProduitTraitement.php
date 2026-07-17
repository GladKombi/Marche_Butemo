<?php

/**
 * Traitement des produits - Classe utilitaire
 * Marché Numérique de Butembo
 */

require_once __DIR__ . '/../../config/database.php';

class ProduitTraitement
{

    /**
     * Créer un nouveau produit
     */
    public static function create($data)
    {
        // Vérifier si l'agriculteur existe
        if (empty($data['agriculteur_id'])) {
            return false;
        }

        $sql = "INSERT INTO produits (
            agriculteur_id, categorie_id, nom, description, 
            prix_unitaire, unite_mesure, quantite_stock, 
            images, est_bio, origine, est_disponible
        ) VALUES (
            :agriculteur_id, :categorie_id, :nom, :description,
            :prix_unitaire, :unite_mesure, :quantite_stock,
            :images, :est_bio, :origine, :est_disponible
        )";

        $params = [
            ':agriculteur_id' => $data['agriculteur_id'],
            ':categorie_id' => $data['categorie_id'],
            ':nom' => trim($data['nom']),
            ':description' => $data['description'] ?? null,
            ':prix_unitaire' => $data['prix_unitaire'],
            ':unite_mesure' => $data['unite_mesure'],
            ':quantite_stock' => $data['quantite_stock'] ?? 0,
            ':images' => $data['images'] ?? null,
            ':est_bio' => $data['est_bio'] ?? 0,
            ':origine' => $data['origine'] ?? null,
            ':est_disponible' => $data['est_disponible'] ?? 1
        ];

        return executeInsert($sql, $params);
    }

    /**
     * Mettre à jour un produit
     */
    public static function update($id, $data)
    {
        $sql = "UPDATE produits SET 
            agriculteur_id = :agriculteur_id,
            nom = :nom,
            description = :description,
            prix_unitaire = :prix_unitaire,
            unite_mesure = :unite_mesure,
            quantite_stock = :quantite_stock,
            images = :images,
            est_bio = :est_bio,
            origine = :origine,
            est_disponible = :est_disponible,
            categorie_id = :categorie_id
            WHERE id = :id AND supprime = 0";

        $params = [
            ':id' => $id,
            ':agriculteur_id' => $data['agriculteur_id'],
            ':nom' => trim($data['nom']),
            ':description' => $data['description'] ?? null,
            ':prix_unitaire' => $data['prix_unitaire'],
            ':unite_mesure' => $data['unite_mesure'],
            ':quantite_stock' => $data['quantite_stock'] ?? 0,
            ':images' => $data['images'] ?? null,
            ':est_bio' => $data['est_bio'] ?? 0,
            ':origine' => $data['origine'] ?? null,
            ':est_disponible' => $data['est_disponible'] ?? 1,
            ':categorie_id' => $data['categorie_id']
        ];

        return executeQuery($sql, $params) ? true : false;
    }

    /**
     * Mettre à jour le stock d'un produit
     */
    public static function updateStock($id, $quantite)
    {
        $sql = "UPDATE produits SET 
            quantite_stock = quantite_stock - :quantite,
            est_disponible = CASE 
                WHEN quantite_stock - :quantite <= 0 THEN 0 
                ELSE 1 
            END
            WHERE id = :id AND supprime = 0 AND quantite_stock >= :quantite";

        $params = [
            ':id' => $id,
            ':quantite' => $quantite
        ];

        return executeQuery($sql, $params) ? true : false;
    }

    /**
     * Suppression logique d'un produit
     */
    public static function delete($id)
    {
        $sql = "UPDATE produits SET supprime = 1 WHERE id = :id";
        return executeQuery($sql, [':id' => $id]) ? true : false;
    }

    /**
     * Vérifier la disponibilité du stock
     */
    public static function checkStock($id, $quantite)
    {
        $sql = "SELECT quantite_stock FROM produits 
                WHERE id = :id AND supprime = 0 AND est_disponible = 1";
        $result = fetchOne($sql, [':id' => $id]);
        return $result && $result->quantite_stock >= $quantite;
    }

    /**
     * Récupérer tous les produits d'un agriculteur
     */
    public static function getByAgriculteur($agriculteurId)
    {
        $sql = "SELECT p.*, c.nom as categorie_nom 
                FROM produits p
                JOIN categories c ON p.categorie_id = c.id AND c.supprime = 0
                WHERE p.agriculteur_id = :agriculteur_id 
                    AND p.supprime = 0
                ORDER BY p.date_creation DESC";

        return fetchAll($sql, [':agriculteur_id' => $agriculteurId]);
    }
}
