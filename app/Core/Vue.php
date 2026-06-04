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

        // EXTR_SKIP : si une clé de $donnees a le même nom qu'une variable déjà
        // définie dans cette portée (ex. $contenu, $cheminVue), on la conserve.
        // Sans ce flag, extract() écraserait silencieusement nos variables locales.
        extract($donnees, EXTR_SKIP);

        // Pattern Output Buffering : ob_start() intercepte tout echo/print de la vue,
        // ob_get_clean() récupère ce texte dans $contenu et arrête le buffer.
        // Le layout affiche $contenu à l'endroit approprié via echo $contenu.
        // Cela permet d'envelopper n'importe quelle vue dans le même layout HTML
        // sans que la vue ait besoin de connaître le layout.
        ob_start();
        require $cheminVue;
        $contenu = ob_get_clean();

        $layout = APP_ROOT . '/app/Views/layouts/principal.php';
        if (!is_file($layout)) {
            // Fallback : si le layout est absent (ex. tests), on affiche la vue brute.
            echo $contenu;
            return;
        }
        require $layout;
    }
}
