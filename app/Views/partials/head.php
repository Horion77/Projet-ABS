<?php
/** Partie haute HTML + barre de navigation. Variables : $pageTitre, $fichierCssPage, $prefixRacine */
if (!isset($fichierCssPage)) {
    $fichierCssPage = null;
}
if (!isset($pageTitre)) {
    $pageTitre = 'Accueil';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitre) ?> | ABS</title>
    <link rel="stylesheet" href="<?= e($prefixRacine) ?>assets/css/style.css">
    <?php if (!empty($fichierCssPage) && is_string($fichierCssPage)) : ?>
    <link rel="stylesheet" href="<?= e($prefixRacine) ?>assets/css/<?= e($fichierCssPage) ?>.css">
    <?php endif; ?>
</head>
<body>
    <a class="skip" href="#contenu-principal">Aller au contenu</a>
    <header class="site-header">
        <div class="header-inner">
            <a class="logo" href="<?= e($prefixRacine) ?>index.php">ABS</a>
            <button type="button" class="nav-toggle" aria-expanded="false" aria-controls="nav-site">Menu</button>
            <nav id="nav-site" class="nav-site" aria-label="Principale">
                <a href="<?= e($prefixRacine) ?>index.php">Accueil</a>
                <a href="<?= e($prefixRacine) ?>pages/map.php">Carte</a>
                <a href="<?= e($prefixRacine) ?>pages/avis.php">Avis</a>
                <?php if (isLoggedIn()) : ?>
                    <span class="nav-sep" aria-hidden="true"></span>
                    <?php
                    $nomNav = trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
                    if ($nomNav === '') {
                        $nomNav = 'Mon compte';
                    }
                    ?>
                    <a href="<?= e($prefixRacine) ?>pages/profil.php"><?= e($nomNav) ?></a>
                    <a class="nav-auth" href="<?= e($prefixRacine) ?>actions/logout.php">Déconnexion</a>
                <?php else : ?>
                    <span class="nav-sep" aria-hidden="true"></span>
                    <a class="nav-auth" href="<?= e($prefixRacine) ?>pages/login.php">Connexion</a>
                    <a class="nav-auth" href="<?= e($prefixRacine) ?>pages/inscription.php">Inscription</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main id="contenu-principal" class="site-main">
