<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Modele;
use PDO;

/**
 * Avis : profil utilisateur, fiche lieu, flux global paginé.
 */
class AvisModel extends Modele
{
    public static function lieuExiste(int $idLieu): bool
    {
        // SELECT 1 LIMIT 1 : plus efficace que COUNT(*) — MySQL s'arrête dès
        // qu'il trouve une ligne correspondante (pas de scan complet de la table).
        $st = self::pdo()->prepare('SELECT 1 FROM lieu WHERE id_lieu = :id LIMIT 1');
        $st->execute([':id' => $idLieu]);
        return (bool) $st->fetchColumn();
    }

    public static function utilisateurADejaAvisSurLieu(int $idUtilisateur, int $idLieu): bool
    {
        $st = self::pdo()->prepare(
            'SELECT COUNT(*) FROM avis WHERE id_utilisateur = :u AND id_lieu = :l'
        );
        $st->execute([':u' => $idUtilisateur, ':l' => $idLieu]);
        return (int) $st->fetchColumn() > 0;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listePubliqueRecents(int $limite = 80): array
    {
        $limite = max(1, min(200, $limite));
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
        $q = self::pdo()->query($sql);
        return $q ? $q->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public static function creerPourLieu(
        int $idUtilisateur,
        int $idLieu,
        int $note,
        ?string $description,
        string $visibility = 'public',
        ?string $titre = null
    ): int {
        // Double protection sur la visibilité : le contrôleur filtre déjà, mais si la
        // valeur arrive corrompue par une autre voie, on retombe sur 'public' par défaut.
        if ($visibility !== 'public' && $visibility !== 'prive') {
            $visibility = 'public';
        }
        // mb_substr : troncature multibyte pour respecter la colonne VARCHAR(200) sans
        // couper au milieu d'un caractère UTF-8.
        $titre = $titre !== null && $titre !== '' ? mb_substr($titre, 0, 200) : null;
        $st    = self::pdo()->prepare(
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
        return (int) self::pdo()->lastInsertId();
    }

    /**
     * Nombre d'avis publics sur un lieu (pour pagination du flux d'avis).
     */
    public static function compterPublicsLieux(?int $filtreNote, ?int $filtreIdPays): int
    {
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
        $st = self::pdo()->prepare($sql);
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
    ): array {
        $page    = max(1, $page);
        $parPage = max(1, min(50, $parPage));
        $offset  = ($page - 1) * $parPage;

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
        // LIMIT / OFFSET en entiers (cast) : pas de placeholder PDO ici ; les valeurs sont bornées plus haut.
        $sql .= ' ORDER BY a.created_at DESC LIMIT ' . (int) $parPage . ' OFFSET ' . (int) $offset;
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function parUtilisateur(int $idUtilisateur): array
    {
        $st = self::pdo()->prepare(
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
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Liste des id_lieu où l'utilisateur a déjà laissé un avis.
     * Utilisé par le filtre « Mes avis » de la carte pour n'afficher que les
     * pins concernés. Renvoie un tableau d'entiers (int), pas de doublons.
     *
     * @return list<int>
     */
    public static function idsLieuxParUtilisateur(int $idUtilisateur): array
    {
        $st = self::pdo()->prepare(
            'SELECT DISTINCT id_lieu FROM avis
              WHERE id_utilisateur = :uid AND id_lieu IS NOT NULL'
        );
        $st->execute([':uid' => $idUtilisateur]);
        $ids = $st->fetchAll(PDO::FETCH_COLUMN);
        return array_map('intval', $ids ?: []);
    }

    /**
     * Les N derniers avis publics par pays (sur des lieux précis).
     *
     * Utilise ROW_NUMBER() OVER (PARTITION BY id_pays) — window function MySQL 8.0+.
     * Incompatible avec MySQL 5.7 : si la BDD est ancienne, cette requête échoue.
     * L'alternative sans window function serait N requêtes séparées (une par pays),
     * ce qui serait bien plus lent sur de nombreux pays.
     *
     * Retourne un tableau indexé par id_pays : [ id_pays => [avis, ...] ]
     *
     * @return array<int, list<array<string, mixed>>>
     */
    public static function derniersAvisParPays(int $limite = 3): array
    {
        $limite = max(1, min(10, $limite));
        $sql = "SELECT id_pays, id_avis, note, lieu_nom, prenom, nom_user, description, created_at, photo_thumb
                FROM (
                    SELECT
                        p.id_pays,
                        a.id_avis,
                        a.note,
                        l.nom           AS lieu_nom,
                        u.prenom,
                        u.nom           AS nom_user,
                        a.description,
                        a.created_at,
                        (SELECT ph.url FROM photo_avis ph
                         WHERE ph.id_avis = a.id_avis
                         ORDER BY ph.ordre ASC, ph.id_photo ASC LIMIT 1) AS photo_thumb,
                        ROW_NUMBER() OVER (PARTITION BY p.id_pays ORDER BY a.created_at DESC) AS rn
                    FROM avis a
                    JOIN lieu     l  ON l.id_lieu    = a.id_lieu
                    JOIN ville    v  ON v.id_ville   = l.id_ville
                    JOIN pays     p  ON p.id_pays    = v.id_pays
                    JOIN utilisateur u ON u.id_utilisateur = a.id_utilisateur
                    WHERE a.visibility = 'public'
                      AND a.id_lieu IS NOT NULL
                ) AS ranked
                WHERE rn <= {$limite}
                ORDER BY id_pays, created_at DESC";

        $rows = self::pdo()->query($sql);
        if (!$rows) return [];

        // Grouper par id_pays en PHP : plus simple qu'un second GROUP BY en SQL.
        $grouped = [];
        foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $idPays = (int) $row['id_pays'];
            // La colonne rn (numéro de rang) est interne à la sous-requête :
            // on la retire pour ne pas l'exposer dans window.MAP_DATA côté JS.
            unset($row['rn']);
            $grouped[$idPays][] = $row;
        }
        return $grouped;
    }

    /**
     * Avis publics sur un lieu, avec nb likes et nb commentaires.
     *
     * @return list<array<string, mixed>>
     */
    public static function publicsParLieu(int $idLieu): array
    {
        $st = self::pdo()->prepare(
            "SELECT a.id_avis, a.note, a.titre, a.description, a.created_at, u.nom, u.prenom,
                (SELECT ph.url FROM photo_avis ph WHERE ph.id_avis = a.id_avis
                 ORDER BY ph.ordre ASC, ph.id_photo ASC LIMIT 1) AS photo_thumb,
                (SELECT COUNT(*) FROM like_avis la WHERE la.id_avis = a.id_avis) AS nb_likes,
                (SELECT COUNT(*) FROM commentaire c WHERE c.id_avis = a.id_avis) AS nb_commentaires
             FROM avis a
             JOIN utilisateur u ON u.id_utilisateur = a.id_utilisateur
             WHERE a.id_lieu = :id AND a.visibility = 'public'
             ORDER BY a.created_at DESC"
        );
        $st->execute([':id' => $idLieu]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Note moyenne et nombre total d'avis sur un lieu.
     * Les avis privés comptent dans la moyenne (cohérence : un utilisateur qui
     * publie un avis privé a quand même exprimé une note sur le lieu).
     *
     * @return array{n:int, moy:string|null}
     */
    public static function statsParLieu(int $idLieu): array
    {
        $st = self::pdo()->prepare(
            // COALESCE(AVG(...), NULL) est redondant (AVG retourne NULL si 0 ligne)
            // mais le rend explicite : le contrôleur teste $stats['n'] > 0 avant d'afficher.
            'SELECT COUNT(*) AS n, COALESCE(ROUND(AVG(a.note), 2), NULL) AS moy
             FROM avis a WHERE a.id_lieu = :id'
        );
        $st->execute([':id' => $idLieu]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return [
            'n'   => (int) ($row['n'] ?? 0),
            'moy' => $row['moy'] ?? null,
        ];
    }
}
