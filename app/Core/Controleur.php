<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Classe de base abstraite pour tous les contrôleurs de l'application.
 * abstract : ne peut pas être instanciée directement — seules les sous-classes
 * (AccueilController, LieuController…) sont instanciées par le Routeur.
 *
 * Nom de méthode rendre() (et non afficher()) pour éviter le conflit avec
 * les actions publiques nommées afficher() dans les sous-classes.
 */
abstract class Controleur
{
    // Chaque contrôleur a accès à la requête courante dès sa construction.
    // protected : accessible dans la classe et ses enfants, pas à l'extérieur.
    protected Requete $requete;

    public function __construct()
    {
        $this->requete = new Requete();
    }

    /**
     * Délègue le rendu à Vue::afficher(). Le paramètre $css est le nom du fichier
     * CSS additionnel (ex. 'home' → public/assets/css/home.css) chargé en plus
     * du style global.
     *
     * @param array<string,mixed> $donnees Variables exposées à la vue via extract().
     */
    protected function rendre(string $vue, array $donnees = [], ?string $css = null): void
    {
        Vue::afficher($vue, $donnees, $css);
    }

    protected function rediriger(string $url, int $code = 302): never
    {
        Reponse::rediriger($url, $code);
    }

    /**
     * Guard d'authentification : redirige vers la page de connexion si l'utilisateur
     * n'est pas connecté. rediriger() appelle exit via Reponse::rediriger() (type never),
     * donc le code du contrôleur après exigerConnexion() ne s'exécute que si connecté.
     */
    protected function exigerConnexion(string $urlSiInvite = '/connexion'): void
    {
        if (!Session::estConnecte()) {
            Session::flashErreurs(['Vous devez être connecté pour accéder à cette page.']);
            $this->rediriger($urlSiInvite);
        }
    }
}
