<?php
/**
 * Profil utilisateur — variables : $util, $mesAvis
 */
$mesAvis = $mesAvis ?? [];
?>
<div class="conteneur conteneur-profil">
    <h1>Profil</h1>
    <?php require __DIR__ . '/../partials/messages.php'; ?>

    <section class="bloc-profil" aria-labelledby="titre-infos">
        <h2 id="titre-infos">Vos informations</h2>
        <?php if (!empty($util['avatar_url'])) : ?>
            <p class="profil-avatar-wrap">
                <img class="profil-avatar" src="<?= e((string) $util['avatar_url']) ?>" alt="" width="96" height="96" loading="lazy">
            </p>
        <?php endif; ?>
        <dl class="grille-profil">
            <dt>Prénom</dt><dd><?= e((string) $util['prenom']) ?></dd>
            <dt>Nom</dt><dd><?= e((string) $util['nom']) ?></dd>
            <dt>E-mail</dt><dd><?= e((string) $util['email']) ?></dd>
            <dt>Inscription</dt><dd><?= e((string) $util['created_at']) ?></dd>
        </dl>
        <?php if (!empty($util['bio'])) : ?>
            <div class="profil-bio">
                <h3 class="profil-bio-titre">Bio</h3>
                <p class="profil-bio-texte"><?= nl2br(e((string) $util['bio'])) ?></p>
            </div>
        <?php endif; ?>
    </section>

    <!-- ─── Modifier mes informations ─────────────────────────────────── -->
    <details class="details-profil">
        <summary>✏️ Modifier mes informations</summary>
        <form method="POST" action="/profil/modifier-infos" class="form-profil" novalidate>
            <div class="champ-profil">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom"
                       value="<?= e((string) $util['prenom']) ?>"
                       minlength="2" maxlength="80" required>
            </div>
            <div class="champ-profil">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom"
                       value="<?= e((string) $util['nom']) ?>"
                       minlength="2" maxlength="80" required>
            </div>
            <div class="champ-profil">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email"
                       value="<?= e((string) $util['email']) ?>"
                       required>
            </div>
            <button type="submit" class="btn-profil-violet">Enregistrer</button>
        </form>
    </details>

    <!-- ─── Changer mon mot de passe ───────────────────────────────────── -->
    <details class="details-profil">
        <summary>🔒 Changer mon mot de passe</summary>
        <form method="POST" action="/profil/modifier-password" class="form-profil" novalidate>
            <div class="champ-profil">
                <label for="password">Nouveau mot de passe</label>
                <input type="password" id="password" name="password" minlength="6" required>
            </div>
            <div class="champ-profil">
                <label for="password_confirm">Confirmer le mot de passe</label>
                <input type="password" id="password_confirm" name="password_confirm" minlength="6" required>
            </div>
            <button type="submit" class="btn-profil-violet">Changer le mot de passe</button>
        </form>
    </details>

    <!-- ─── Photo de profil ─────────────────────────────────────────────── -->
    <details class="details-profil">
        <summary>📷 Photo de profil</summary>
        <form method="POST" action="/profil/modifier-avatar" class="form-profil"
              enctype="multipart/form-data" novalidate>
            <?php
            $avatarSrc = !empty($util['avatar'])
                ? e((string) $util['avatar'])
                : (!empty($util['avatar_url']) ? e((string) $util['avatar_url']) : null);
            ?>
            <?php if ($avatarSrc) : ?>
                <p class="apercu-avatar-wrap">
                    <img class="apercu-avatar"
                         src="<?= $avatarSrc ?>"
                         alt="Avatar actuel" width="80" height="80" loading="lazy">
                </p>
            <?php else : ?>
                <p class="apercu-avatar-vide">Aucune photo de profil pour le moment.</p>
            <?php endif; ?>
            <div class="champ-profil">
                <label for="avatar">Choisir une image (JPG ou PNG, max 2 Mo)</label>
                <input type="file" id="avatar" name="avatar"
                       accept="image/jpeg,image/png" required>
            </div>
            <button type="submit" class="btn-profil-violet">Mettre à jour la photo</button>
        </form>
    </details>

    <section class="bloc-avis" aria-labelledby="titre-mes-avis">
        <h2 id="titre-mes-avis">Vos avis (<?= count($mesAvis) ?>)</h2>
        <?php if (count($mesAvis) === 0) : ?>
            <p class="message-vide">Vous n’avez pas encore publié d’avis.</p>
        <?php else : ?>
            <ul class="liste-avis-profil">
                <?php foreach ($mesAvis as $a) : ?>
                <li>
                    <strong class="note-profil">Note : <?= (int) $a['note'] ?> / 5</strong>
                    <span class="cible-avis"> —
                        <?php
                        if (!empty($a['id_lieu'])) {
                            echo 'Lieu : ' . e((string) ($a['libelle_lieu'] ?? '—'));
                        } elseif (!empty($a['id_ville'])) {
                            echo 'Ville : ' . e((string) ($a['libelle_ville'] ?? '—'));
                        } elseif (!empty($a['id_pays'])) {
                            echo 'Pays : ' . e((string) ($a['libelle_pays'] ?? '—'));
                        } else {
                            echo 'Cible inconnue';
                        }
                        ?>
                    </span>
                    <?php if (!empty($a['titre'])) : ?>
                        <p class="comm-avis comm-avis-titre"><strong><?= e((string) $a['titre']) ?></strong></p>
                    <?php endif; ?>
                    <?php if (!empty($a['description'])) : ?>
                        <p class="comm-avis"><?= e((string) $a['description']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($a['photo_thumb'])) : ?>
                        <p class="avis-photo-wrap">
                            <img class="avis-photo-thumb" src="<?= e((string) $a['photo_thumb']) ?>" alt="" width="100" height="100" loading="lazy">
                        </p>
                    <?php endif; ?>
                    <time datetime="<?= e((string) $a['created_at']) ?>"><?= e((string) $a['created_at']) ?></time>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
