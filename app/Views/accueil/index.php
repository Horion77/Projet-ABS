<?php
/**
 * Page d'accueil — variables : $lieuxPop, $derniersAvis
 */
// ?? [] : défense contre une éventuelle absence de la variable si le contrôleur
// évolue sans passer la clé. Préféré à isset() partout dans les vues.
$lieuxPop     = $lieuxPop     ?? [];
$derniersAvis = $derniersAvis ?? [];
?>
<?php
// displaySuccess() seul (pas le partial messages.php complet) : la page d'accueil
// affiche les confirmations (connexion réussie, inscription…) mais pas les erreurs
// de formulaire qui, elles, sont renvoyées sur la page de formulaire concernée.
?>
<?= displaySuccess() ?>
<section class="accueil-hero" aria-label="Bienvenue">
    <div class="hero-overlay">
        <h1 class="hero-titre">Explorez le monde, partagez vos avis</h1>
        <p class="hero-soustitre">Découvrez des lieux, notez-les et inspirez d’autres voyageurs sur ABS.</p>
        <div class="hero-actions">
            <a class="btn btn-hero" href="<?= e(url('carte')) ?>">Explorer la carte</a>
            <?php if (!isLoggedIn()) : ?>
            <a class="btn btn-hero-sec" href="<?= e(url('inscription')) ?>">S'inscrire</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="conteneur conteneur-accueil">
    <h2 class="titre-rubrique">Lieux populaires</h2>
    <p class="soustitre-rubrique">D’après la note moyenne des avis (publics).</p>

    <?php if (count($lieuxPop) === 0) : ?>
        <p class="message-vide accueil-vide">Aucun lieu n’a encore reçu d’avis, ou la base vient d’être installée. Le classement s’affichera dès qu’il y aura des avis publics sur des lieux.</p>
    <?php else : ?>
        <div class="grille-cartes-lieux">
            <?php foreach ($lieuxPop as $row) : ?>
            <?php
            // (int) $row['id_lieu'] : cast en entier avant injection dans l'URL.
            // Même si e() échappe les caractères HTML, le cast garantit qu'aucune
            // valeur non numérique (ex. injection SQL) ne peut atteindre l'URL.
            ?>
            <a class="carte-lieu" href="<?= e(url('lieu')) ?>?id=<?= (int) $row['id_lieu'] ?>">
                <div class="carte-lieu-illu" aria-hidden="true"></div>
                <div class="carte-lieu-corps">
                    <h3><?= e((string) $row['lieu']) ?></h3>
                    <p class="carte-lieu-pays"><?= e((string) $row['pays']) ?>, <?= e((string) ($row['ville'] ?? '')) ?></p>
                    <p class="carte-lieu-note">
                        <?php if (isset($row['note_moyenne']) && $row['note_moyenne'] !== null) : ?>
                            Note moy. : <?= e((string) $row['note_moyenne']) ?>/5
                            (<?= (int) $row['nb_avis'] ?> avis)
                        <?php else : ?>
                            Encore pas de note
                        <?php endif; ?>
                    </p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="conteneur conteneur-accueil conteneur-derniers-avis">
    <h2 class="titre-rubrique">Derniers avis sur un lieu</h2>
    <?php if (count($derniersAvis) === 0) : ?>
        <p class="message-vide">Aucun avis récent à afficher pour l’instant.</p>
    <?php else : ?>
        <ul class="liste-derniers-avis">
            <?php foreach ($derniersAvis as $av) : ?>
            <li>
                <strong class="rda-note"><?= (int) $av['note'] ?>/5</strong>
                — <span class="rda-lieu"><a href="<?= e(url('lieu')) ?>?id=<?= (int) $av['id_lieu'] ?>"><?= e((string) ($av['lieu'] ?? 'Lieu')) ?></a></span>
                <span class="rda-auteur">(<?= e((string) $av['prenom']) ?> <?= e((string) ($av['nom'] ?? '')) ?>)</span>
                <?php if (!empty($av['description'])) : ?>
                    <?php
                    // tronque_e() : troncature multibyte ET échappement HTML en un seul appel.
                    // Équivalent à e(mb_strimwidth(...)) mais avec le fallback substr intégré.
                    ?>
                    <p class="rda-comm">« <?= tronque_e((string) $av['description'], 160) ?> »</p>
                <?php endif; ?>
                <time class="rda-temps" datetime="<?= e((string) $av['created_at']) ?>"><?= e((string) $av['created_at']) ?></time>
            </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
