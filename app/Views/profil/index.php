<?php
/** Profil (vue) — reçoit : $util, $mesAvis */
$mesAvis = $mesAvis ?? [];
?>
    <div class="conteneur conteneur-profil">
        <h1>Profil</h1>
        <?= displaySuccess() ?>

        <section class="bloc-profil" aria-labelledby="titre-infos">
            <h2 id="titre-infos">Vos informations</h2>
            <?php if (!empty($util['avatar_url'])) : ?>
                <p class="profil-avatar-wrap">
                    <img class="profil-avatar" src="<?= e((string) $util['avatar_url']) ?>" alt="" width="96" height="96" loading="lazy">
                </p>
            <?php endif; ?>
            <dl class="grille-profil">
                <dt>Prénom</dt><dd><?= e($util['prenom']) ?></dd>
                <dt>Nom</dt><dd><?= e($util['nom']) ?></dd>
                <dt>E-mail</dt><dd><?= e($util['email']) ?></dd>
                <dt>Inscription</dt><dd><?= e($util['created_at']) ?></dd>
            </dl>
            <?php if (!empty($util['bio'])) : ?>
                <div class="profil-bio">
                    <h3 class="profil-bio-titre">Bio</h3>
                    <p class="profil-bio-texte"><?= nl2br(e((string) $util['bio'])) ?></p>
                </div>
            <?php endif; ?>
        </section>

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
                        echo 'Lieu : ' . e($a['libelle_lieu'] ?? '—');
                    } elseif (!empty($a['id_ville'])) {
                        echo 'Ville : ' . e($a['libelle_ville'] ?? '—');
                    } elseif (!empty($a['id_pays'])) {
                        echo 'Pays : ' . e($a['libelle_pays'] ?? '—');
                    } else {
                        echo 'Cible inconnue';
                    }
                    ?>
                        </span>
                        <?php if (!empty($a['titre'])) : ?>
                            <p class="comm-avis comm-avis-titre"><strong><?= e((string) $a['titre']) ?></strong></p>
                        <?php endif; ?>
                        <?php if (!empty($a['description'])) : ?>
                            <p class="comm-avis"><?= e($a['description']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($a['photo_thumb'])) : ?>
                            <p class="avis-photo-wrap">
                                <img class="avis-photo-thumb" src="<?= e((string) $a['photo_thumb']) ?>" alt="" width="100" height="100" loading="lazy">
                            </p>
                        <?php endif; ?>
                        <time datetime="<?= e($a['created_at']) ?>"><?= e($a['created_at']) ?></time>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
