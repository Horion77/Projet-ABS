<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

//On récupère et valide l'id du pays dans l'URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0; //on prend l'id et on le met sous forme d'entier

if ($id <= 0) {
    redirect('../index.php');
}

//On récupère les infos du pays
$stmt = $pdo->prepare("SELECT * FROM countries WHERE id = :id");
$stmt->execute([':id' => $id]);
$pays = $stmt->fetch();

//Si le pays n'existe pas en BDD, on redirige
if (!$pays) {
    redirect('../index.php');
}
//On récupère les lieux de ce pays avec leur note moyenne
$stmt = $pdo->prepare("SELECT p.*, 
                        AVG(r.rating) AS avg_rating,
                        COUNT(r.id) AS nb_reviews
                        FROM places p
                        LEFT JOIN reviews r ON r.place_id = p.id
                        WHERE p.country_id = :country_id
                        GROUP BY p.id
                        ORDER BY avg_rating DESC");

$stmt->execute([':country_id' => $id]);
$lieux = $stmt->fetchAll();


//On va afficher les infos du pays et ses lieux
?>
<div class="pays-container">
    <h1><?= htmlspecialchars($pays['name']) ?></h1>

    <?php if (empty($lieux)): ?>
        <p>Aucun lieu enregistré pour ce pays pour le moment.</p>

    <?php else: ?>
        <div class="lieux-grid">
            <?php foreach ($lieux as $lieu): ?>
                <div class="lieu-card">
                    <?php if ($lieu['image_url']): ?>
                        <img src="<?= htmlspecialchars($lieu['image_url']) ?>" alt="<?= htmlspecialchars($lieu['name']) ?>">
                    <?php endif; ?>
                    <h2><?= htmlspecialchars($lieu['name']) ?></h2>
                    <?php if ($lieu['avg_rating']): ?>
                        <div class="note">Note moyenne : <?= round($lieu['avg_rating'], 1) ?>/5</div>
                        <div class="nb-avis"><?= $lieu['nb_reviews'] ?> avis</div>
                    <?php else: ?>
                        <div class="note">Pas encore de note</div>
                    <?php endif; ?>
                    <a href="place.php?id=<?= $lieu['id'] ?>">Voir les avis</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>