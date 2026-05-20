<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controleur;
use App\Core\Session;
use App\Models\AvisModel;
use App\Models\CategorieLieuModel;
use App\Models\LieuModel;
use App\Models\PaysModel;
use Throwable;

/**
 * Fiche d'un lieu : description, image, note moyenne, formulaire d'avis, avis publics.
 * Logique extraite de l'ancien pages/place.php.
 *
 * Méthode creer() : endpoint AJAX pour permettre aux utilisateurs connectés d'ajouter
 * leurs propres pins sur la carte (restaurants, monuments, etc.).
 */
class LieuController extends Controleur
{
    public function afficher(): void
    {
        $id = $this->requete->getInt('id');

        $lieu       = null;
        $avis       = [];
        $noteMoy    = null;
        $erreur     = null;
        $dejaAvis   = false;

        if ($id < 1) {
            $erreur = 'Identifiant de lieu invalide.';
        } else {
            $lieu = LieuModel::trouverParIdAvecLocalisation($id);
            if (!$lieu) {
                $erreur = 'Ce lieu n’existe pas ou n’est plus disponible.';
            } else {
                $avis = AvisModel::publicsParLieu($id);
                // Moyenne = null si aucun avis public (évite d'afficher « 0/5 » trompeur).
                $stats   = AvisModel::statsParLieu($id);
                $noteMoy = $stats['n'] > 0 ? $stats['moy'] : null;

                if (Session::estConnecte()) {
                    $dejaAvis = AvisModel::utilisateurADejaAvisSurLieu(
                        (int) ($_SESSION['user_id'] ?? 0),
                        $id
                    );
                }
            }
        }

        $this->rendre('lieu/afficher', [
            'lieu'      => $lieu,
            'avis'      => $avis,
            'noteMoy'   => $noteMoy,
            'erreur'    => $erreur,
            'dejaAvis'  => $dejaAvis,
            'pageTitre' => $lieu['nom'] ?? 'Lieu',
        ], 'place');
    }

    /**
     * POST /lieu/creer — Endpoint AJAX (JSON in / JSON out) appelé par la carte
     * quand un utilisateur connecté pose un pin via le mode « Ajouter un lieu ».
     *
     * Le formulaire envoie un FormData (potentiellement multipart si photo) avec :
     * nom, type, id_categorie, description, latitude, longitude, adresse,
     * ville_nom, pays_nom, pays_code_iso, [photo].
     */
    public function creer(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->requete->estPost()) {
            $this->repondreJson(['success' => false, 'erreur' => 'Méthode non autorisée.'], 405);
        }

        if (!Session::estConnecte()) {
            $this->repondreJson(['success' => false, 'erreur' => 'Connexion requise.'], 401);
        }

        // Lecture + validation des champs texte
        $nom         = $this->requete->postString('nom');
        $type        = $this->requete->postString('type', 'monument');
        $idCategorie = $this->requete->postInt('id_categorie');
        $description = $this->requete->postString('description');
        $latitude    = (float) $this->requete->post('latitude', 0);
        $longitude   = (float) $this->requete->post('longitude', 0);
        $adresse     = $this->requete->postString('adresse');
        $villeNom    = $this->requete->postString('ville_nom');
        $paysNom     = $this->requete->postString('pays_nom');
        $paysCodeIso = $this->requete->postString('pays_code_iso');

