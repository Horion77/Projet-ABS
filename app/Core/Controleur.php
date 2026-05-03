<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Base abstraite des contrôleurs. Sucre syntaxique pour le rendu et les redirections.
 */
abstract class Controleur
{
    protected Requete $requete;

    public function __construct()
    {
        $this->requete = new Requete();
    }

    /**
     * @param array<string,mixed> $donnees
     */
    protected function rendre(string $vue, array $donnees = [], ?string $css = null): void
    {
        Vue::afficher($vue, $donnees, $css);
    }

    protected function rediriger(string $url, int $code = 302): never
    {
        Reponse::rediriger($url, $code);
    }

    protected function exigerConnexion(string $urlSiInvite = '/connexion'): void
    {
        if (!Session::estConnecte()) {
            Session::flashErreurs(['Vous devez être connecté pour accéder à cette page.']);
            $this->rediriger($urlSiInvite);
        }
    }
}
