<?php
/**
 * Layout : entête + $contenu (rempli par Core/Vue) + pied. Ne contient pas la logique métier.
 * Variables attendues : $contenu, $pageTitre, $fichierCssPage (optionnel).
 */
require __DIR__ . '/../partials/entete.php';
echo $contenu;
require __DIR__ . '/../partials/pied.php';
