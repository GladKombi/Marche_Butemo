<?php
require_once __DIR__ . '/../../config/database.php';
class PaiementSelect {
    public static function getByAgriculteur(int $userId): array {
        return fetchAll("SELECT pa.id, pa.commande_id, pa.reference_paiement, pa.montant AS montant_commande,
                pa.mode_paiement, pa.date_paiement, pa.date_remboursement,
                c.numero_commande, c.date_commande, u.nom AS client_nom, u.prenom AS client_prenom,
                SUM(lc.quantite * lc.prix_unitaire) AS montant_agriculteur,
                GROUP_CONCAT(CONCAT(pr.nom, ' × ', lc.quantite) SEPARATOR '||') AS produits
            FROM paiements pa
            JOIN commandes c ON c.id = pa.commande_id AND c.supprime = 0
            JOIN utilisateurs u ON u.id = c.acheteur_id AND u.supprime = 0
            JOIN ligne_commandes lc ON lc.commande_id = c.id AND lc.supprime = 0
            JOIN produits pr ON pr.id = lc.produit_id AND pr.supprime = 0
            JOIN agriculteurs a ON a.id = pr.agriculteur_id AND a.supprime = 0
            WHERE a.utilisateur_id = :user AND pa.supprime = 0
            GROUP BY pa.id
            ORDER BY pa.date_paiement IS NULL DESC, c.date_commande DESC", [':user'=>$userId]);
    }
}
