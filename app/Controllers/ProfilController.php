<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controleur;
use App\Core\Session;
use App\Models\AvisModel;
use App\Models\ProfilModel;
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

    // ─── Modifier informations personnelles ───────────────────────────────

    public function modifierInfos(): void
    {
        $this->exigerConnexion('/connexion');

        $id     = (int) ($_SESSION['user_id'] ?? 0);
        $prenom = trim((string) ($_POST['prenom'] ?? ''));
        $nom    = trim((string) ($_POST['nom']    ?? ''));
        $email  = trim((string) ($_POST['email']  ?? ''));

        $erreurs = [];

        if (mb_strlen($prenom) < 2 || mb_strlen($prenom) > 80) {
            $erreurs[] = 'Le prénom doit contenir entre 2 et 80 caractères.';
        }
        if (mb_strlen($nom) < 2 || mb_strlen($nom) > 80) {
            $erreurs[] = 'Le nom doit contenir entre 2 et 80 caractères.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = 'L\'adresse e-mail n\'est pas valide.';
        } elseif (ProfilModel::emailExistePourAutre($email, $id)) {
            $erreurs[] = 'Cette adresse e-mail est déjà utilisée par un autre compte.';
        }

        if (!empty($erreurs)) {
            Session::flashErreurs($erreurs);
            $this->rediriger('/profil');
        }

        if (ProfilModel::modifierInfos($id, $prenom, $nom, $email)) {
            // La session PHP stocke le prénom/nom pour l'afficher dans le header
            // sans requête SQL à chaque page. On les synchronise ici après modification
            // pour éviter que le header affiche l'ancien nom jusqu'à la prochaine connexion.
            $_SESSION['user_prenom'] = $prenom;
            $_SESSION['user_nom']    = $nom;
            Session::flashSucces('Vos informations ont été mises à jour avec succès.');
        } else {
            Session::flashErreurs(['Une erreur est survenue lors de la mise à jour. Réessayez.']);
        }

        $this->rediriger('/profil');
    }

    // ─── Modifier le mot de passe ─────────────────────────────────────────

    public function modifierPassword(): void
    {
        $this->exigerConnexion('/connexion');

        $id              = (int) ($_SESSION['user_id'] ?? 0);
        $password        = (string) ($_POST['password']         ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

        $erreurs = [];

        // ⚠️  Le message dit "6 caractères" mais la règle vérifie < 12.
        // À corriger pour rester cohérent avec InscriptionController (12 min).
        if (mb_strlen($password) < 12) {
            $erreurs[] = 'Le mot de passe doit contenir au moins 6 caractères.';
        }
        if (!preg_match('/[A-Z]/', $password)) { 
            $erreurs[] = 'Majuscule requise.'; 
            }
        if (!preg_match('/[0-9]/', $password)) { 
            $erreurs[] = 'Chiffre requis.';
            }
            
        if ($password !== $passwordConfirm) {
            $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
        }

        if (!empty($erreurs)) {
            Session::flashErreurs($erreurs);
            $this->rediriger('/profil');
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID);

        if (ProfilModel::modifierPassword($id, $hash)) {
            Session::flashSucces('Votre mot de passe a été changé avec succès.');
        } else {
            Session::flashErreurs(['Une erreur est survenue lors du changement de mot de passe.']);
        }

        $this->rediriger('/profil');
    }

    // ─── Modifier l'avatar ────────────────────────────────────────────────

    public function modifierAvatar(): void
    {
        $this->exigerConnexion('/connexion');

        $id = (int) ($_SESSION['user_id'] ?? 0);

        if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            Session::flashErreurs(['Aucun fichier reçu ou erreur lors de l\'envoi.']);
            $this->rediriger('/profil');
        }

        $fichier   = $_FILES['avatar'];
        $taille    = (int) $fichier['size'];
        $tmpName   = (string) $fichier['tmp_name'];
        $mimeType  = mime_content_type($tmpName) ?: '';

        $mimesAutorisés = ['image/jpeg', 'image/png'];
        if (!in_array($mimeType, $mimesAutorisés, true)) {
            Session::flashErreurs(['Seuls les formats JPG et PNG sont acceptés.']);
            $this->rediriger('/profil');
        }

        $tailleMax = 2 * 1024 * 1024; // 2 Mo
        if ($taille > $tailleMax) {
            Session::flashErreurs(['L\'image ne doit pas dépasser 2 Mo.']);
            $this->rediriger('/profil');
        }

        $extension  = $mimeType === 'image/png' ? 'png' : 'jpg';
        $nomFichier = uniqid('avatar_', true) . '.' . $extension;
        $dossier    = __DIR__ . '/../../public/assets/uploads/avatars/';
        $destination = $dossier . $nomFichier;

        if (!move_uploaded_file($tmpName, $destination)) {
            Session::flashErreurs(['Impossible de sauvegarder l\'image. Réessayez.']);
            $this->rediriger('/profil');
        }

        // Chemin relatif (sans /Projet-ABS/public) contrairement aux photos de lieux
        // qui stockent un chemin absolu. Les avatars sont servis via public/assets/
        // et le CSS les référence avec src="/assets/...".
        $cheminPublic = '/assets/uploads/avatars/' . $nomFichier;

        if (ProfilModel::modifierAvatar($id, $cheminPublic)) {
            Session::flashSucces('Votre photo de profil a été mise à jour.');
        } else {
            Session::flashErreurs(['Erreur lors de la mise à jour en base de données.']);
        }

        $this->rediriger('/profil');
    }
}