        if ($nom === '' || mb_strlen($nom) > 150) {
            $this->repondreJson(['success' => false, 'erreur' => 'Nom invalide (1 à 150 caractères).'], 400);
        }
        if (!in_array($type, ['pays', 'ville', 'monument'], true)) {
            $type = 'monument';
        }
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            $this->repondreJson(['success' => false, 'erreur' => 'Coordonnées GPS invalides.'], 400);
        }
        if ($idCategorie < 1 || !CategorieLieuModel::existe($idCategorie)) {
            $this->repondreJson(['success' => false, 'erreur' => 'Catégorie inconnue.'], 400);
        }
        if ($villeNom === '' || $paysNom === '') {
            $this->repondreJson(['success' => false, 'erreur' => 'Ville et pays requis (reverse-geocoding).'], 400);
        }
        if (mb_strlen($description) > 2000) {
            $this->repondreJson(['success' => false, 'erreur' => 'Description trop longue (2000 caractères max).'], 400);
        }

        // Upload photo (optionnelle)
        $imageUrl = null;
        if (isset($_FILES['photo']) && is_array($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $imageUrl = $this->traiterUploadPhoto($_FILES['photo']);
            } catch (Throwable $e) {
                $this->repondreJson(['success' => false, 'erreur' => $e->getMessage()], 400);
            }
        }

        // Résolution pays + ville (auto-création si absents)
        try {
            $idPays  = PaysModel::trouverOuCreerParCodeIso(
                $paysCodeIso !== '' ? $paysCodeIso : null,
                $paysNom
            );
            $idVille = LieuModel::trouverOuCreerVille($villeNom, $idPays);
        } catch (Throwable $e) {
            $this->repondreJson(['success' => false, 'erreur' => 'Localisation impossible : ' . $e->getMessage()], 400);
        }

        // Insertion du lieu
        try {
            $idLieu = LieuModel::creer([
                'nom'          => mb_substr($nom, 0, 150),
                'type'         => $type,
                'icon'         => $this->iconeParDefaut($type),
                'description'  => $description !== '' ? mb_substr($description, 0, 2000) : null,
                'latitude'     => $latitude,
                'longitude'    => $longitude,
                'adresse'      => $adresse !== '' ? mb_substr($adresse, 0, 255) : null,
                'image_url'    => $imageUrl,
                'id_categorie' => $idCategorie,
                'id_ville'     => $idVille,
            ]);
        } catch (Throwable $e) {
            $this->repondreJson(['success' => false, 'erreur' => 'Enregistrement impossible.'], 500);
        }

        // Réponse : on renvoie le lieu complet pour que le JS l'ajoute à la carte sans reload.
        $this->repondreJson([
            'success' => true,
            'lieu'    => [
                'id_lieu'      => $idLieu,
                'name'         => $nom,
                'lat'          => $latitude,
                'lng'          => $longitude,
                'image_url'    => $imageUrl,
                'type'         => $type,
                'icon'         => $this->iconeParDefaut($type),
                'categorie'    => null,
                'country_name' => $paysNom,
                'id_pays'      => $idPays,
                'avg_rating'   => null,
                'review_count' => 0,
            ],
        ]);
    }

    /**
     * Upload photo : vérifie le MIME réel (pas l'extension), la taille, et stocke
     * dans public/uploads/lieux/ avec un nom unique. Retourne l'URL relative
     * `/Projet-ABS/public/uploads/lieux/xxx.jpg` à enregistrer en BDD.
     */
    private function traiterUploadPhoto(array $file): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Erreur d’upload (code ' . $file['error'] . ').');
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new \RuntimeException('Photo trop lourde (5 Mo max).');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        $ext   = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => null,
        };
        if ($ext === null) {
            throw new \RuntimeException('Format photo non supporté (JPEG/PNG/WebP uniquement).');
        }

        $dossier = dirname(__DIR__, 2) . '/public/uploads/lieux';
        if (!is_dir($dossier) && !mkdir($dossier, 0775, true) && !is_dir($dossier)) {
            throw new \RuntimeException('Dossier d’upload indisponible.');
        }

        $nomFichier = uniqid('lieu_', true) . '.' . $ext;
        $cible      = $dossier . '/' . $nomFichier;
        if (!move_uploaded_file($file['tmp_name'], $cible)) {
            throw new \RuntimeException('Impossible de stocker la photo.');
        }

        // URL publique servie par Apache : /Projet-ABS/public/uploads/lieux/...
        return '/Projet-ABS/public/uploads/lieux/' . $nomFichier;
    }

    /** Icône emoji par défaut selon le type de lieu (cohérent avec le seed BDD). */
    private function iconeParDefaut(string $type): string
    {
        return match ($type) {
            'pays'  => '🏳️',
            'ville' => '🏙️',
            default => '📍',
        };
    }

    /** @param array<string,mixed> $payload */
    private function repondreJson(array $payload, int $code = 200): never
    {
        http_response_code($code);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
