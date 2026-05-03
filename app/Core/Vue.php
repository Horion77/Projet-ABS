<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Rendu des vues : extrait les variables, capture le contenu, l'enveloppe dans le layout principal.
 *
 * Variables toujours disponibles dans la vue : $pageTitre, $fichierCssPage (optionnel),
 * et toutes les clés du tableau $donnees.
 */
class Vue
{
    /**
     * @param string $fichierVue Chemin sans .php sous app/Views (ex. "lieu/afficher").
     * @param array<string,mixed> $donnees Variables exposées à la vue.
     * @param string|null $css Nom du fichier CSS additionnel (sans .css), placé dans public/assets/css/.
     */
    public static function afficher(string $fichierVue, array $donnees = [], ?string $css = null): void
    {
        $cheminVue = APP_ROOT . '/app/Views/' . $fichierVue . '.php';
        if (!is_file($cheminVue)) {
            http_response_code(500);
            echo 'Vue introuvable : ' . htmlspecialchars($fichierVue, ENT_QUOTES, 'UTF-8');
            return;
        }

        if (!isset($donnees['pageTitre'])) {
            $donnees['pageTitre'] = 'Accueil';
        }
        $fichierCssPage = $css ?? ($donnees['fichierCssPage'] ?? null);
        $donnees['fichierCssPage'] = $fichierCssPage;

        extract($donnees, EXTR_SKIP);

        ob_start();
        require $cheminVue;
        $contenu = ob_get_clean();

        $layout = APP_ROOT . '/app/Views/layouts/principal.php';
        if (!is_file($layout)) {
            echo $contenu;
            return;
        }
        require $layout;
    }
}
