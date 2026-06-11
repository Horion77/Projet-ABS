<?php
/**
 * Fiche d'un lieu : infos, formulaire d'avis, liste des avis publics
 * avec likes et commentaires (+ réponses).
 *
 * Variables : $lieu, $avis, $commentaires, $likesAvis, $noteMoy, $erreur, $dejaAvis
 */
$lieu         = $lieu         ?? null;
$avis         = $avis         ?? [];
$commentaires = $commentaires ?? [];
$likesAvis    = $likesAvis    ?? [];
$noteMoy      = $noteMoy      ?? null;
$erreur       = $erreur       ?? null;
$dejaAvis     = $dejaAvis     ?? false;
$userId       = isLoggedIn() ? (int) ($_SESSION['user_id'] ?? 0) : 0;
?>

<div class="conteneur place-fiche">
    <?php if ($erreur !== null) : ?>
        <p class="message-erreur-place" role="alert"><?= e($erreur) ?></p>
        <p><a class="btn" href="<?= e(url('carte')) ?>">Retour à la carte</a></p>

    <?php else : ?>
        <?php
        /**
         * @var array<string,mixed> $lieu
         * Ici $lieu est garanti non-null par le contrôleur : si $erreur === null,
         * c'est que LieuModel::trouverParIdAvecLocalisation() a retourné un tableau.
         * L'annotation aide Intelephense à inférer le type (il ne remonte pas le
         * flux de contrôle à travers les blocs if/else alternatifs).
         */
        ?>

        <article class="place-article">

            <!-- En-tête lieu -->
            <div class="place-entete">
                <div class="place-illu<?= !empty($lieu['image_url']) ? ' place-illu--photo' : '' ?>" aria-hidden="true">
                    <?php if (!empty($lieu['image_url'])) : ?>
                        <img src="<?= e((string) $lieu['image_url']) ?>" alt="" class="place-illu-img" width="400" height="300" loading="lazy">
                    <?php endif; ?>
                </div>

                <div class="place-entete-texte">
                    <p class="place-crumbs">
                        <a href="<?= e(url()) ?>">Accueil</a>
                        <span class="place-crumbs-sep" aria-hidden="true"> / </span>
                        <a href="<?= e(url('carte')) ?>">Carte</a>
                    </p>
                    <h1 class="place-titre"><?= e((string) $lieu['nom']) ?></h1>
                    <p class="place-meta">
                        <?= e((string) $lieu['categorie']) ?>
                        — <?= e((string) $lieu['ville']) ?>,
                        <a href="<?= e(url('pays')) ?>?id=<?= (int) $lieu['id_pays'] ?>"><?= e((string) $lieu['pays']) ?></a>
                    </p>
                    <p class="place-note-entete">
                        <?php if ($noteMoy !== null) : ?>
                            <strong>Note moyenne :</strong> <?= e((string) $noteMoy) ?>/5
                        <?php else : ?>
                            <span class="place-sans-note">Aucun avis pour l'instant.</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>


            <!-- Description -->
            <?php if (!empty($lieu['description'])) : ?>
                <section class="place-bloc" aria-label="Présentation">
                    <h2>Présentation</h2>
                    <?php
                    // nl2br(e(...)) : l'ordre est critique. e() échappe d'abord les caractères
                    // HTML (<, >, &…), puis nl2br() convertit \n en <br> sur le texte déjà
                    // sécurisé. Inverser l'ordre convertirait les \n avant d'échapper, ce
                    // qui laisserait des <br> non échappés et permettrait de l'injection HTML.
                    ?>
                    <p class="place-desc"><?= nl2br(e((string) $lieu['description'])) ?></p>
                </section>
            <?php endif; ?>


            <?php if (isset($lieu['latitude'], $lieu['longitude']) && $lieu['latitude'] !== null) : ?>
                <p class="place-coords">
                    <span class="place-coords-label">Coordonnées :</span>
                    <?= e((string) $lieu['latitude']) ?>, <?= e((string) $lieu['longitude']) ?>
                </p>
            <?php endif; ?>


            <!-- Formulaire avis -->
            <?php if (isLoggedIn()) : ?>
                <section class="place-form-avis" aria-labelledby="titre-form-avis">
                    <h2 id="titre-form-avis">Donner votre avis</h2>
                    <?php require __DIR__ . '/../partials/messages.php'; ?>
                    <?php if ($dejaAvis) : ?>
                        <p class="message-vide">Vous avez déjà laissé un avis pour ce lieu.</p>
                    <?php else : ?>
                        <form id="form-avis-lieu" class="form-avis-lieu" method="post" action="<?= e(url('avis')) ?>" novalidate>
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
                    <p><a href="<?= e(url('connexion')) ?>">Connectez-vous</a> pour publier un avis sur ce lieu.</p>
                </section>
            <?php endif; ?>

        </article>


        <!-- Liste des avis publics -->
        <section class="place-avis" aria-label="Avis">
            <h2>Avis</h2>

            <?php if (count($avis) === 0) : ?>
                <p class="message-vide">Aucun avis public sur ce lieu pour l'instant.</p>

            <?php else : ?>
                <ul class="liste-avis-lieu">

                    <?php foreach ($avis as $a) :
                        $idAvis  = (int) $a['id_avis'];
                        $auteur  = trim(($a['prenom'] ?? '') . ' ' . ($a['nom'] ?? '')) ?: 'Utilisateur';
                        $dt      = $a['created_at'] ? new \DateTimeImmutable((string) $a['created_at']) : null;
                        $nbLikes = (int) ($a['nb_likes'] ?? 0);
                        $nbCom   = (int) ($a['nb_commentaires'] ?? 0);
                        $jaiLike = $likesAvis[$idAvis] ?? false;
                        $comsSorted = $commentaires[$idAvis] ?? [];
                    ?>

                    <li class="carte-avis-lieu" id="avis-<?= $idAvis ?>">

                        <!-- En-tête de l'avis -->
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
                            <p class="avis-texte">«&nbsp;<?= nl2br(e((string) $a['description'])) ?>&nbsp;»</p>
                        <?php endif; ?>

                        <?php if (!empty($a['photo_thumb'])) : ?>
                            <p class="avis-photo-wrap">
                                <img class="avis-photo-thumb" src="<?= e((string) $a['photo_thumb']) ?>" alt="" width="100" height="100" loading="lazy">
                            </p>
                        <?php endif; ?>


                        <!-- Actions : like + toggle commentaires -->
                        <div class="avis-actions">

                            <!-- Bouton like avis (checkbox = état visuel instantané, AJAX = sync serveur) -->
                            <?php if (isLoggedIn()) : ?>
                                <span class="like-avis-wrap" data-id-avis="<?= $idAvis ?>">
                                    <input type="checkbox"
                                           id="like-avis-<?= $idAvis ?>"
                                           class="like-toggle"
                                           <?= $jaiLike ? 'checked' : '' ?>
                                           aria-label="<?= $jaiLike ? 'Retirer mon like' : 'Liker cet avis' ?>">
                                    <label for="like-avis-<?= $idAvis ?>" class="like-label">
                                        ❤ <span class="like-count"><?= $nbLikes ?></span>
                                    </label>
                                </span>
                            <?php else : ?>
                                <span class="like-readonly" title="Connectez-vous pour liker">
                                    ❤ <span class="like-count"><?= $nbLikes ?></span>
                                </span>
                            <?php endif; ?>

                            <!-- Toggle section commentaires -->
                            <button class="btn-toggle-com" data-target="com-<?= $idAvis ?>" aria-expanded="false">
                                <?= $nbCom ?> commentaire<?= $nbCom !== 1 ? 's' : '' ?>
                            </button>

                            <!-- Signaler l'avis (utilisateurs connectés seulement) -->
                            <?php if (isLoggedIn()) : ?>
                                <details class="signaler-wrap">
                                    <summary class="btn-signaler" title="Signaler cet avis">⚑ Signaler</summary>
                                    <form class="form-signaler" data-type="avis" data-id-cible="<?= $idAvis ?>">
                                        <label>Motif
                                            <select name="motif" required>
                                                <option value="spam">Spam</option>
                                                <option value="insulte">Insulte / haine</option>
                                                <option value="inapproprie">Contenu inapproprié</option>
                                                <option value="hors_sujet">Hors sujet</option>
                                                <option value="autre">Autre</option>
                                            </select>
                                        </label>
                                        <textarea name="details" maxlength="500" rows="2" placeholder="Détails (optionnel)"></textarea>
                                        <button type="submit" class="btn btn--sm">Envoyer le signalement</button>
                                        <span class="signaler-msg" hidden></span>
                                    </form>
                                </details>
                            <?php endif; ?>

                        </div>


                        <!-- Section commentaires (masquée par défaut) -->
                        <div class="section-commentaires" id="com-<?= $idAvis ?>" hidden>

                            <?php if (!empty($comsSorted)) :

                                // Sépare racines et réponses
                                        // Sépare la liste plate en deux groupes :
                                // racines  → commentaires sans parent (premier niveau)
                                // reponses → indexées par id_parent pour accès O(1) lors du rendu
                                // CommentaireModel::parAvis() retourne une liste plate ; c'est ici
                                // qu'on reconstruit l'arbre à deux niveaux (racine + réponses directes).
                                $racines  = array_filter($comsSorted, fn($c) => $c['id_parent'] === null);
                                $reponses = [];
                                foreach ($comsSorted as $c) {
                                    if ($c['id_parent'] !== null) {
                                        $reponses[(int) $c['id_parent']][] = $c;
                                    }
                                }

                                foreach ($racines as $com) :
                                    $idCom      = (int) $com['id_commentaire'];
                                    $auteurCom  = trim(($com['prenom'] ?? '') . ' ' . ($com['nom'] ?? '')) ?: 'Utilisateur';
                                    $dtCom      = $com['created_at'] ? new \DateTimeImmutable((string) $com['created_at']) : null;
                                    $nbLikesCom = (int) ($com['nb_likes'] ?? 0);
                            ?>

                                <div class="commentaire" id="com-item-<?= $idCom ?>">
                                    <div class="com-ent">
                                        <strong class="com-auteur"><?= e($auteurCom) ?></strong>
                                        <?php if ($dtCom) : ?>
                                            <time class="com-date" datetime="<?= e($dtCom->format('c')) ?>"><?= e($dtCom->format('d/m/Y')) ?></time>
                                        <?php endif; ?>
                                    </div>
                                    <p class="com-texte"><?= nl2br(e((string) $com['texte'])) ?></p>

                                    <div class="com-actions">
                                        <!-- Like commentaire -->
                                        <?php if (isLoggedIn()) : ?>
                                            <span class="like-com-wrap" data-id-com="<?= $idCom ?>">
                                                <input type="checkbox"
                                                       id="like-com-<?= $idCom ?>"
                                                       class="like-toggle"
                                                       aria-label="Liker ce commentaire">
                                                <label for="like-com-<?= $idCom ?>" class="like-label like-label--sm">
                                                    ❤ <span class="like-count"><?= $nbLikesCom ?></span>
                                                </label>
                                            </span>
                                        <?php else : ?>
                                            <span class="like-readonly">
                                                ❤ <span class="like-count"><?= $nbLikesCom ?></span>
                                            </span>
                                        <?php endif; ?>

                                        <!-- Toggle réponse -->
                                        <?php if (isLoggedIn()) : ?>
                                            <button class="btn-toggle-reponse" data-target="rep-<?= $idCom ?>">Répondre</button>

                                            <!-- Signaler le commentaire -->
                                            <details class="signaler-wrap signaler-wrap--sm">
                                                <summary class="btn-signaler" title="Signaler ce commentaire">⚑ Signaler</summary>
                                                <form class="form-signaler" data-type="commentaire" data-id-cible="<?= $idCom ?>">
                                                    <label>Motif
                                                        <select name="motif" required>
                                                            <option value="spam">Spam</option>
                                                            <option value="insulte">Insulte / haine</option>
                                                            <option value="inapproprie">Contenu inapproprié</option>
                                                            <option value="hors_sujet">Hors sujet</option>
                                                            <option value="autre">Autre</option>
                                                        </select>
                                                    </label>
                                                    <textarea name="details" maxlength="500" rows="2" placeholder="Détails (optionnel)"></textarea>
                                                    <button type="submit" class="btn btn--sm">Envoyer le signalement</button>
                                                    <span class="signaler-msg" hidden></span>
                                                </form>
                                            </details>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Réponses au commentaire (indentées) -->
                                    <?php if (!empty($reponses[$idCom])) :
                                        foreach ($reponses[$idCom] as $rep) :
                                            $auteurRep = trim(($rep['prenom'] ?? '') . ' ' . ($rep['nom'] ?? '')) ?: 'Utilisateur';
                                            $dtRep     = $rep['created_at'] ? new \DateTimeImmutable((string) $rep['created_at']) : null;
                                    ?>
                                        <div class="commentaire commentaire--reponse">
                                            <div class="com-ent">
                                                <strong class="com-auteur"><?= e($auteurRep) ?></strong>
                                                <?php if ($dtRep) : ?>
                                                    <time class="com-date" datetime="<?= e($dtRep->format('c')) ?>"><?= e($dtRep->format('d/m/Y')) ?></time>
                                                <?php endif; ?>
                                            </div>
                                            <p class="com-texte"><?= nl2br(e((string) $rep['texte'])) ?></p>
                                        </div>
                                    <?php endforeach; endif; ?>

                                    <!-- Formulaire réponse (masqué) -->
                                    <?php if (isLoggedIn()) : ?>
                                        <form class="form-reponse" id="rep-<?= $idCom ?>" method="post"
                                              action="<?= e(url('commentaire')) ?>" hidden>
                                            <input type="hidden" name="id_avis"   value="<?= $idAvis ?>">
                                            <input type="hidden" name="id_lieu"   value="<?= (int) $lieu['id_lieu'] ?>">
                                            <input type="hidden" name="id_parent" value="<?= $idCom ?>">
                                            <textarea name="texte" rows="2" maxlength="2000" placeholder="Votre réponse…" required></textarea>
                                            <button type="submit" class="btn btn--sm">Envoyer</button>
                                        </form>
                                    <?php endif; ?>
                                </div>

                                <?php endforeach; ?>

                            <?php else : ?>
                                <p class="com-vide">Aucun commentaire pour l'instant.</p>
                            <?php endif; ?>


                            <!-- Formulaire nouveau commentaire -->
                            <?php if (isLoggedIn()) : ?>
                                <form class="form-commentaire" method="post" action="<?= e(url('commentaire')) ?>">
                                    <input type="hidden" name="id_avis" value="<?= $idAvis ?>">
                                    <input type="hidden" name="id_lieu" value="<?= (int) $lieu['id_lieu'] ?>">
                                    <div class="groupe-champ">
                                        <label for="com-texte-<?= $idAvis ?>">Ajouter un commentaire</label>
                                        <textarea id="com-texte-<?= $idAvis ?>" name="texte" rows="2" maxlength="2000"
                                                  placeholder="Votre commentaire…" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn--sm">Commenter</button>
                                </form>
                            <?php else : ?>
                                <p class="com-login"><a href="<?= e(url('connexion')) ?>">Connectez-vous</a> pour commenter.</p>
                            <?php endif; ?>

                        </div><!-- /.section-commentaires -->

                    </li>

                    <?php endforeach; ?>
                </ul>

            <?php endif; ?>
        </section>

    <?php endif; ?>
