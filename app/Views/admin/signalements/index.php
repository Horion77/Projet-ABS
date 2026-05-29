<?php
/**
 * Page admin : file d'attente des signalements + actions de modération.
 * Variables : $liste, $statut, $nbEnAttente, $nbTraite, $nbRejete
 */
$liste       = $liste       ?? [];
$statut      = $statut      ?? 'en_attente';
$nbEnAttente = (int) ($nbEnAttente ?? 0);
$nbTraite    = (int) ($nbTraite    ?? 0);
$nbRejete    = (int) ($nbRejete    ?? 0);

/** Libellés lisibles pour les motifs stockés en base. */
$libMotif = static function (string $m): string {
    return match ($m) {
        'spam'        => 'Spam',
        'insulte'     => 'Insulte / haine',
        'inapproprie' => 'Contenu inapproprié',
        'hors_sujet'  => 'Hors sujet',
        default       => 'Autre',
    };
};
?>
<div class="admin-mod conteneur">

    <header class="adm-hero">
        <h1>Modération des signalements</h1>
        <p>Les utilisateurs peuvent signaler un avis ou un commentaire. Vous décidez ici de l'action à prendre.</p>
    </header>

    <?php require __DIR__ . '/../../partials/messages.php'; ?>

    <!-- Onglets par statut (compteur entre parenthèses) -->
    <nav class="adm-tabs" aria-label="Filtrer par statut">
        <?php foreach (
            [
                ['en_attente', 'En attente', $nbEnAttente],
                ['traite',     'Traités',    $nbTraite],
                ['rejete',     'Rejetés',    $nbRejete],
            ] as [$s, $lib, $nb]
        ) : ?>
            <a class="adm-tab <?= $statut === $s ? 'adm-tab--actif' : '' ?>"
               href="<?= e(url('admin/signalements')) ?>?statut=<?= e($s) ?>">
                <?= e($lib) ?> <span class="adm-tab-nb"><?= $nb ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if (empty($liste)) : ?>
        <div class="adm-vide">
            <p>Aucun signalement <?= $statut === 'en_attente' ? 'en attente' : ($statut === 'traite' ? 'traité' : 'rejeté') ?>.</p>
        </div>
    <?php else : ?>
        <ul class="adm-liste">
        <?php foreach ($liste as $s) :
            $idSig    = (int) $s['id_signalement'];
            $type     = (string) $s['cible_type'];
            $idCible  = (int) $s['cible_id'];
            $sigUser  = trim(($s['sig_prenom'] ?? '') . ' ' . ($s['sig_nom'] ?? '')) ?: 'Utilisateur';
            $cibleUser= trim(($s['cible_prenom'] ?? '') . ' ' . ($s['cible_nom'] ?? ''));
            $contenu  = (string) ($s['contenu'] ?? '');
            $supprime = $contenu === ''; // la cible n'existe plus (supprimée entretemps)
            $dt       = !empty($s['created_at']) ? new \DateTimeImmutable((string) $s['created_at']) : null;
            $extrait  = $contenu !== '' ? (mb_strlen($contenu) > 220 ? mb_substr($contenu, 0, 220) . '…' : $contenu) : '—';
        ?>
            <li class="adm-card adm-card--<?= e($type) ?>">

                <div class="adm-card-head">
                    <span class="adm-badge adm-badge--<?= e($type) ?>"><?= $type === 'avis' ? 'Avis' : 'Commentaire' ?></span>
                    <span class="adm-motif"><?= e($libMotif((string) $s['motif'])) ?></span>
                    <?php if ($dt) : ?>
                        <time class="adm-date" datetime="<?= e($dt->format('c')) ?>"><?= e($dt->format('d/m/Y à H:i')) ?></time>
                    <?php endif; ?>
                </div>

                <p class="adm-meta">
                    Signalé par <strong><?= e($sigUser) ?></strong>
                    <?php if ($cibleUser !== '') : ?>
                        · auteur du contenu : <strong><?= e($cibleUser) ?></strong>
                    <?php endif; ?>
                </p>

                <?php if (!empty($s['details'])) : ?>
                    <p class="adm-details">« <?= e((string) $s['details']) ?> »</p>
                <?php endif; ?>

                <blockquote class="adm-extrait <?= $supprime ? 'adm-extrait--vide' : '' ?>">
                    <?= e($extrait) ?>
                </blockquote>

                <?php if ($type === 'avis' && !empty($s['avis_id_lieu'])) : ?>
                    <p class="adm-lien">
                        <a href="<?= e(url('lieu')) ?>?id=<?= (int) $s['avis_id_lieu'] ?>#avis-<?= $idCible ?>" target="_blank" rel="noopener">Voir l'avis dans son contexte ↗</a>
                    </p>
                <?php endif; ?>

                <?php if ($statut === 'en_attente') : ?>
                    <form class="adm-actions" method="post" action="<?= e(url('admin/signalements/traiter')) ?>">
                        <input type="hidden" name="id" value="<?= $idSig ?>">
                        <button type="submit" name="action" value="marquer_traite" class="btn btn-sm">Marquer traité</button>
                        <button type="submit" name="action" value="rejeter" class="btn btn-sm btn-ghost">Rejeter</button>
                        <?php if (!$supprime) : ?>
                            <button type="submit" name="action" value="supprimer_contenu"
                                    class="btn btn-sm btn-danger"
                                    onclick="return confirm('Supprimer définitivement ce <?= $type === 'avis' ? 'avis' : 'commentaire' ?> ?');">
                                Supprimer le contenu
                            </button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>

            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>

</div>
