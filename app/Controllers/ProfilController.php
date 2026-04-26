<?php
namespace App\Controllers;

use App\Core\View;
use App\Models\AvisModel;
use App\Models\UtilisateurModel;

class ProfilController
{
    public function montrer() : void
    {
        if (!isLoggedIn()) {
            redirect('login.php');
        }

        $id = (int) $_SESSION['user_id'];
        $util  = UtilisateurModel::parId($id);
        if (!$util) {
            session_destroy();
            redirect('login.php');
        }
        $mesAvis = AvisModel::parUtilisateur($id);

        View::render('profil/index', [
            'util'     => $util,
            'mesAvis'  => $mesAvis,
            'pageTitre' => 'Mon profil',
        ]);
    }
}
