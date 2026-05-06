<?php
/**
 * Liste paginée des avis publics.
 * Variables : $liste, $page, $pagesTotal, $totalAvis, $parPage, $filtreNote, $filtrePays, $paysListe
 */
$liste      = $liste      ?? [];
$page       = $page       ?? 1;
$pagesTotal = $pagesTotal ?? 1;
$totalAvis  = $totalAvis  ?? 0;
$filtreNote = $filtreNote ?? null;
$filtrePays = $filtrePays ?? null;
$paysListe  = $paysListe  ?? [];

$qBase = [];
if ($filtreNote !== null) {
    $qBase['rating'] = (string) $filtreNote;
}
if ($filtrePays !== null) {
    $qBase['country_id'] = (string) $filtrePays;
}
?>
<div class="conteneur conteneur-avis-liste">
    <h1 class="titre-page-avis">Tous les avis</h1>
    <p class="intro-avis"><?= (int) $totalAvis ?> avis public<?= $totalAvis > 1 ? 's' : '' ?> sur des lieux.</p>

    <form class="form-filtres-avis" method="get" action="<?= e(url('avis')) ?>" aria-label="Filtrer la liste">
        <div class="filtres-row">
            <label for="f-rating">Note</label>
            <select id="f-rating" name="rating">
                <option value="">Toutes</option>
                <?php for ($n = 5; $n >= 1; $n--) : ?>
                    <option value="<?= $n ?>"<?= $filtreNote === $n ? ' selected' : '' ?>><?= $n ?>/5</option>
                <?php endfor; ?>
            </select>
            <label for="f-pays">Pays</label>
            <select id="f-pays" name="country_id">
                <option value="">Tous</option>
                <?php foreach ($paysListe as $p) : ?>
                    <option value="<?= (int) $p['id_pays'] ?>"<?= $filtrePays === (int) $p['id_pays'] ? ' selected' : '' ?>><?= e((string) $p['nom']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-filtre-avis">Appliquer</button>
            <a class="lien-reset-filtres" href="<?= e(url('avis')) ?>">Réinitialiser</a>
        </div>
    </form>

    <?php require __DIR__ . '/../partials/messages.php'; ?>

    <?php if (count($liste) === 0) : ?>
        <p class="message-vide">Aucun avis ne correspond à ces critères.</p>
    <?php else : ?>
        <ul class="liste-avis-globale">
            <?php foreach ($liste as $row) :
                $auteur = trim((string) ($row['prenom'] ?? '') . ' ' . (string) ($row['nom'] ?? ''));
                if ($auteur === '') {
                    $auteur = 'Utilisateur';
                }
                $dt = !empty($row['created_at']) ? new \DateTimeImmutable((string) $row['created_at']) : null;
                $lieuId = isset($row['id_lieu']) ? (int) $row['id_lieu'] : 0;
                $desc = (string) ($row['description'] ?? '');
                ?>
            <li class="carte-avis-global">
                <div class="carte-avis-global-ent">
                    <span class="stars-wrap"><?= starsRatingHtml((int) $row['note']) ?></span>
                    <span class="avis-auteur"><?= e($auteur) ?></span>
                    <?php if ($dt) : ?>
                        <time class="avis-date" datetime="<?= e($dt->format('c')) ?>"><?= e($dt->format('d/m/Y à H:i')) ?></time>
                    <?php endif; ?>
                </div>
                <p class="avis-cible">
                    <strong><?= e((string) ($row['lieu_nom'] ?? '—')) ?></strong>
                    — <?= e((string) ($row['pays_nom'] ?? '')) ?>
                </p>
                <?php if (!empty($row['titre'])) : ?>
                    <p class="avis-titre-liste"><?= e((string) $row['titre']) ?></p>
                <?php endif; ?>
                <?php if ($desc !== '') : ?>
                    <p class="avis-texte">« <?= tronque_e($desc, 220) ?> »</p>
                <?php endif; ?>
                <?php if (!empty($row['photo_thumb'])) : ?>
                    <p class="avis-photo-wrap">
                        <img class="avis-photo-thumb" src="<?= e((string) $row['photo_thumb']) ?>" alt="" width="120" height="120" loading="lazy">
                    </p>
                <?php endif; ?>
                <?php if ($lieuId > 0) : ?>
                    <p class="avis-lien-lieu"><a href="<?= e(url('lieu')) ?>?id=<?= $lieuId ?>">Voir le lieu</a></p>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>

        <nav class="pagination-avis" aria-label="Pagination">
            <?php if ($page > 1) : ?>
                <a class="page-nav" href="<?= e(url('avis')) ?>?<?= e(reviews_pagination_query($qBase, $page - 1)) ?>">Précédent</a>
            <?php else : ?>
                <span class="page-nav page-nav--disabled">Précédent</span>
            <?php endif; ?>
            <span class="page-info">Page <?= (int) $page ?> / <?= (int) $pagesTotal ?></span>
            <?php if ($page < $pagesTotal) : ?>
                <a class="page-nav" href="<?= e(url('avis')) ?>?<?= e(reviews_pagination_query($qBase, $page + 1)) ?>">Suivant</a>
            <?php else : ?>
                <span class="page-nav page-nav--disabled">Suivant</span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
