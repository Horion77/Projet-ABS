<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controleur;
use App\Core\Session;
use App\Models\UtilisateurModel;

/**
 * Inscription : formulaire + traitement.
 */
class InscriptionController extends Controleur
{
    public function afficher(): void
    {
        $this->rendre('inscription/index', [
            'old'       => Session::recupererAncien('old_inscription'),
            'pageTitre' => 'Inscription',
        ], 'auth');
    }

    public function traiterInscription(): void
    {
        $erreurs = [];
        $nom    = $this->requete->postString('nom');
        $prenom = $this->requete->postString('prenom');
        $email  = $this->requete->postString('email');
        $mdp1   = (string) $this->requete->post('password', '');
        $mdp2   = (string) $this->requete->post('password_confirm', '');

        if ($nom === '' || mb_strlen($nom) < 2 || mb_strlen($nom) > 80) {
            $erreurs[] = 'Le nom doit faire entre 2 et 80 caractères.';
        }
        if ($prenom === '' || mb_strlen($prenom) < 2 || mb_strlen($prenom) > 80) {
            $erreurs[] = 'Le prénom doit faire entre 2 et 80 caractères.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = 'L’adresse e-mail n’est pas valide.';
        } elseif (mb_strlen($email) > 180) {
            $erreurs[] = 'E-mail trop long.';
        }
        if (strlen($mdp1) < 6) {
            $erreurs[] = 'Le mot de passe doit comporter au moins 6 caractères.';
        }
        if ($mdp1 !== $mdp2) {
            $erreurs[] = 'Les mots de passe ne correspondent pas.';
        }

        if (empty($erreurs) && UtilisateurModel::emailExiste($email)) {
            $erreurs[] = 'Cette adresse e-mail est déjà utilisée.';
        }

        if (!empty($erreurs)) {
            Session::flashErreurs($erreurs);
            Session::flashAncien('old_inscription', [
                'nom'    => $nom,
                'prenom' => $prenom,
                'email'  => $email,
            ]);
            $this->rediriger('/inscription');
        }

        $hash = password_hash($mdp1, PASSWORD_DEFAULT);
        $id   = UtilisateurModel::creer($nom, $prenom, $email, $hash);

        Session::connecter($id, $prenom, $nom);
        Session::flashSucces('Bienvenue sur ABS ! Votre compte est prêt.');

        $this->rediriger('/');
    }
}
