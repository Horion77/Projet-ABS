<?php
/**
 * Fiche d'un lieu — variables : $lieu, $avis, $noteMoy, $erreur, $dejaAvis
 */
$lieu     = $lieu     ?? null;
$avis     = $avis     ?? [];
$noteMoy  = $noteMoy  ?? null;
$erreur   = $erreur   ?? null;
$dejaAvis = $dejaAvis ?? false;
?>
<div class="conteneur place-fiche">
    <?php if ($erreur !== null) : ?>
        <p class="message-erreur-place" role="alert"><?= e($erreur) ?></p>
        <p><a class="btn" href="/carte">Retour à la carte</a></p>
    <?php else : ?>
        <article class="place-article">
            <div class="place-entete">
                <div class="place-illu<?= !empty($lieu['image_url']) ? ' place-illu--photo' : '' ?>" aria-hidden="true">
                    <?php if (!empty($lieu['image_url'])) : ?>
                        <img src="<?= e((string) $lieu['image_url']) ?>" alt="" class="place-illu-img" width="400" height="300" loading="lazy">
                    <?php endif; ?>
                </div>
                <div class="place-entete-texte">
                    <p class="place-crumbs">
                        <a href="/">Accueil</a>
                        <span class="place-crumbs-sep" aria-hidden="true"> / </span>
                        <a href="/carte">Carte</a>
                    </p>
                    <h1 class="place-titre"><?= e((string) $lieu['nom']) ?></h1>
                    <p class="place-meta">
                        <?= e((string) $lieu['categorie']) ?>
                        — <?= e((string) $lieu['ville']) ?>,
                        <a href="/pays?id=<?= (int) $lieu['id_pays'] ?>"><?= e((string) $lieu['pays']) ?></a>
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

            <?php if (!empty($lieu['description'])) : ?>
                <section class="place-bloc" aria-label="Présentation">
                    <h2>Présentation</h2>
                    <p class="place-desc"><?= nl2br(e((string) $lieu['description'])) ?></p>
                </section>
            <?php endif; ?>

            <?php if (isset($lieu['latitude'], $lieu['longitude']) && $lieu['latitude'] !== null && $lieu['longitude'] !== null) : ?>
                <p class="place-coords">
                    <span class="place-coords-label">Coordonnées :</span>
                    <?= e((string) $lieu['latitude']) ?>, <?= e((string) $lieu['longitude']) ?>
                </p>
            <?php endif; ?>

            <?php if (isLoggedIn()) : ?>
                <section class="place-form-avis" aria-labelledby="titre-form-avis">
                    <h2 id="titre-form-avis">Donner votre avis</h2>
                    <?php require __DIR__ . '/../partials/messages.php'; ?>
                    <?php if ($dejaAvis) : ?>
                        <p class="message-vide">Vous avez déjà laissé un avis pour ce lieu.</p>
                    <?php else : ?>
                        <form id="form-avis-lieu" class="form-avis-lieu" method="post" action="/avis" novalidate>
                            <input type="hidden" name="place_id" value="<?= (int) $lieu['id_lieu'] ?>">
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
                    <p><a href="/connexion">Connectez-vous</a> pour publier un avis sur ce lieu.</p>
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
