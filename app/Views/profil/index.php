<?php
/**
 * Profil utilisateur — variables : $util, $mesAvis
 */
$mesAvis = $mesAvis ?? [];
$util = $util ?? [];
?>
<div class="conteneur conteneur-profil">
    <h1>Profil</h1>
    <?php require __DIR__ . '/../partials/messages.php'; ?>

    <section class="bloc-profil" aria-labelledby="titre-infos">
        <h2 id="titre-infos">Vos informations</h2>
<?php 
        // Double source d'avatar — ordre de priorité :
        //   1. $util['avatar']     → chemin local relatif (ex. "assets/uploads/avatars/avatar_xxx.jpg")
        //                            uploadé par l'utilisateur via ProfilController::modifierAvatar()
        //   2. $util['avatar_url'] → URL externe (ex. Dicebear SVG généré par le seed)
        // La vérification strtolower(...) === 'null' gère les anciennes lignes en BDD où
        // la colonne stockait littéralement la chaîne "null" au lieu de la valeur SQL NULL.
        $valAvatar = trim((string)($util['avatar'] ?? ''));
        if (strtolower($valAvatar) === 'null') $valAvatar = '';

        $valAvatarUrl = trim((string)($util['avatar_url'] ?? ''));
        if (strtolower($valAvatarUrl) === 'null') $valAvatarUrl = '';

        // ltrim($valAvatar, '/') + url() : reconstruit l'URL publique complète en tenant
        // compte de APP_BASE_URL (MAMP). Un chemin "assets/..." devient "/Projet-ABS/public/assets/...".
        $avatarAffiche = null;
        if ($valAvatar !== '') {
            $avatarAffiche = e(url(ltrim($valAvatar, '/')));
        } elseif ($valAvatarUrl !== '') {
            $avatarAffiche = e($valAvatarUrl);
        }
        ?>

        <?php if ($avatarAffiche) : ?>
            <div class="profil-avatar-wrap">
                <img class="profil-avatar" 
                     src="<?= $avatarAffiche ?>" 
                     alt="Photo de profil" 
                     width="80" height="80" 
                     loading="lazy">
            </div>
        <?php else : ?>
            <div class="profil-avatar-wrap profil-avatar-vide">
                <?php
                // Initiales générées en fallback quand aucun avatar n'est disponible.
                // mb_substr + mb_strtoupper : multibyte-safe pour les prénoms accentués
                // (Élodie → "É", pas "E" ou un octet corrompu avec substr/strtoupper).
                ?>
                <span class="profil-avatar-initiales">
                    <?= mb_strtoupper(mb_substr((string)($util['prenom'] ?? ''), 0, 1)) ?>
                    <?= mb_strtoupper(mb_substr((string)($util['nom'] ?? ''), 0, 1)) ?>
                </span>
            </div>
        <?php endif; ?>
        <dl class="grille-profil">
            <dt>👤 Prénom</dt><dd><?= e((string) $util['prenom']) ?></dd>
            <dt>👤 Nom</dt><dd><?= e((string) $util['nom']) ?></dd>
            <dt>✉️ E-mail</dt><dd><?= e((string) $util['email']) ?></dd>
            <dt>📅 Inscription</dt><dd><?= e(date('d/m/Y', strtotime((string) $util['created_at']))) ?></dd>
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
        <form method="POST" action="<?= e(url('profil/modifier-infos')) ?>" class="form-profil" novalidate>
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
        <form method="POST" action="<?= e(url('profil/modifier-password')) ?>" class="form-profil" novalidate>
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
        <form method="POST" action="<?= e(url('profil/modifier-avatar')) ?>" class="form-profil"
              enctype="multipart/form-data" novalidate>
            <?php
            // Même logique de nettoyage
            $valAvatarForm = trim((string)($util['avatar'] ?? ''));
            if (strtolower($valAvatarForm) === 'null') $valAvatarForm = '';
            
            $valAvatarUrlForm = trim((string)($util['avatar_url'] ?? ''));
            if (strtolower($valAvatarUrlForm) === 'null') $valAvatarUrlForm = '';

            // 2. Sélection de la bonne source avec url()
            $avatarSrc = null;
            if ($valAvatarForm !== '') {
                $avatarSrc = e(url(ltrim($valAvatarForm, '/'))); 
            } elseif ($valAvatarUrlForm !== '') {
                $avatarSrc = e($valAvatarUrlForm);
            }
            ?>
            
            <?php if ($avatarSrc) : ?>
                <p class="apercu-avatar-wrap">
                    <img class="profil-avatar"
                         src="<?= $avatarSrc ?>"
                         alt="Avatar actuel" width="80" height="80" loading="lazy">
                </p>
            <?php else : ?>
                <div class="profil-avatar-wrap profil-avatar-vide" style="margin: 0 auto 1rem auto;">
                    <span class="profil-avatar-initiales">
                        <?= mb_strtoupper(mb_substr((string)($util['prenom'] ?? ''), 0, 1)) ?>
                        <?= mb_strtoupper(mb_substr((string)($util['nom'] ?? ''), 0, 1)) ?>
                    </span>
                </div>
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
            <p class="message-vide">Vous n'avez pas encore publié d'avis.</p>
        <?php else : ?>
            <ul class="liste-avis-profil">
                <?php foreach ($mesAvis as $a) : ?>
                <li>
                    <strong class="note-profil" title="Note de <?= (int) $a['note'] ?> sur 5" aria-label="Note de <?= (int) $a['note'] ?> sur 5">
                        <?= str_repeat('⭐', (int) $a['note']) . str_repeat('☆', 5 - (int) $a['note']) ?>
                    </strong>
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
                    <time datetime="<?= e((string) $a['created_at']) ?>">Le <?= e(date('d/m/Y à H\hi', strtotime((string) $a['created_at']))) ?></time>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
