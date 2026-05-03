<?php
/**
 * Layout principal : entête + contenu capturé + pied.
 * Variables : $contenu, $pageTitre, $fichierCssPage
 */
require __DIR__ . '/../partials/entete.php';
echo $contenu;
require __DIR__ . '/../partials/pied.php';