</div>


<!-- JS : likes AJAX (checkbox) + toggle commentaires -->
<script>
(function () {

    const URL_LIKE_AVIS    = <?= json_encode(url('avis/liker')) ?>;
    const URL_LIKE_COM     = <?= json_encode(url('commentaire/liker')) ?>;
    const URL_SIGNALEMENT  = <?= json_encode(url('signalement')) ?>;


    /* ---- Toggle section commentaires ---- */
    document.querySelectorAll('.btn-toggle-com').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(btn.dataset.target);
            if (!target) return;
            const open = target.hidden;
            target.hidden = !open;
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });


    /* ---- Toggle formulaire réponse ---- */
    document.querySelectorAll('.btn-toggle-reponse').forEach(btn => {
        btn.addEventListener('click', () => {
            const form = document.getElementById(btn.dataset.target);
            if (form) form.hidden = !form.hidden;
        });
    });


    /* ---- Like avis (checkbox + AJAX) ---- */
    document.querySelectorAll('.like-avis-wrap').forEach(wrap => {
        const cb    = wrap.querySelector('input[type="checkbox"]');
        const count = wrap.querySelector('.like-count');
        if (!cb || !count) return;

        cb.addEventListener('change', async () => {
            const idAvis = wrap.dataset.idAvis;
            try {
                const res  = await fetch(URL_LIKE_AVIS, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id_avis=' + encodeURIComponent(idAvis),
                });
                const data = await res.json();
                // Mise à jour optimiste : la checkbox a déjà changé visuellement au clic.
                // On synchronise le compteur et l'état réel avec la réponse serveur.
                count.textContent = data.count;
                cb.checked = data.liked;
                cb.setAttribute('aria-label', data.liked ? 'Retirer mon like' : 'Liker cet avis');
            } catch (e) {
                // Rollback : l'appel a échoué, on remet la checkbox dans son état précédent.
                cb.checked = !cb.checked;
                console.error('Erreur like avis', e);
            }
        });
    });


    /* ---- Like commentaire (checkbox + AJAX) ---- */
    document.querySelectorAll('.like-com-wrap').forEach(wrap => {
        const cb    = wrap.querySelector('input[type="checkbox"]');
        const count = wrap.querySelector('.like-count');
        if (!cb || !count) return;

        cb.addEventListener('change', async () => {
            const idCom = wrap.dataset.idCom;
            try {
                const res  = await fetch(URL_LIKE_COM, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id_commentaire=' + encodeURIComponent(idCom),
                });
                const data = await res.json();
                count.textContent = data.count;
                cb.checked = data.liked;
            } catch (e) {
                cb.checked = !cb.checked;
                console.error('Erreur like commentaire', e);
            }
        });
    });


    /* ---- Signalement avis / commentaire (formulaire AJAX) ---- */
    document.querySelectorAll('.form-signaler').forEach(form => {
        const msg = form.querySelector('.signaler-msg');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const type    = form.dataset.type;
            const idCible = form.dataset.idCible;
            const motif   = form.querySelector('[name="motif"]').value;
            const details = form.querySelector('[name="details"]').value;

            const params = new URLSearchParams();
            params.set('type', type);
            params.set('id_cible', idCible);
            params.set('motif', motif);
            if (details) params.set('details', details);

            try {
                const res  = await fetch(URL_SIGNALEMENT, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString(),
                });
                const data = await res.json();
                if (data.success) {
                    msg.hidden = false;
                    msg.textContent = data.deja
                        ? 'Vous aviez déjà signalé ce contenu.'
                        : 'Signalement envoyé, merci !';
                    msg.classList.add('signaler-msg--ok');
                    form.querySelector('button[type="submit"]').disabled = true;
                } else {
                    msg.hidden = false;
                    msg.textContent = data.erreur || 'Erreur lors du signalement.';
                    msg.classList.add('signaler-msg--err');
                }
            } catch (err) {
                msg.hidden = false;
                msg.textContent = 'Connexion impossible.';
                console.error('Erreur signalement', err);
            }
        });
    });

})();
</script>
