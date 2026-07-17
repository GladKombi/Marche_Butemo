<?php

/**
 * Requêtes SELECT pour les commandes
 * Marché Numérique de Butembo
 */

require_once __DIR__ . '/../../config/database.php';

class CommandeSelect
{

    /**
     * Récupérer toutes les commandes
     */
    public static function getAll($limit = null, $offset = 0)
    {
        $sql = "SELECT 
                    c.id,
                    c.numero_commande,
                    c.date_commande,
                    c.montant_total,
                    500 AS frais_livraison,
                    (SELECT p.mode_paiement FROM paiements p WHERE p.commande_id = c.id AND p.supprime = 0 ORDER BY p.id DESC LIMIT 1) AS mode_paiement,
                    (SELECT d.adresse_livraison FROM details_livraison d WHERE d.id_commande = c.id AND d.supprime = 0 ORDER BY d.id DESC LIMIT 1) AS adresse_livraison,
                    (SELECT d.instructions_specifiques FROM details_livraison d WHERE d.id_commande = c.id AND d.supprime = 0 ORDER BY d.id DESC LIMIT 1) AS instructions_livraison,
                    CASE
                        WHEN c.date_annulation IS NOT NULL THEN 'annulee'
                        WHEN EXISTS (SELECT 1 FROM livraisons l WHERE l.commande_id = c.id AND l.supprime = 0 AND l.statut_livraison = 'terminee') THEN 'livree'
                        WHEN EXISTS (SELECT 1 FROM livraisons l WHERE l.commande_id = c.id AND l.supprime = 0 AND l.statut_livraison = 'en_cours') THEN 'en_livraison'
                        ELSE 'en_attente'
                    END AS statut_commande,
                    CASE WHEN EXISTS (SELECT 1 FROM paiements p WHERE p.commande_id = c.id AND p.supprime = 0 AND p.date_paiement IS NOT NULL)
                        THEN 'paye' ELSE 'en_attente' END AS statut_paiement,
                    c.date_annulation,
                    c.raison_annulation,
                    u.id as acheteur_id,
                    u.nom as acheteur_nom,
                    u.prenom as acheteur_prenom,
                    u.email as acheteur_email,
                    u.telephone as acheteur_telephone,
                    COUNT(lc.id) as nb_produits,
                    SUM(lc.quantite) as quantite_totale
                FROM commandes c
                JOIN utilisateurs u ON c.acheteur_id = u.id AND u.supprime = 0
                LEFT JOIN ligne_commandes lc ON c.id = lc.commande_id AND lc.supprime = 0
                WHERE c.supprime = 0
                GROUP BY c.id
                ORDER BY c.date_commande DESC";

        if ($limit) {
            $sql .= " LIMIT :limit OFFSET :offset";
            return fetchAll($sql, [':limit' => $limit, ':offset' => $offset]);
        }

        return fetchAll($sql);
    }

    /**
     * Récupérer une commande par son ID
     */
    public static function getById($id)
    {
        $sql = "SELECT 
                    c.*,
                    u.id as acheteur_id,
                    u.nom as acheteur_nom,
                    u.prenom as acheteur_prenom,
                    u.email as acheteur_email,
                    u.telephone as acheteur_telephone,
                    u.adresse as acheteur_adresse
                FROM commandes c
                JOIN utilisateurs u ON c.acheteur_id = u.id AND u.supprime = 0
                WHERE c.id = :id AND c.supprime = 0";

        return fetchOne($sql, [':id' => $id]);
    }

    /**
     * Récupérer une commande par son numéro
     */
    public static function getByNumero($numero)
    {
        $sql = "SELECT 
                    c.*,
                    u.nom as acheteur_nom,
                    u.prenom as acheteur_prenom,
                    u.telephone as acheteur_telephone
                FROM commandes c
                JOIN utilisateurs u ON c.acheteur_id = u.id AND u.supprime = 0
                WHERE c.numero_commande = :numero AND c.supprime = 0";

        return fetchOne($sql, [':numero' => $numero]);
    }

