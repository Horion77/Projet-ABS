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
            // 'old' : valeurs du dernier envoi raté (e-mail saisi) pour repopuler le formulaire.
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
        // password_verify() compare le mot de passe en clair au hash Argon2ID stocké.
        // On évalue $user en premier (court-circuit &&) : si l'email n'existe pas,
        // password_verify n'est pas appelé, ce qui évite une attaque timing sur
        // l'existence du compte tout en restant rapide.
        if (!$user || !password_verify($mdp, (string) $user['password_hash'])) {
            // Message volontairement vague pour ne pas confirmer l'existence du compte.
            Session::flashErreurs(['E-mail ou mot de passe incorrect.']);
            Session::flashAncien('old_login', ['email' => $email]);
            $this->rediriger('/connexion');
        }

        Session::connecter(
            (int) $user['id_utilisateur'],
            (string) $user['prenom'],
            (string) $user['nom'],
            // id_role ?? 3 : si la colonne est absente du résultat, on retombe sur
            // le rôle "utilisateur" (3) plutôt que de bloquer la connexion.
            (int) ($user['id_role'] ?? 3),
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
