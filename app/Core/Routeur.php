<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Routeur minimaliste : chaque route = [METHODE, CHEMIN, [Controleur::class, 'methode']].
 * Ne supporte pas (volontairement) les paramètres dans le chemin — on utilise les query strings
 * pour rester proche du PHP natif enseigné dans le cours.
 */
class Routeur
{
    /** @var list<array{0:string,1:string,2:array{0:class-string,1:string}}> */
    private array $routes;

    /**
     * @param list<array{0:string,1:string,2:array{0:class-string,1:string}}> $routes
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function dispatch(): void
    {
        $req     = new Requete();
        $methode = $req->methode();
        $chemin  = $req->chemin();

        // Si le chemin existe dans la table mais pas la méthode → 405 ; sinon chemin inconnu → 404.
        $cheminAutorise = false;
        foreach ($this->routes as [$mRoute, $cRoute, $action]) {
            if ($cRoute !== $chemin) {
                continue;
            }
            $cheminAutorise = true;
            if ($mRoute !== $methode) {
                continue;
            }
            [$classe, $methodeCtrl] = $action;
            (new $classe())->{$methodeCtrl}();
            return;
        }

        if ($cheminAutorise) {
            http_response_code(405);
            header('Allow: ' . implode(', ', $this->methodesPour($chemin)));
            echo '<h1>405 — Méthode non autorisée</h1>';
            return;
        }

        Reponse::notFound();
    }

    /** @return list<string> */
    private function methodesPour(string $chemin): array
    {
        $m = [];
        foreach ($this->routes as [$mRoute, $cRoute, $_]) {
            if ($cRoute === $chemin) {
                $m[] = $mRoute;
            }
        }
        return array_values(array_unique($m));
    }
}
