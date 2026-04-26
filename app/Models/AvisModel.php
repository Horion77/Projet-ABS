<?php
namespace App\Models;

/**
 * Avis d’un utilisateur (affichage profil)
 */
class AvisModel
{
    public static function parUtilisateur(int $idUtilisateur) : array
    {
        $st = Database::getPdo()->prepare(
            "SELECT a.id_avis, a.note, a.description, a.created_at, a.id_lieu, a.id_ville, a.id_pays,
                l.nom  AS libelle_lieu,
                v.nom  AS libelle_ville,
                p.nom  AS libelle_pays
             FROM avis a
             LEFT JOIN lieu  l ON a.id_lieu  = l.id_lieu
             LEFT JOIN ville v ON a.id_ville = v.id_ville
             LEFT JOIN pays  p ON a.id_pays  = p.id_pays
             WHERE a.id_utilisateur = :uid
             ORDER BY a.created_at DESC"
        );
        $st->execute([':uid' => $idUtilisateur]);
        return $st->fetchAll();
    }
}
