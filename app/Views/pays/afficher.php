<?php
/**
 * Fiche pays adaptée depuis `Projet-ABS-Sara/pages/pays.php`.
 * Variables MVC : $pays, $lieux, $erreur
 */
$pays   = $pays   ?? null;
$lieux  = $lieux  ?? [];
$erreur = $erreur ?? null;
$paysData = is_array($pays) ? $pays : [];
?>
<div class="pays-container conteneur conteneur-pays">
    <?php if ($erreur !== null) : ?>
        <p class="message-erreur-place" role="alert"><?= e($erreur) ?></p>
        <p><a class="btn" href="<?= e(url()) ?>">Accueil</a></p>
    <?php else : ?>
        <p class="place-crumbs">
            <a href="<?= e(url()) ?>">Accueil</a>
            <span class="place-crumbs-sep" aria-hidden="true"> / </span>
            <a href="<?= e(url('carte')) ?>">Carte</a>
        </p>

        <h1><?= e((string) ($paysData['nom'] ?? 'Pays')) ?></h1>
        <?php if (!empty($paysData['continent'])) : ?>
            <p class="intro-pays"><?= e((string) $paysData['continent']) ?> — code <?= e((string) ($paysData['code_iso'] ?? '')) ?></p>
        <?php endif; ?>

        <?php if (empty($lieux)) : ?>
            <p>Aucun lieu enregistré pour ce pays pour le moment.</p>
        <?php else : ?>
            <div class="lieux-grid grille-lieux-pays">
                <?php foreach ($lieux as $lieu) :
                    $idLieu = (int) $lieu['id_lieu'];
                    $avg = $lieu['avg_rating'] ?? null;
                    $nbAvis = (int) ($lieu['nb_avis'] ?? 0);
                    ?>
                    <div class="lieu-card carte-lieu-pays">
                        <?php if (!empty($lieu['image_url'])) : ?>
                            <img src="<?= e((string) $lieu['image_url']) ?>" alt="<?= e((string) $lieu['nom']) ?>" loading="lazy">
                        <?php endif; ?>
                        <h2><?= e((string) $lieu['nom']) ?></h2>
                        <?php if (!empty($lieu['ville_nom'])) : ?>
                            <p class="carte-lieu-pays-ville"><?= e((string) $lieu['ville_nom']) ?></p>
                        <?php endif; ?>

                        <?php if ($avg !== null && $nbAvis > 0) : ?>
                            <div class="note">
                                Note moyenne : <?= e((string) round((float) $avg, 1)) ?>/5
                            </div>
                            <div class="nb-avis"><?= $nbAvis ?> avis</div>
                        <?php else : ?>
                            <div class="note">Pas encore de note</div>
                        <?php endif; ?>

                        <a href="<?= e(url('lieu')) ?>?id=<?= $idLieu ?>">Voir les avis</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
