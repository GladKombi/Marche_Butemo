<?php

require_once __DIR__ . '/../../config/database.php';

class LivraisonSelect
{
    public static function getByLivreur(int $livreurId): array
    {
        return fetchAll("SELECT l.*, c.numero_commande, c.montant_total, c.date_commande,
                u.nom AS client_nom, u.prenom AS client_prenom, u.telephone AS client_telephone,
                (SELECT d.adresse_livraison FROM details_livraison d
                 WHERE d.id_commande = c.id AND d.supprime = 0 ORDER BY d.id DESC LIMIT 1) AS adresse_livraison,
                (SELECT d.instructions_specifiques FROM details_livraison d
                 WHERE d.id_commande = c.id AND d.supprime = 0 ORDER BY d.id DESC LIMIT 1) AS instructions,
                (SELECT GROUP_CONCAT(CONCAT(p.nom, ' × ', lc.quantite, ' ', p.unite_mesure) SEPARATOR '||')
                 FROM ligne_commandes lc JOIN produits p ON p.id = lc.produit_id
                 WHERE lc.commande_id = c.id AND lc.supprime = 0 AND p.supprime = 0) AS produits
            FROM livraisons l
            JOIN commandes c ON c.id = l.commande_id AND c.supprime = 0
            JOIN utilisateurs u ON u.id = c.acheteur_id AND u.supprime = 0
            WHERE l.livreur_id = :livreur AND l.supprime = 0
            ORDER BY FIELD(l.statut_livraison, 'en_cours', 'terminee', 'annulee'), l.date_assignation DESC",
            [':livreur' => $livreurId]);
    }
}