    /**
     * Récupérer les commandes d'un acheteur
     */
    public static function getByAcheteur($acheteurId, $limit = null)
    {
        $sql = "SELECT c.*,
                    COUNT(lc.id) AS nb_produits,
                    COALESCE(SUM(lc.quantite), 0) AS quantite_totale,
                    (SELECT d.adresse_livraison FROM details_livraison d WHERE d.id_commande = c.id AND d.supprime = 0 ORDER BY d.id DESC LIMIT 1) AS adresse_livraison,
                    (SELECT d.instructions_specifiques FROM details_livraison d WHERE d.id_commande = c.id AND d.supprime = 0 ORDER BY d.id DESC LIMIT 1) AS instructions_livraison,
                    (SELECT p.mode_paiement FROM paiements p WHERE p.commande_id = c.id AND p.supprime = 0 ORDER BY p.id DESC LIMIT 1) AS mode_paiement,
                    (SELECT l.code_suivi FROM livraisons l WHERE l.commande_id = c.id AND l.supprime = 0 ORDER BY l.id DESC LIMIT 1) AS code_suivi,
                    CASE WHEN c.date_annulation IS NOT NULL THEN 'annulee'
                         WHEN EXISTS (SELECT 1 FROM livraisons l WHERE l.commande_id = c.id AND l.supprime = 0 AND l.statut_livraison = 'terminee') THEN 'livree'
                         WHEN EXISTS (SELECT 1 FROM livraisons l WHERE l.commande_id = c.id AND l.supprime = 0 AND l.statut_livraison = 'en_cours') THEN 'en_livraison'
                         ELSE 'en_attente' END AS statut_commande,
                    CASE WHEN EXISTS (SELECT 1 FROM paiements p WHERE p.commande_id = c.id AND p.supprime = 0 AND p.date_paiement IS NOT NULL) THEN 'paye' ELSE 'en_attente' END AS statut_paiement
                FROM commandes c
                LEFT JOIN ligne_commandes lc ON c.id = lc.commande_id AND lc.supprime = 0
                WHERE c.acheteur_id = :acheteur_id AND c.supprime = 0
                GROUP BY c.id
                ORDER BY c.date_commande DESC";

        $params = [':acheteur_id' => $acheteurId];

        if ($limit) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = $limit;
        }

