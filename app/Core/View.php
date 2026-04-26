<?php
namespace App\Core;

/**
 * Rendu des vues (sépare la présentation du contrôleur)
 */
class View
{
    /**
     * @param string $fichierVue  chemin type "home/index" (sans .php) sous app/Views
     * @param array  $donnees     variables exposées à la vue
     * @param string|null $css   nom du fichier additionnel (auth, home…)
     */
    public static function render(string $fichierVue, array $donnees = [], ?string $css = null) : void
    {
        if (!\defined('APP_ROOT')) {
            \define('APP_ROOT', \dirname(__DIR__, 2));
        }
        require_once APP_ROOT . '/includes/functions.php';

        extract($donnees, EXTR_SKIP);

        if ($css !== null) {
            $fichierCssPage = $css;
        } elseif (!isset($fichierCssPage) && \array_key_exists('fichierCssPage', $donnees)) {
            $fichierCssPage = $donnees['fichierCssPage'] ?? null;
        }

        if (!isset($pageTitre)) {
            $pageTitre = $donnees['pageTitre'] ?? 'Accueil';
        }

        $fichierCssPage = $fichierCssPage ?? null;
        $prefixRacine   = \prefixRacine();

        $cheminVue = APP_ROOT . '/app/Views/' . $fichierVue . '.php';
        if (!\is_file($cheminVue)) {
            \http_response_code(500);
            echo 'Vue introuvable : ' . \e($fichierVue);
            return;
        }

        \ob_start();
        require $cheminVue;
        $contenu = \ob_get_clean();

        $layout = APP_ROOT . '/app/Views/layouts/main.php';
        if (!\is_file($layout)) {
            echo $contenu;
            return;
        }
        require $layout;
    }
}
