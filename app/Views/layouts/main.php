<?php
/**
 * Mise en page : en-tête + $contenu + pied (variables : $contenu, $pageTitre, $fichierCssPage, $prefixRacine)
 */
$prefixRacine = $prefixRacine ?? prefixRacine();
require __DIR__ . '/../partials/head.php';
echo $contenu;
require __DIR__ . '/../partials/foot.php';
