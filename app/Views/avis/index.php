<?php
/** Flux global des avis publics — reçoit : $liste */
$liste = $liste ?? [];
?>
    <div class="conteneur conteneur-avis-liste">
        <h1 class="titre-page-avis">Avis récents</h1>
        <p class="intro-avis">Tous les avis publics publiés sur ABS, du plus récent au plus ancien.</p>
        <?= displaySuccess() ?>
        <?= displayErrors() ?>

        <?php if (count($liste) === 0) : ?>
            <p class="message-vide">Aucun avis public pour l’instant.</p>
        <?php else : ?>
            <ul class="liste-avis-globale">
                <?php foreach ($liste as $row) :
                    $auteur = trim((string) ($row['prenom'] ?? '') . ' ' . (string) ($row['nom'] ?? ''));
                    if ($auteur === '') {
                        $auteur = 'Utilisateur';
                    }
                    if (!empty($row['lieu_nom'])) {
                        $cible = 'Lieu : ' . (string) $row['lieu_nom'];
                    } elseif (!empty($row['ville_nom'])) {
                        $cible = 'Ville : ' . (string) $row['ville_nom'];
                    } elseif (!empty($row['pays_nom'])) {
                        $cible = 'Pays : ' . (string) $row['pays_nom'];
                    } else {
                        $cible = '—';
                    }
                    $dt = !empty($row['created_at']) ? new \DateTimeImmutable((string) $row['created_at']) : null;
                    $lieuId = isset($row['id_lieu']) ? (int) $row['id_lieu'] : 0;
                    ?>
                <li class="carte-avis-global">
                    <div class="carte-avis-global-ent">
                        <strong class="note-badge"><?= (int) $row['note'] ?>/5</strong>
                        <span class="avis-auteur"><?= e($auteur) ?></span>
                        <?php if ($dt) : ?>
                            <time class="avis-date" datetime="<?= e($dt->format('c')) ?>"><?= e($dt->format('d/m/Y à H:i')) ?></time>
                        <?php endif; ?>
                    </div>
                    <p class="avis-cible"><?= e($cible) ?></p>
                    <?php if (!empty($row['description'])) : ?>
                        <p class="avis-texte">« <?= nl2br(e((string) $row['description'])) ?> »</p>
                    <?php endif; ?>
                    <?php if (!empty($row['photo_thumb'])) : ?>
                        <p class="avis-photo-wrap">
                            <img class="avis-photo-thumb" src="<?= e((string) $row['photo_thumb']) ?>" alt="" width="120" height="120" loading="lazy">
                        </p>
                    <?php endif; ?>
                    <?php if ($lieuId > 0) : ?>
                        <p class="avis-lien-lieu"><a href="place.php?id=<?= $lieuId ?>">Voir le lieu</a></p>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
