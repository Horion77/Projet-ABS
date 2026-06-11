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
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('images/favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
    <?php
    // CSS additionnel chargé uniquement pour les pages qui en ont besoin :
    // home.css (accueil), map.css (carte), auth.css (connexion/inscription)…
    // Évite de charger en global des styles très spécifiques à une seule page.
    if (!empty($fichierCssPage) && is_string($fichierCssPage)) : ?>
    <link rel="stylesheet" href="<?= e(asset('css/' . $fichierCssPage . '.css')) ?>">
    <?php endif; ?>
</head>
<body>
    <?php
    // Lien "Aller au contenu" : visible uniquement au focus clavier (CSS .skip).
    // Obligatoire pour l'accessibilité (WCAG 2.4.1) — permet aux utilisateurs
    // de lecteurs d'écran / navigation clavier de sauter la navigation répétée.
    ?>
    <a class="skip" href="#contenu-principal">Aller au contenu</a>
    <?php require __DIR__ . '/navigation.php'; ?>
    <?php
    // <main> est ouvert ici mais FERMÉ dans partials/pied.php.
    // Le layout principal.php intercale $contenu entre entete.php et pied.php,
    // donc la balise reste ouverte pendant tout le rendu de la vue.
    ?>
    <main id="contenu-principal" class="site-main">
