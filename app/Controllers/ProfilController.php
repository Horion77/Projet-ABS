<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controleur;
use App\Core\Session;
use App\Models\AvisModel;
use App\Models\UtilisateurModel;

/**
 * Profil utilisateur connecté + ses propres avis.
 */
class ProfilController extends Controleur
{
    public function afficher(): void
    {
        $this->exigerConnexion('/connexion');

        $id   = (int) ($_SESSION['user_id'] ?? 0);
        $util = UtilisateurModel::parId($id);
        // Compte supprimé en BDD mais session encore valide → on force la déconnexion.
        if (!$util) {
            Session::deconnecter();
            $this->rediriger('/connexion');
        }

        $this->rendre('profil/index', [
            'util'      => $util,
            'mesAvis'   => AvisModel::parUtilisateur($id),
            'pageTitre' => 'Mon profil',
        ]);
    }
}
