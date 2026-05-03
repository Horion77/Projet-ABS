<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

use App\Models\AvisModel;
use App\Models\Database;

$prefixRacine = prefixRacine();
$pageTitre = 'Lieu';
$fichierCssPage = 'place';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$place = null;
$avis = [];
$noteMoy = null;
$erreur = null;
$dejaAvisLieu = false;

if ($id < 1) {
    $erreur = 'Identifiant de lieu invalide.';
} else {
    $pdo = Database::getPdo();
    $stmt = $pdo->prepare(
        'SELECT l.id_lieu, l.nom, l.description, l.latitude, l.longitude, l.image_url,
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
            "SELECT a.id_avis, a.note, a.titre, a.description, a.created_at, u.nom, u.prenom,
                (SELECT ph.url FROM photo_avis ph WHERE ph.id_avis = a.id_avis
                 ORDER BY ph.ordre ASC, ph.id_photo ASC LIMIT 1) AS photo_thumb
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

        $dejaAvisLieu = false;
        if (isLoggedIn()) {
            $dejaAvisLieu = AvisModel::utilisateurADejaAvisSurLieu((int) $_SESSION['user_id'], $id);
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
                <div class="place-illu<?= !empty($place['image_url']) ? ' place-illu--photo' : '' ?>" aria-hidden="true">
                    <?php if (!empty($place['image_url'])) : ?>
                        <img src="<?= e((string) $place['image_url']) ?>" alt="" class="place-illu-img" width="400" height="300" loading="lazy">
                    <?php endif; ?>
                </div>
                <div class="place-entete-texte">
                    <p class="place-crumbs">
                        <a href="<?= e($prefixRacine) ?>index.php">Accueil</a>
                        <span class="place-crumbs-sep" aria-hidden="true"> / </span>
                        <a href="<?= e($prefixRacine) ?>pages/map.php">Carte</a>
                    </p>
                    <h1 class="place-titre"><?= e((string) $place['nom']) ?></h1>
                    <p class="place-meta">
                        <?= e((string) $place['categorie']) ?>
                        — <?= e((string) $place['ville']) ?>,
                        <a href="<?= e($prefixRacine) ?>pages/country.php?id=<?= (int) $place['id_pays'] ?>"><?= e((string) $place['pays']) ?></a>
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

            <?php if (isLoggedIn()) : ?>
                <section class="place-form-avis" aria-labelledby="titre-form-avis">
                    <h2 id="titre-form-avis">Donner votre avis</h2>
                    <?= displayErrors() ?>
                    <?= displaySuccess() ?>
                    <?php if ($dejaAvisLieu) : ?>
                        <p class="message-vide">Vous avez déjà laissé un avis pour ce lieu.</p>
                    <?php else : ?>
                        <form id="form-avis-lieu" class="form-avis-lieu" method="post" action="<?= e($prefixRacine) ?>actions/review_action.php" novalidate>
                            <input type="hidden" name="place_id" value="<?= (int) $place['id_lieu'] ?>">
                            <div class="groupe-champ">
                                <span class="label-like" id="label-stars">Votre note *</span>
                                <input type="hidden" name="rating" id="rating-value" value="" aria-required="true">
                                <div class="stars-input" id="stars-input" role="group" aria-labelledby="label-stars">
                                    <?php for ($s = 1; $s <= 5; $s++) : ?>
                                    <button type="button" class="star-btn" data-star-value="<?= $s ?>" aria-label="Noter <?= $s ?> sur 5">★</button>
                                    <?php endfor; ?>
                                </div>
                                <p class="field-error" id="err-rating" hidden>Veuillez choisir une note.</p>
                            </div>
                            <div class="groupe-champ">
                                <label for="avis-title">Titre (optionnel)</label>
                                <input type="text" id="avis-title" name="title" maxlength="200" placeholder="Ex. : Très belle visite">
                            </div>
                            <div class="groupe-champ">
                                <label for="avis-comment">Commentaire *</label>
                                <textarea id="avis-comment" name="comment" rows="4" maxlength="8000" required placeholder="Décrivez votre expérience…"></textarea>
                                <p class="field-error" id="err-comment" hidden>Le commentaire ne peut pas être vide.</p>
                            </div>
                            <div class="groupe-champ form-avis-vis">
                                <input type="checkbox" id="avis-prive" name="visibility" value="prive">
                                <label for="avis-prive">Avis privé (visible seulement sur votre profil)</label>
                            </div>
                            <button type="submit" class="btn btn-avis-submit">Publier mon avis</button>
                        </form>
                    <?php endif; ?>
                </section>
            <?php else : ?>
                <section class="place-form-avis place-form-avis--invite" aria-label="Connexion requise">
                    <p><a href="<?= e($prefixRacine) ?>pages/login.php">Connectez-vous</a> pour publier un avis sur ce lieu.</p>
                </section>
            <?php endif; ?>
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
                            <span class="stars-wrap"><?= starsRatingHtml((int) $a['note']) ?></span>
                            <span class="note-badge"><?= (int) $a['note'] ?>/5</span>
                            <span class="avis-auteur"><?= e($auteur) ?></span>
                            <?php if ($dt) : ?>
                                <time class="avis-date" datetime="<?= e($dt->format('c')) ?>"><?= e($dt->format('d/m/Y à H:i')) ?></time>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($a['titre'])) : ?>
                            <p class="avis-titre-lieu"><?= e((string) $a['titre']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($a['description'])) : ?>
                            <p class="avis-texte">« <?= nl2br(e((string) $a['description'])) ?> »</p>
                        <?php endif; ?>
                        <?php if (!empty($a['photo_thumb'])) : ?>
                            <p class="avis-photo-wrap">
                                <img class="avis-photo-thumb" src="<?= e((string) $a['photo_thumb']) ?>" alt="" width="100" height="100" loading="lazy">
                            </p>
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
