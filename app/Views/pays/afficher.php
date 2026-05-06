<?php
/**
 * Fiche pays — variables : $pays, $lieux, $erreur
 */
$pays   = $pays   ?? null;
$lieux  = $lieux  ?? [];
$erreur = $erreur ?? null;
?>
<div class="conteneur conteneur-pays">
    <?php if ($erreur !== null) : ?>
        <p class="message-erreur-place" role="alert"><?= e($erreur) ?></p>
        <p><a class="btn" href="<?= e(url()) ?>">Accueil</a></p>
    <?php else : ?>
        <p class="place-crumbs">
            <a href="<?= e(url()) ?>">Accueil</a>
            <span class="place-crumbs-sep" aria-hidden="true"> / </span>
            <a href="<?= e(url('carte')) ?>">Carte</a>
        </p>
        <h1 class="titre-page-pays"><?= e((string) $pays['nom']) ?></h1>
        <?php if (!empty($pays['continent'])) : ?>
            <p class="intro-pays"><?= e((string) $pays['continent']) ?> — code <?= e((string) ($pays['code_iso'] ?? '')) ?></p>
        <?php endif; ?>

        <h2 class="titre-rubrique-pays">Lieux</h2>
        <?php if (count($lieux) === 0) : ?>
            <p class="message-vide">Aucun lieu référencé pour ce pays.</p>
        <?php else : ?>
            <div class="grille-lieux-pays">
                <?php foreach ($lieux as $L) :
                    $idL = (int) $L['id_lieu'];
                    $avg = $L['avg_rating'] ?? null;
                    $nb  = (int) ($L['nb_avis'] ?? 0);
                    ?>
                <a class="carte-lieu-pays" href="<?= e(url('lieu')) ?>?id=<?= $idL ?>">
                    <div class="carte-lieu-pays-illu<?= !empty($L['image_url']) ? ' has-img' : '' ?>">
                        <?php if (!empty($L['image_url'])) : ?>
                            <img src="<?= e((string) $L['image_url']) ?>" alt="" loading="lazy" width="400" height="240">
                        <?php endif; ?>
                    </div>
                    <div class="carte-lieu-pays-corps">
                        <h3><?= e((string) $L['nom']) ?></h3>
                        <p class="carte-lieu-pays-ville"><?= e((string) ($L['ville_nom'] ?? '')) ?></p>
                        <p class="carte-lieu-pays-note">
                            <?php if ($avg !== null && $nb > 0) : ?>
                                <?= starsRatingHtml((int) round((float) $avg)) ?>
                                <span class="note-chiffre"><?= e((string) $avg) ?>/5</span>
                                <span class="nb-avis">(<?= $nb ?> avis)</span>
                            <?php else : ?>
                                <span class="sans-note">Pas encore de note</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
