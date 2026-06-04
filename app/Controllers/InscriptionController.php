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
            'bodyClass' => 'page-stars page-auth',
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
        $erreurs[] = 'L\'adresse e-mail n\'est pas valide.';
    } elseif (mb_strlen($email) > 180) {
        $erreurs[] = 'E-mail trop long.';
    }

    // Complexité mot de passe
    if (mb_strlen($mdp1) < 12) {
        $erreurs[] = 'Le mot de passe doit contenir au moins 12 caractères.';
    }
    if (!preg_match('/[A-Z]/', $mdp1)) {
        $erreurs[] = 'Le mot de passe doit contenir au moins une majuscule.';
    }
    if (!preg_match('/[0-9]/', $mdp1)) {
        $erreurs[] = 'Le mot de passe doit contenir au moins un chiffre.';
    }
    if ($mdp1 !== $mdp2) {
        $erreurs[] = 'Les mots de passe ne correspondent pas.';
    }

    // La vérification d'unicité de l'email est faite en dernier et seulement si
    // la validation basique a réussi : évite une requête SQL inutile si le format
    // est déjà invalide, et empêche de révéler l'existence d'un compte via le
    // message d'erreur si les autres champs sont eux-mêmes invalides.
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

    // Argon2ID : algorithme de hachage recommandé par l'OWASP pour les mots de passe.
    // memory_cost = 65536 Ko (64 Mo) : coût mémoire élevé pour ralentir les attaques GPU.
    // time_cost   = 4 itérations : augmente le temps CPU sans toucher à la mémoire.
    // threads     = 1 : PHP ne supporte pas le parallélisme dans ce contexte.
    $hash = password_hash($mdp1, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost'   => 4,
        'threads'     => 1,
    ]);
    $id = UtilisateurModel::creer($nom, $prenom, $email, $hash);

    Session::connecter($id, $prenom, $nom);
    Session::flashSucces('Bienvenue sur ABS ! Votre compte est prêt.');

    $this->rediriger('/');
}
}
