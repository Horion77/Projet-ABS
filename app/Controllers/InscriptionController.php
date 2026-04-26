<?php
namespace App\Controllers;

use App\Core\View;
use App\Models\UtilisateurModel;

class InscriptionController
{
    public function form() : void
    {
        $old = $_SESSION['old_inscription'] ?? [];
        if (isset($_SESSION['old_inscription'])) {
            unset($_SESSION['old_inscription']);
        }
        View::render('auth/inscription', [
            'old'       => $old,
            'pageTitre' => 'Inscription',
        ], 'auth');
    }

    public function traiterInscription() : void
    {
        $erreurs = [];
        $nom   = trim($_POST['nom']   ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email  = trim($_POST['email']  ?? '');
        $mdp1   = $_POST['password']         ?? '';
        $mdp2   = $_POST['password_confirm'] ?? '';

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
            $_SESSION['errors']         = $erreurs;
            $_SESSION['old_inscription'] = [
                'nom'    => $nom,
                'prenom' => $prenom,
                'email'  => $email,
            ];
            redirect('../pages/inscription.php');
        }

        $hash = password_hash($mdp1, PASSWORD_DEFAULT);
        $id  = UtilisateurModel::creer($nom, $prenom, $email, $hash);

        $_SESSION['user_id']     = $id;
        $_SESSION['user_prenom'] = $prenom;
        $_SESSION['user_nom']   = $nom;
        $_SESSION['success']     = 'Bienvenue sur ABS ! Votre compte est prêt.';

        redirect('../index.php');
    }
}