        return fetchAll($sql, $params);
    }

    /**
     * Commandes contenant au moins un produit de l'utilisateur agriculteur.
     */
    public static function getByAgriculteur($utilisateurId)
    {
        $sql = "SELECT lc.commande_id,
                    SUM(lc.quantite * lc.prix_unitaire) AS montant_agriculteur,
                    COUNT(lc.id) AS nb_produits_agriculteur
                FROM ligne_commandes lc
                JOIN produits p ON p.id = lc.produit_id AND p.supprime = 0
                JOIN agriculteurs a ON a.id = p.agriculteur_id AND a.supprime = 0
                JOIN commandes c ON c.id = lc.commande_id AND c.supprime = 0
                WHERE a.utilisateur_id = :utilisateur_id AND lc.supprime = 0
                GROUP BY lc.commande_id";
        $ownedRows = fetchAll($sql, [':utilisateur_id' => (int) $utilisateurId]);
        $owned = [];
        foreach ($ownedRows as $row) $owned[(int) $row->commande_id] = $row;

        $result = [];
        foreach (self::getAll() as $commande) {
            if (!isset($owned[(int) $commande->id])) continue;
            $commande->montant_agriculteur = (float) $owned[(int) $commande->id]->montant_agriculteur;
            $commande->nb_produits_agriculteur = (int) $owned[(int) $commande->id]->nb_produits_agriculteur;
            $result[] = $commande;
        }
        return $result;
    }

    /**
     * Récupérer les commandes par statut
     */
    public static function getByStatus($statut)
    {
        return array_values(array_filter(self::getAll(), function ($commande) use ($statut) {
            return ($commande->statut_commande ?? 'en_attente') === $statut;
        }));
    }

    /**
     * Récupérer les lignes d'une commande
     */
    public static function getLignesCommande($commandeId)
    {
        $sql = "SELECT 
                    lc.*,
                    p.nom as produit_nom,
                    p.unite_mesure,
                    p.images
                FROM ligne_commandes lc
                JOIN produits p ON lc.produit_id = p.id AND p.supprime = 0
                WHERE lc.commande_id = :commande_id AND lc.supprime = 0
                ORDER BY lc.id";

        return fetchAll($sql, [':commande_id' => $commandeId]);
    }

    /**
     * Compter le nombre total de commandes
     */
    public static function countAll()
    {
        $sql = "SELECT COUNT(*) as total FROM commandes WHERE supprime = 0";
        $result = fetchOne($sql);
        return $result ? (int)$result->total : 0;
    }

    /**
     * Compter les commandes par statut
     */
    public static function countByStatus($statut)
    {
        return count(self::getByStatus($statut));
    }

    /**
     * Compter les commandes d'un acheteur
     */
    public static function countByAcheteur($acheteurId)
    {
        $sql = "SELECT COUNT(*) as total FROM commandes 
                WHERE acheteur_id = :acheteur_id AND supprime = 0";
        $result = fetchOne($sql, [':acheteur_id' => $acheteurId]);
        return $result ? (int)$result->total : 0;
    }

    /**
     * Récupérer le montant total des ventes
     */
    public static function getTotalVentes($periode = null)
    {
        $sql = "SELECT SUM(montant_total) as total FROM commandes 
                WHERE supprime = 0";

        if ($periode) {
            switch ($periode) {
                case 'today':
                    $sql .= " AND DATE(date_commande) = CURDATE()";
                    break;
                case 'week':
                    $sql .= " AND YEARWEEK(date_commande) = YEARWEEK(CURDATE())";
                    break;
                case 'month':
                    $sql .= " AND MONTH(date_commande) = MONTH(CURDATE()) 
                             AND YEAR(date_commande) = YEAR(CURDATE())";
                    break;
                case 'year':
                    $sql .= " AND YEAR(date_commande) = YEAR(CURDATE())";
                    break;
            }
        }

        $result = fetchOne($sql);
        return $result ? (float)$result->total : 0;
    }

    /**
     * Récupérer les commandes récentes
     */
    public static function getRecent($limit = 10)
    {
        return array_slice(self::getAll(), 0, max(0, (int) $limit));
    }

    /**
     * Commandes récentes avec statuts calculés depuis les tables réelles.
     */
    public static function getRecentWithStatus($limit = 10)
    {
        return self::getRecent($limit);
    }

    /**
     * Chiffre d'affaires quotidien des derniers jours, jours sans vente inclus.
     */
    public static function getDailyStats($days = 7)
    {
        $days = max(1, min(365, (int) $days));
        $start = (new DateTimeImmutable('today'))->modify('-' . ($days - 1) . ' days');
        $sql = "SELECT DATE(date_commande) AS jour, SUM(montant_total) AS total_ventes
                FROM commandes
                WHERE supprime = 0 AND date_commande >= :date_debut
                GROUP BY DATE(date_commande)";
        $rows = fetchAll($sql, [':date_debut' => $start->format('Y-m-d 00:00:00')]);
        $totals = [];
        foreach ($rows as $row) {
            $totals[$row->jour] = (float) $row->total_ventes;
        }

        $result = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->modify('+' . $i . ' days')->format('Y-m-d');
            $result[] = (object) ['jour' => $date, 'total_ventes' => $totals[$date] ?? 0.0];
        }
        return $result;
    }
}
