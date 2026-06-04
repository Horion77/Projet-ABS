<?php
/**
 * Page d'erreur 404 — fichier HTML autonome, sans layout principal.
 * Appelé directement par Reponse::notFound() via require, en dehors du
 * système Vue::afficher(). Les fonctions globales (e(), asset(), url())
 * restent disponibles car bootstrap.php les a déjà chargées via Aides.php.
 */
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page introuvable | ABS</title>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>

<body>
    <main class="site-main">
        <div class="conteneur" style="text-align:center; padding: 4rem 1rem;">
            <h1>404</h1>
            <p>AHHH Désolé, cette page n’existe pas ou a été déplacée.</p>
            <p><a class="btn" href="<?= e(url()) ?>">Retour à l’accueil</a></p>
        </div>
    </main>
</body>

</html>