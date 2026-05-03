<?php
namespace App\Models;

use PDO;

/**
 * Avis (profil, lieu, flux global)
 */
class AvisModel
{
    public static function lieuExiste(int $idLieu) : bool
    {
        $st = Database::getPdo()->prepare('SELECT 1 FROM lieu WHERE id_lieu = :id LIMIT 1');
        $st->execute([':id' => $idLieu]);
        return (bool) $st->fetchColumn();
    }

    public static function utilisateurADejaAvisSurLieu(int $idUtilisateur, int $idLieu) : bool
    {
        $st = Database::getPdo()->prepare(
            'SELECT COUNT(*) FROM avis WHERE id_utilisateur = :u AND id_lieu = :l'
        );
        $st->execute([':u' => $idUtilisateur, ':l' => $idLieu]);
        return (int) $st->fetchColumn() > 0;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listePubliqueRecents(int $limite = 80) : array
    {
        $limite = max(1, min(200, $limite));
        $pdo    = Database::getPdo();
        $sql    = "SELECT a.id_avis, a.note, a.titre, a.description, a.created_at, a.id_lieu, a.id_ville, a.id_pays,
                u.prenom, u.nom,
                l.nom AS lieu_nom,
                v.nom AS ville_nom,
                p.nom AS pays_nom,
                (SELECT ph.url FROM photo_avis ph WHERE ph.id_avis = a.id_avis
                 ORDER BY ph.ordre ASC, ph.id_photo ASC LIMIT 1) AS photo_thumb
             FROM avis a
             JOIN utilisateur u ON u.id_utilisateur = a.id_utilisateur
             LEFT JOIN lieu l ON a.id_lieu = l.id_lieu
             LEFT JOIN ville v ON a.id_ville = v.id_ville
             LEFT JOIN pays p ON a.id_pays = p.id_pays
             WHERE a.visibility = 'public'
             ORDER BY a.created_at DESC
             LIMIT {$limite}";
        $q = $pdo->query($sql);
        return $q ? $q->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public static function creerPourLieu(
        int $idUtilisateur,
        int $idLieu,
        int $note,
        ?string $description,
        string $visibility = 'public',
        ?string $titre = null
    ) : int {
        if ($visibility !== 'public' && $visibility !== 'prive') {
            $visibility = 'public';
        }
        $titre = $titre !== null && $titre !== '' ? mb_substr($titre, 0, 200) : null;
        $st    = Database::getPdo()->prepare(
            'INSERT INTO avis (note, titre, description, visibility, id_utilisateur, id_lieu)
             VALUES (:n, :t, :d, :v, :u, :l)'
        );
        $st->execute([
            ':n' => $note,
            ':t' => $titre,
            ':d' => $description,
            ':v' => $visibility,
            ':u' => $idUtilisateur,
            ':l' => $idLieu,
        ]);
        return (int) Database::getPdo()->lastInsertId();
    }

    /**
     * Nombre d’avis publics sur un lieu (pour pagination reviews).
     */
    public static function compterPublicsLieux(?int $filtreNote, ?int $filtreIdPays) : int
    {
        $pdo = Database::getPdo();
        $sql = "SELECT COUNT(*) FROM avis a
            JOIN lieu l ON a.id_lieu = l.id_lieu
            JOIN ville vi ON l.id_ville = vi.id_ville
            JOIN pays p ON vi.id_pays = p.id_pays
            WHERE a.visibility = 'public' AND a.id_lieu IS NOT NULL";
        $params = [];
        if ($filtreNote !== null && $filtreNote >= 1 && $filtreNote <= 5) {
            $sql .= ' AND a.note = :note';
            $params[':note'] = $filtreNote;
        }
        if ($filtreIdPays !== null && $filtreIdPays > 0) {
            $sql .= ' AND p.id_pays = :pays';
            $params[':pays'] = $filtreIdPays;
        }
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return (int) $st->fetchColumn();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listePublicsLieuxPaginated(
        int $page,
        int $parPage,
        ?int $filtreNote,
        ?int $filtreIdPays
    ) : array {
        $page    = max(1, $page);
        $parPage = max(1, min(50, $parPage));
        $offset  = ($page - 1) * $parPage;

        $pdo = Database::getPdo();
        $sql = "SELECT a.id_avis, a.note, a.titre, a.description, a.created_at, a.id_lieu,
                u.prenom, u.nom,
                l.nom AS lieu_nom,
                p.nom AS pays_nom, p.id_pays,
                (SELECT ph.url FROM photo_avis ph WHERE ph.id_avis = a.id_avis
                 ORDER BY ph.ordre ASC, ph.id_photo ASC LIMIT 1) AS photo_thumb
             FROM avis a
             JOIN utilisateur u ON u.id_utilisateur = a.id_utilisateur
             JOIN lieu l ON a.id_lieu = l.id_lieu
             JOIN ville vi ON l.id_ville = vi.id_ville
             JOIN pays p ON vi.id_pays = p.id_pays
             WHERE a.visibility = 'public' AND a.id_lieu IS NOT NULL";
        $params = [];
        if ($filtreNote !== null && $filtreNote >= 1 && $filtreNote <= 5) {
            $sql .= ' AND a.note = :note';
            $params[':note'] = $filtreNote;
        }
        if ($filtreIdPays !== null && $filtreIdPays > 0) {
            $sql .= ' AND p.id_pays = :pays';
            $params[':pays'] = $filtreIdPays;
        }
        $sql .= ' ORDER BY a.created_at DESC LIMIT ' . (int) $parPage . ' OFFSET ' . (int) $offset;
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function parUtilisateur(int $idUtilisateur) : array
    {
        $st = Database::getPdo()->prepare(
            "SELECT a.id_avis, a.note, a.titre, a.description, a.created_at, a.id_lieu, a.id_ville, a.id_pays,
                l.nom  AS libelle_lieu,
                v.nom  AS libelle_ville,
                p.nom  AS libelle_pays,
                (SELECT ph.url FROM photo_avis ph WHERE ph.id_avis = a.id_avis
                 ORDER BY ph.ordre ASC, ph.id_photo ASC LIMIT 1) AS photo_thumb
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
