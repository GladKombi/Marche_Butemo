<?php

/**
 * Traitement des commandes (CREATE, UPDATE, DELETE)
 */

require_once __DIR__ . '/../../config/database.php';

class CommandeTraitement
{

    /**
     * Créer une nouvelle commande
     */
    public static function create($data)
    {
        // Générer un numéro de commande unique
        $numeroCommande = 'CMD-' . date('Ymd') . '-' . strtoupper(uniqid());

        $sql = "INSERT INTO commandes (
            acheteur_id, numero_commande, montant_total, supprime
        ) VALUES (
            :acheteur_id, :numero_commande, :montant_total, 0
        )";

        $params = [
            ':acheteur_id' => $data['acheteur_id'],
            ':numero_commande' => $numeroCommande,
            ':montant_total' => $data['montant_total']
        ];

        return executeInsert($sql, $params);
    }

    /**
     * Mettre à jour le statut d'une commande
     */
    public static function updateStatus($id, $statut, $raison = null)
    {
        $sql = "UPDATE commandes SET 
            statut_commande = :statut
            WHERE id = :id AND supprime = 0";

        $params = [
            ':id' => $id,
            ':statut' => $statut
        ];

        if ($statut === 'annulee') {
            $sql .= ", date_annulation = NOW(), raison_annulation = :raison";
            $params[':raison'] = $raison;
        }

        return executeQuery($sql, $params) ? true : false;
    }

    /**
     * Mettre à jour le statut de paiement
     */
    public static function updatePaymentStatus($id, $statutPaiement)
    {
        $sql = "UPDATE commandes SET 
            statut_paiement = :statut_paiement
            WHERE id = :id AND supprime = 0";

        return executeQuery($sql, [
            ':id' => $id,
            ':statut_paiement' => $statutPaiement
        ]) ? true : false;
    }

    /**
     * Suppression logique d'une commande
     */
    public static function delete($id)
    {
        $sql = "UPDATE commandes SET supprime = 1 WHERE id = :id";
        return executeQuery($sql, [':id' => $id]) ? true : false;
    }
}
