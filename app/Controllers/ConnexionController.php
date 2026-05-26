<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controleur;
use App\Core\Session;
use App\Models\UtilisateurModel;

/**
 * Connexion / déconnexion.
 */
class ConnexionController extends Controleur
{
    public function afficher(): void
    {
        $this->rendre('connexion/index', [
            'old'       => Session::recupererAncien('old_login'),
            'pageTitre' => 'Connexion',
            'bodyClass' => 'page-stars page-auth',
        ], 'auth');
    }

    public function traiterConnexion(): void
    {
        $email = $this->requete->postString('email');
        $mdp   = (string) $this->requete->post('password', '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flashErreurs(['E-mail non valide.']);
            Session::flashAncien('old_login', ['email' => $email]);
            $this->rediriger('/connexion');
        }

        $user = UtilisateurModel::parEmail($email);
        // password_verify : le mot de passe en clair n'est jamais comparé au hash « à la main ».
        if (!$user || !password_verify($mdp, (string) $user['password_hash'])) {
            Session::flashErreurs(['E-mail ou mot de passe incorrect.']);
            Session::flashAncien('old_login', ['email' => $email]);
            $this->rediriger('/connexion');
        }

        Session::connecter(
            (int) $user['id_utilisateur'],
            (string) $user['prenom'],
            (string) $user['nom'],
        );
        Session::flashSucces('Connexion réussie, bonne navigation.');

        $this->rediriger('/');
    }

    public function deconnecter(): void
    {
        Session::deconnecter();
        $this->rediriger('/');
    }
}
