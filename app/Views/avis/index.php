<?php
/**
 * Page avis adaptée depuis `Projet-ABS-Sara/pages/avis.php`.
 * Variables MVC : $liste, $page, $pagesTotal, $totalAvis, $filtreNote, $filtrePays, $paysListe
 */
$liste      = $liste      ?? [];
$page       = $page       ?? 1;
$pagesTotal = $pagesTotal ?? 1;
$totalAvis  = $totalAvis  ?? 0;
$filtreNote = $filtreNote ?? null;
$filtrePays = $filtrePays ?? null;
$paysListe  = $paysListe  ?? [];

// $qBase : paramètres de filtre actifs, réinjectés dans chaque lien de pagination.
// Sans ça, cliquer sur "Suivant" perdrait les filtres note/pays sélectionnés.
// reviews_pagination_query() (Aides.php) fusionne $qBase avec le numéro de page.
$qBase = [];
if ($filtreNote !== null) {
    $qBase['rating'] = (string) $filtreNote;
}
if ($filtrePays !== null) {
    $qBase['country_id'] = (string) $filtrePays;
}
?>
<div class="avis-container conteneur conteneur-avis-liste">
    <h1>Tous les avis</h1>
    <p class="intro-avis"><?= (int) $totalAvis ?> avis public<?= $totalAvis > 1 ? 's' : '' ?> sur des lieux.</p>

    <?php require __DIR__ . '/../partials/messages.php'; ?>

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

    <?php if (empty($liste)) : ?>
        <p class="message-vide">Aucun avis pour le moment.</p>
    <?php else : ?>
        <?php foreach ($liste as $a) :
            $auteur = trim((string) ($a['prenom'] ?? '') . ' ' . (string) ($a['nom'] ?? ''));
            if ($auteur === '') {
                $auteur = 'Utilisateur';
            }
            $lieuId = (int) ($a['id_lieu'] ?? 0);
            // DateTimeImmutable (et non DateTime) : l'objet ne peut pas être modifié
            // par accident après création — plus sûr dans un foreach qui itère sur plusieurs avis.
            $dt = !empty($a['created_at']) ? new \DateTimeImmutable((string) $a['created_at']) : null;
            ?>
            <div class="avis-card carte-avis-global">
                <div class="avis-header carte-avis-global-ent">
                    <span class="avis-lieu"><?= e((string) ($a['lieu_nom'] ?? 'Lieu')) ?></span>
                    <span class="avis-pays"><?= e((string) ($a['pays_nom'] ?? '')) ?></span>
                </div>
                <div class="avis-note">Note : <?= (int) $a['note'] ?>/5</div>
                <div class="avis-auteur">Par <?= e($auteur) ?></div>
                <?php if (!empty($a['titre'])) : ?>
                    <div class="avis-titre-liste"><?= e((string) $a['titre']) ?></div>
                <?php endif; ?>
                <div class="avis-commentaire"><?= e((string) ($a['description'] ?? '')) ?></div>
                <?php if ($dt) : ?>
                    <time class="avis-date" datetime="<?= e($dt->format('c')) ?>"><?= e($dt->format('d/m/Y à H:i')) ?></time>
                <?php endif; ?>
                <?php if ($lieuId > 0) : ?>
                    <a href="<?= e(url('lieu')) ?>?id=<?= $lieuId ?>">Voir le lieu</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($pagesTotal > 1) : ?>
    <div class="pagination pagination-avis">
        <?php if ($page > 1) : ?>
            <a href="<?= e(url('avis')) ?>?<?= e(reviews_pagination_query($qBase, $page - 1)) ?>">Précédent</a>
        <?php endif; ?>

        <span>Page <?= (int) $page ?> sur <?= (int) $pagesTotal ?></span>

        <?php if ($page < $pagesTotal) : ?>
            <a href="<?= e(url('avis')) ?>?<?= e(reviews_pagination_query($qBase, $page + 1)) ?>">Suivant</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
