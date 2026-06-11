<?php
/**
 * Page « Découvrir » : formulaire de critères + liste de lieux recommandés.
 * Variables : $resultats, $aRecherche, $categorie, $continent, $noteMin,
 *             $idPays, $idVille, $categories, $continents, $paysListe, $villesListe
 */
$resultats   = $resultats   ?? [];
$aRecherche  = $aRecherche  ?? false;
$categorie   = $categorie   ?? '';
$continent   = $continent   ?? '';
$noteMin     = $noteMin     ?? 0;
$idPays      = $idPays      ?? 0;
$idVille     = $idVille     ?? 0;
$categories  = $categories  ?? [];
$continents  = $continents  ?? [];
$paysListe   = $paysListe   ?? [];
$villesListe = $villesListe ?? [];

// Closure locale (pas dans Aides.php) : le rendu demi-étoile ⯨ est spécifique
// à cette page. Les autres vues utilisent starsRatingHtml() (entiers seulement).
$etoiles = static function (?float $note): string {
    if ($note === null) {
        return '';
    }
    $pleines = (int) floor($note);
    $demi    = ($note - $pleines) >= 0.5 ? 1 : 0;
    $vides   = 5 - $pleines - $demi;
    return str_repeat('★', $pleines) . ($demi ? '⯨' : '') . str_repeat('☆', max(0, $vides));
};
?>
<div class="decouvrir conteneur">

    <header class="dc-hero">
        <h1>Où veux-tu aller&nbsp;?</h1>
        <p>Dis-nous ce que tu cherches, on te sort les meilleurs spots.</p>
    </header>

    <?php
    // method="get" : les critères de recherche sont dans l'URL (?cat=Musée&note=4…).
    // Avantage : l'URL est partageable/bookmarkable. Pas de données sensibles ici,
    // donc GET est approprié (contrairement aux formulaires de connexion/inscription).
    ?>
    <form class="dc-form" method="get" action="<?= e(url('decouvrir')) ?>">
        <div class="dc-field">
            <label for="dc-cat">Type de lieu</label>
            <select id="dc-cat" name="cat">
                <option value="">Tous les types</option>
                <?php foreach ($categories as $c) : $lib = (string) $c['libelle']; ?>
                    <option value="<?= e($lib) ?>"<?= $categorie === $lib ? ' selected' : '' ?>><?= e($lib) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="dc-field">
            <label for="dc-note">Note minimale</label>
            <select id="dc-note" name="note">
                <option value="0">Peu importe</option>
                <?php for ($n = 5; $n >= 1; $n--) : ?>
                    <option value="<?= $n ?>"<?= (int) $noteMin === $n ? ' selected' : '' ?>><?= $n ?>★ et plus</option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="dc-field">
            <label for="dc-continent">Continent</label>
            <select id="dc-continent" name="continent">
                <option value="">Tous</option>
                <?php foreach ($continents as $cont) : ?>
                    <option value="<?= e($cont) ?>"<?= $continent === $cont ? ' selected' : '' ?>><?= e($cont) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="dc-field">
            <label for="dc-pays">Pays</label>
            <select id="dc-pays" name="pays">
                <option value="0">Tous</option>
                <?php foreach ($paysListe as $p) : ?>
                    <option value="<?= (int) $p['id_pays'] ?>"<?= (int) $idPays === (int) $p['id_pays'] ? ' selected' : '' ?>><?= e((string) $p['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="dc-field">
            <label for="dc-ville">Ville</label>
            <select id="dc-ville" name="ville">
                <option value="0">Toutes</option>
                <?php foreach ($villesListe as $v) : ?>
                    <option value="<?= (int) $v['id_ville'] ?>"<?= (int) $idVille === (int) $v['id_ville'] ? ' selected' : '' ?>>
                        <?= e((string) $v['nom']) ?> <?= e('(' . (string) $v['pays'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="dc-actions">
            <button type="submit" class="dc-btn">Découvrir</button>
            <a class="dc-reset" href="<?= e(url('decouvrir')) ?>">Réinitialiser</a>
        </div>
    </form>

    <?php if (!$aRecherche) : ?>
        <div class="dc-empty">
            <div class="dc-empty-icon">🧭</div>
            <p>Choisis un ou plusieurs critères ci-dessus pour obtenir ta liste de lieux.</p>
        </div>
    <?php elseif (empty($resultats)) : ?>
        <div class="dc-empty">
            <div class="dc-empty-icon">🔍</div>
            <p>Aucun lieu ne correspond à ces critères. Essaie d'élargir ta recherche.</p>
        </div>
    <?php else : ?>
        <p class="dc-count"><?= count($resultats) ?> lieu<?= count($resultats) > 1 ? 'x' : '' ?> trouvé<?= count($resultats) > 1 ? 's' : '' ?></p>
        <div class="dc-grid">
            <?php foreach ($resultats as $i => $r) :
                $note    = $r['note_moy'] !== null ? (float) $r['note_moy'] : null;
                $nbAvis  = (int) ($r['nb_avis'] ?? 0);
                $lienLieu = url('lieu') . '?id=' . (int) $r['id_lieu'];
                $img = (string) ($r['image_url'] ?? '');
                ?>
                <a class="dc-card" href="<?= e($lienLieu) ?>">
                    <div class="dc-rank">#<?= $i + 1 ?></div>
                    <?php if ($img !== '') : ?>
                        <img class="dc-card-img" src="<?= e($img) ?>" alt="" loading="lazy" onerror="this.style.display='none'">
                    <?php else : ?>
                        <div class="dc-card-img dc-card-noimg"></div>
                    <?php endif; ?>
                    <div class="dc-card-body">
                        <div class="dc-card-cat"><?= e((string) ($r['categorie'] ?? '')) ?></div>
                        <h3 class="dc-card-nom"><?= e((string) $r['nom']) ?></h3>
                        <div class="dc-card-loc"><?= e((string) ($r['ville'] ?? '')) ?> · <?= e((string) ($r['pays'] ?? '')) ?></div>
                        <?php if ($note !== null) : ?>
                            <div class="dc-card-note">
                                <span class="dc-stars"><?= $etoiles($note) ?></span>
                                <span class="dc-note-num"><?= e(number_format($note, 1, ',', '')) ?></span>
                                <span class="dc-nb">(<?= $nbAvis ?> avis)</span>
                            </div>
                        <?php else : ?>
                            <div class="dc-card-note dc-no-note">Pas encore d'avis</div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
