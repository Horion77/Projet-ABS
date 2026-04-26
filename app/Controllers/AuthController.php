<?php
namespace App\Controllers;

use App\Core\View;
use App\Models\UtilisateurModel;

class AuthController
{
    public function formLogin() : void
    {
        $old = $_SESSION['old_login'] ?? [];
        if (isset($_SESSION['old_login'])) {
            unset($_SESSION['old_login']);
        }
        View::render('auth/login', [
            'old'       => $old,
            'pageTitre' => 'Connexion',
        ], 'auth');
    }

    public function traiterLogin() : void
    {
        $email = trim($_POST['email'] ?? '');
        $mdp   = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['errors']  = ['E-mail non valide.'];
            $_SESSION['old_login'] = ['email' => $email];
            redirect('../pages/login.php');
        }

        $user = UtilisateurModel::parEmail($email);
        if (!$user || !password_verify($mdp, $user['password_hash'])) {
            $_SESSION['errors']  = ['E-mail ou mot de passe incorrect.'];
            $_SESSION['old_login'] = ['email' => $email];
            redirect('../pages/login.php');
        }

        $_SESSION['user_id']     = (int) $user['id_utilisateur'];
        $_SESSION['user_prenom']  = $user['prenom'];
        $_SESSION['user_nom']   = $user['nom'];
        $_SESSION['success']    = 'Connexion réussie, bonne navigation.';

        redirect('../index.php');
    }

    public function logout() : void
    {
        session_destroy();
        header('Location: ../index.php');
        exit;
    }
}
