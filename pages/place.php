<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

use App\Models\Database;

$prefixRacine = prefixRacine();
$pageTitre = 'Lieu';
$fichierCssPage = 'place';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$place = null;
$avis = [];
$noteMoy = null;
$erreur = null;

if ($id < 1) {
    $erreur = 'Identifiant de lieu invalide.';
} else {
    $pdo = Database::getPdo();
    $stmt = $pdo->prepare(
        'SELECT l.id_lieu, l.nom, l.description, l.latitude, l.longitude,
            cl.libelle AS categorie, vi.nom AS ville, p.nom AS pays, p.id_pays
         FROM lieu l
         JOIN categorie_lieu cl ON cl.id_categorie = l.id_categorie
         JOIN ville vi ON vi.id_ville = l.id_ville
         JOIN pays p ON p.id_pays = vi.id_pays
         WHERE l.id_lieu = :id'
    );
    $stmt->execute(['id' => $id]);
    $place = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$place) {
        $erreur = 'Ce lieu n’existe pas ou n’est plus disponible.';
    } else {
        $pageTitre = (string) $place['nom'];

        $qAvis = $pdo->prepare(
            "SELECT a.id_avis, a.note, a.description, a.created_at, u.nom, u.prenom
             FROM avis a
             JOIN utilisateur u ON u.id_utilisateur = a.id_utilisateur
             WHERE a.id_lieu = :id AND a.visibility = 'public'
             ORDER BY a.created_at DESC"
        );
        $qAvis->execute(['id' => $id]);
        $avis = $qAvis->fetchAll(PDO::FETCH_ASSOC);

        $qAvg = $pdo->prepare(
            "SELECT COUNT(*) AS n, COALESCE(ROUND(AVG(a.note), 2), NULL) AS moy
             FROM avis a WHERE a.id_lieu = :id AND a.visibility = 'public'"
        );
        $qAvg->execute(['id' => $id]);
        $rowAvg = $qAvg->fetch(PDO::FETCH_ASSOC);
        if ($rowAvg && (int) $rowAvg['n'] > 0 && $rowAvg['moy'] !== null) {
            $noteMoy = $rowAvg['moy'];
        }
    }
}

require __DIR__ . '/../app/Views/partials/head.php';
?>

<div class="conteneur place-fiche">
    <?php if ($erreur !== null) : ?>
        <p class="message-erreur-place" role="alert"><?= e($erreur) ?></p>
        <p><a class="btn" href="<?= e($prefixRacine) ?>pages/map.php">Retour à la carte</a></p>
    <?php else : ?>
        <article class="place-article">
            <div class="place-entete">
                <div class="place-illu" aria-hidden="true"></div>
                <div class="place-entete-texte">
                    <p class="place-crumbs">
                        <a href="<?= e($prefixRacine) ?>index.php">Accueil</a>
                        <span class="place-crumbs-sep" aria-hidden="true"> / </span>
                        <a href="<?= e($prefixRacine) ?>pages/map.php">Carte</a>
                    </p>
                    <h1 class="place-titre"><?= e((string) $place['nom']) ?></h1>
                    <p class="place-meta">
                        <?= e((string) $place['categorie']) ?>
                        — <?= e((string) $place['ville']) ?>, <?= e((string) $place['pays']) ?>
                    </p>
                    <p class="place-note-entete">
                        <?php if ($noteMoy !== null) : ?>
                            <strong>Note moyenne (avis publics) :</strong> <?= e((string) $noteMoy) ?>/5
                        <?php else : ?>
                            <span class="place-sans-note">Aucun avis public pour l’instant.</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <?php if (!empty($place['description'])) : ?>
                <section class="place-bloc" aria-label="Présentation">
                    <h2>Présentation</h2>
                    <p class="place-desc"><?= nl2br(e((string) $place['description'])) ?></p>
                </section>
            <?php endif; ?>

            <?php if (isset($place['latitude'], $place['longitude']) && $place['latitude'] !== null && $place['longitude'] !== null) : ?>
                <p class="place-coords">
                    <span class="place-coords-label">Coordonnées :</span>
                    <?= e((string) $place['latitude']) ?>, <?= e((string) $place['longitude']) ?>
                </p>
            <?php endif; ?>
            <!-- TODO: include formulaire d’avis (autre branche) -->
        </article>

        <section class="place-avis" aria-label="Avis">
            <h2>Avis</h2>
            <?php if (count($avis) === 0) : ?>
                <p class="message-vide">Aucun avis public sur ce lieu pour l’instant.</p>
            <?php else : ?>
                <ul class="liste-avis-lieu">
                    <?php foreach ($avis as $a) :
                        $auteur = trim((string) ($a['prenom'] ?? '') . ' ' . (string) ($a['nom'] ?? ''));
                        if ($auteur === '') {
                            $auteur = 'Utilisateur';
                        }
                        $dt = $a['created_at'] ? new \DateTimeImmutable((string) $a['created_at']) : null;
                        ?>
                    <li class="carte-avis-lieu">
                        <div class="carte-avis-lieu-ent">
                            <strong class="note-badge"><?= (int) $a['note'] ?>/5</strong>
                            <span class="avis-auteur"><?= e($auteur) ?></span>
                            <?php if ($dt) : ?>
                                <time class="avis-date" datetime="<?= e($dt->format('c')) ?>"><?= e($dt->format('d/m/Y à H:i')) ?></time>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($a['description'])) : ?>
                            <p class="avis-texte">« <?= nl2br(e((string) $a['description'])) ?> »</p>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<?php
require __DIR__ . '/../app/Views/partials/foot.php';
