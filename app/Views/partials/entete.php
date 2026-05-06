<?php
/**
 * En-tête HTML : doctype, métadonnées, feuilles de style, ouverture du <main>.
 * Variables : $pageTitre, $fichierCssPage (optionnel)
 */
$pageTitre      = $pageTitre      ?? 'Accueil';
$fichierCssPage = $fichierCssPage ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitre) ?> | ABS</title>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
    <?php if (!empty($fichierCssPage) && is_string($fichierCssPage)) : ?>
    <link rel="stylesheet" href="<?= e(asset('css/' . $fichierCssPage . '.css')) ?>">
    <?php endif; ?>
</head>
<body>
    <a class="skip" href="#contenu-principal">Aller au contenu</a>
    <?php require __DIR__ . '/navigation.php'; ?>
    <main id="contenu-principal" class="site-main">
