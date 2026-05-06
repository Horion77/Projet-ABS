<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

// Pagination (on veut max 10 avis par page)
$par_page = 10;
$page_courante = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page_courante < 1) $page_courante = 1;
$offset = ($page_courante - 1) * $par_page;

// Compter le nombre total d'avis
$total = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
$total_pages = ceil($total / $par_page);

// Récupérer les avis de la page courante
$stmt = $pdo->prepare("SELECT r.*, u.username, p.name AS place_name, c.name AS country_name
                        FROM reviews r
                        JOIN users u ON r.user_id = u.id
                        JOIN places p ON r.place_id = p.id
                        JOIN countries c ON p.country_id = c.id
                        ORDER BY r.created_at DESC
                        LIMIT :limit OFFSET :offset");

$stmt->bindValue(':limit', $par_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$avis = $stmt->fetchAll();

//On va afficher les avis mais en HTML (c'est pour ca qu'on sort du php avec le ?>)
?>
<div class="avis-container">
    <h1>Tous les avis</h1>

    <?= displayErrors() ?>
    <?= displaySuccess() ?>

    <?php if (empty($avis)): ?>
        <p>Aucun avis pour le moment.</p>

    <?php else: ?>
        <?php foreach ($avis as $a): ?>
            <div class="avis-card">
                <div class="avis-header">
                    <span class="avis-lieu"><?= htmlspecialchars($a['place_name']) ?></span>
                    <span class="avis-pays"><?= htmlspecialchars($a['country_name']) ?></span>
                </div>
                <div class="avis-note">Note : <?= $a['rating'] ?>/5</div>
                <div class="avis-auteur">Par <?= htmlspecialchars($a['username']) ?></div>
                <div class="avis-commentaire"><?= htmlspecialchars($a['comment']) ?></div>
                <div class="avis-date"><?= $a['created_at'] ?></div>
                <a href="place.php?id=<?= $a['place_id'] ?>">Voir le lieu</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

//On fait la pagination avec les boutons précédant et suivant 
<?php if ($total_pages > 1): ?>
    <div class="pagination">

        <?php if ($page_courante > 1): ?>
            <a href="avis.php?page=<?= $page_courante - 1 ?>">← Précédent</a>
        <?php endif; ?>

        <span>Page <?= $page_courante ?> sur <?= $total_pages ?></span>

        <?php if ($page_courante < $total_pages): ?>
            <a href="avis.php?page=<?= $page_courante + 1 ?>">Suivant →</a>
        <?php endif; ?>

    </div>
<?php endif; ?>

//on ferme la page
<?php require_once '../includes/footer.php'; ?>