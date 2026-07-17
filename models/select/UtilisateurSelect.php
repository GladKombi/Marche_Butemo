<?php

/**
 * Requêtes SELECT pour les utilisateurs
 * Marché Numérique de Butembo
 */

require_once __DIR__ . '/../../config/database.php';

class UtilisateurSelect
{

    /**
     * Récupérer tous les utilisateurs actifs
     */
    public static function getAllUtilisateurs($limit = null, $offset = 0)
    {
        // La liste d'administration n'utilise que les colonnes de la table
        // utilisateurs. Ne pas joindre les tables de profils ici : certaines
        // installations ne possèdent pas encore la table livreurs et la jointure
        // faisait alors échouer toute la requête, donnant un tableau vide.
        $sql = "SELECT
                    u.id,
                    u.nom,
                    u.prenom,
                    u.email,
                    u.telephone,
                    u.type_utilisateur,
                    u.adresse,
                    u.est_verifie,
                    u.statut,
                    u.date_creation
                FROM utilisateurs u
                WHERE u.supprime = 0
                ORDER BY u.date_creation DESC";

        if ($limit) {
            $sql .= " LIMIT :limit OFFSET :offset";
            return fetchAll($sql, [':limit' => $limit, ':offset' => $offset]);
        }

        return fetchAll($sql);
    }

    /**
     * Récupérer un utilisateur par son ID
     */
    public static function getById($id)
    {
        $sql = "SELECT 
                    u.*,
                    a.raison_sociale,
                    a.numero_contribuable,
                    a.numero_agrement,
                    a.zone_geographique as agriculteur_zone,
                    a.superficie_terrain,
                    a.certifications,
                    l.type_vehicule,
                    l.plaque_immatriculation,
                    l.permis_conduire,
                    l.zone_couverture,
                    l.dispo_heure_debut,
                    l.dispo_heure_fin,
                    l.tarif_base,
                    l.nb_livraisons_total,
                    l.note_moyenne as livreur_note,
                    l.est_disponible as livreur_disponible
                FROM utilisateurs u
                LEFT JOIN agriculteurs a ON u.id = a.utilisateur_id AND a.supprime = 0
                LEFT JOIN livreurs l ON u.id = l.utilisateur_id AND l.supprime = 0
                WHERE u.id = :id AND u.supprime = 0";

        return fetchOne($sql, [':id' => $id]);
    }

    /**
     * Récupérer un utilisateur par son email
     */
    public static function getByEmail($email)
    {
        $sql = "SELECT * FROM utilisateurs 
                WHERE email = :email AND supprime = 0";
        return fetchOne($sql, [':email' => $email]);
    }

    /**
     * Récupérer un utilisateur par son téléphone
     */
    public static function getByTelephone($telephone)
    {
        $sql = "SELECT * FROM utilisateurs 
                WHERE telephone = :telephone AND supprime = 0";
        return fetchOne($sql, [':telephone' => $telephone]);
    }

    /**
     * Récupérer les utilisateurs par type
     */
    public static function getByType($type, $limit = null, $offset = 0)
    {
        $sql = "SELECT 
                    u.*,
                    a.raison_sociale,
                    a.zone_geographique as agriculteur_zone,
                    l.type_vehicule,
                    l.est_disponible as livreur_disponible
                FROM utilisateurs u
                LEFT JOIN agriculteurs a ON u.id = a.utilisateur_id AND a.supprime = 0
                LEFT JOIN livreurs l ON u.id = l.utilisateur_id AND l.supprime = 0
                WHERE u.type_utilisateur = :type 
                    AND u.supprime = 0
                ORDER BY u.date_creation DESC";

        $params = [':type' => $type];

        if ($limit) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
        }

        return fetchAll($sql, $params);
    }

    /**
     * Vérifier les identifiants de connexion
     */
    public static function authenticate($email, $password)
    {
        $user = self::getByEmail($email);

        if ($user && password_verify($password, $user->mot_de_passe)) {
            return $user;
        }

        return false;
    }

    /**
     * Compter le nombre total d'utilisateurs
     */
    public static function countAll()
    {
        $sql = "SELECT COUNT(*) as total FROM utilisateurs WHERE supprime = 0";
        $result = fetchOne($sql);
        return $result ? (int)$result->total : 0;
    }

    /**
     * Compter par type d'utilisateur
     */
    public static function countByType($type)
    {
        $sql = "SELECT COUNT(*) as total FROM utilisateurs 
                WHERE type_utilisateur = :type AND supprime = 0";
        $result = fetchOne($sql, [':type' => $type]);
        return $result ? (int)$result->total : 0;
    }

    /**
     * Compter par statut
     */
    public static function countByStatus($statut)
    {
        $sql = "SELECT COUNT(*) as total FROM utilisateurs 
                WHERE statut = :statut AND supprime = 0";
        $result = fetchOne($sql, [':statut' => $statut]);
        return $result ? (int)$result->total : 0;
    }

    /**
     * Récupérer les derniers utilisateurs inscrits
     */
    public static function getRecent($limit = 5)
    {
        $sql = "SELECT * FROM utilisateurs 
                WHERE supprime = 0 
                ORDER BY date_creation DESC 
                LIMIT :limit";
        return fetchAll($sql, [':limit' => $limit]);
    }

    /**
     * Récupérer les témoignages (utilisateurs avec avis)
     */
    public static function getTestimonials($limit = 3)
    {
        $sql = "SELECT 
                    u.id,
                    u.nom,
                    u.prenom,
                    u.photo_profil,
                    u.type_utilisateur,
                    av.commentaire,
                    av.note,
                    av.date_creation as date_avis
                FROM utilisateurs u
                JOIN avis_produits av ON u.id = av.utilisateur_id AND av.supprime = 0
                WHERE u.supprime = 0 
                    AND u.statut = 'actif'
                    AND av.commentaire IS NOT NULL 
                    AND av.commentaire != ''
                GROUP BY u.id
                ORDER BY av.date_creation DESC
                LIMIT :limit";

        return fetchAll($sql, [':limit' => $limit]);
    }

    /**
     * Rechercher des utilisateurs
     */
    public static function search($keyword, $type = null)
    {
        $sql = "SELECT 
                    u.*,
                    a.raison_sociale,
                    a.zone_geographique as agriculteur_zone
                FROM utilisateurs u
                LEFT JOIN agriculteurs a ON u.id = a.utilisateur_id AND a.supprime = 0
                WHERE u.supprime = 0
                    AND (u.nom LIKE :keyword 
                         OR u.prenom LIKE :keyword 
                         OR u.email LIKE :keyword 
                         OR u.telephone LIKE :keyword
                         OR a.raison_sociale LIKE :keyword)";

        $params = [':keyword' => '%' . $keyword . '%'];

        if ($type) {
            $sql .= " AND u.type_utilisateur = :type";
            $params[':type'] = $type;
        }

        $sql .= " ORDER BY u.date_creation DESC";

        return fetchAll($sql, $params);
    }
}
