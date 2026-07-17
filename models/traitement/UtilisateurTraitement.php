<?php

/**
 * Traitement des utilisateurs (CREATE, UPDATE, DELETE)
 */

require_once __DIR__ . '/../../config/database.php';

class UtilisateurTraitement
{

    /**
     * Créer un nouvel utilisateur
     */
    public static function create($data)
    {
        $sql = "INSERT INTO utilisateurs (
            nom, prenom, email, telephone, mot_de_passe, 
            type_utilisateur, adresse, est_verifie, statut
        ) VALUES (
            :nom, :prenom, :email, :telephone, :mot_de_passe,
            :type_utilisateur, :adresse, :est_verifie, :statut
        )";

        $params = [
            ':nom' => $data['nom'],
            ':prenom' => $data['prenom'],
            ':email' => $data['email'],
            ':telephone' => $data['telephone'],
            ':mot_de_passe' => password_hash($data['mot_de_passe'], PASSWORD_DEFAULT),
            ':type_utilisateur' => $data['type_utilisateur'],
            ':adresse' => $data['adresse'] ?? null,
            ':est_verifie' => $data['est_verifie'] ?? 0,
            ':statut' => $data['statut'] ?? 'actif'
        ];

        return executeInsert($sql, $params);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public static function update($id, $data)
    {
        $sql = "UPDATE utilisateurs SET 
            nom = :nom,
            prenom = :prenom,
            telephone = :telephone,
            type_utilisateur = :type_utilisateur,
            adresse = :adresse,
            statut = :statut
            WHERE id = :id AND supprime = 0";

        $params = [
            ':id' => $id,
            ':nom' => $data['nom'],
            ':prenom' => $data['prenom'],
            ':telephone' => $data['telephone'],
            ':type_utilisateur' => $data['type_utilisateur'],
            ':adresse' => $data['adresse'] ?? null,
            ':statut' => $data['statut'] ?? 'actif'
        ];

        return executeQuery($sql, $params) ? true : false;
    }

    /**
     * Mettre à jour le mot de passe
     */
    public static function updatePassword($id, $nouveauMotDePasse)
    {
        $sql = "UPDATE utilisateurs SET 
            mot_de_passe = :mot_de_passe
            WHERE id = :id AND supprime = 0";

        $params = [
            ':id' => $id,
            ':mot_de_passe' => password_hash($nouveauMotDePasse, PASSWORD_DEFAULT)
        ];

        return executeQuery($sql, $params) ? true : false;
    }

    /**
     * Suppression logique d'un utilisateur
     */
    public static function delete($id)
    {
        $sql = "UPDATE utilisateurs SET supprime = 1 WHERE id = :id";
        return executeQuery($sql, [':id' => $id]) ? true : false;
    }

    /**
     * Vérifier si l'email existe déjà
     */
    public static function emailExists($email, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM utilisateurs 
                WHERE email = :email AND supprime = 0";
        $params = [':email' => $email];

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeId;
        }

        $result = fetchOne($sql, $params);
        return $result && $result->count > 0;
    }
}
