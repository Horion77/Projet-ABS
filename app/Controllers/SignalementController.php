<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controleur;
use App\Core\Reponse;
use App\Core\Session;
use App\Models\SignalementModel;

/**
 * Signalements : création (utilisateur connecté) + file d'attente et actions
 * de modération (admin uniquement).
 */
class SignalementController extends Controleur
{
    /** Motifs acceptés : si le client envoie autre chose, on tombe sur "autre". */
    private const MOTIFS = ['spam', 'insulte', 'inapproprie', 'hors_sujet', 'autre'];

    // ── Utilisateur : signaler un avis ou un commentaire ──────────────────────
    /**
     * POST /signalement
     * Champs attendus : type ('avis'|'commentaire'), id_cible (int),
     *                   motif (string), details (optionnel)
     * Répond en JSON : { success, deja? } ou { success:false, erreur }.
     */
    public function creer(): never
    {
        $this->exigerConnexion('connexion');

        $type    = (string) $this->requete->post('type', '');
        $idCible = $this->requete->postInt('id_cible');
        $motif   = (string) $this->requete->post('motif', 'autre');
        $details = trim((string) $this->requete->post('details', ''));
        $details = $details !== '' ? $details : null;

        if (!in_array($type, SignalementModel::TYPES, true) || $idCible <= 0) {
            Reponse::json(['success' => false, 'erreur' => 'Paramètres invalides.'], 400);
        }
        if (!in_array($motif, self::MOTIFS, true)) {
            $motif = 'autre';
        }

        $user = Session::utilisateur();
        // Si l'utilisateur a déjà signalé ce contenu, on répond success:true (pas d'erreur)
        // avec deja:true pour que le JS puisse afficher un retour différent ("déjà signalé")
        // sans traiter ça comme un échec. Le contenu n'est pas re-signalé.
        if (SignalementModel::aDejaSignale((int) $user['id'], $type, $idCible)) {
            Reponse::json(['success' => true, 'deja' => true]);
        }

        $id = SignalementModel::creer((int) $user['id'], $type, $idCible, $motif, $details);
        Reponse::json(['success' => $id > 0]);
    }

    // ── Admin : file d'attente ────────────────────────────────────────────────
    /**
     * GET /admin/signalements
     * Liste les signalements groupés par statut. Filtrable via ?statut=…
     */
    public function index(): void
    {
        $this->exigerModerateur();

        $statut = (string) $this->requete->get('statut', 'en_attente');
        if (!in_array($statut, SignalementModel::STATUTS, true)) {
            $statut = 'en_attente';
        }

        $this->rendre('admin/signalements/index', [
            'liste'        => SignalementModel::listePourAdmin($statut, 200),
            'statut'       => $statut,
            'nbEnAttente'  => SignalementModel::compterParStatut('en_attente'),
            'nbTraite'     => SignalementModel::compterParStatut('traite'),
            'nbRejete'     => SignalementModel::compterParStatut('rejete'),
            'pageTitre'    => 'Modération — Signalements',
        ], 'admin');
    }

    // ── Admin : actions sur un signalement ────────────────────────────────────
    /**
     * POST /admin/signalements/traiter
     * Action : 'marquer_traite' | 'rejeter' | 'supprimer_contenu'.
     */
    public function traiter(): never
    {
        $this->exigerModerateur();

        $id     = $this->requete->postInt('id');
        $action = (string) $this->requete->post('action', '');

        $sig = SignalementModel::trouver($id);
        if ($sig === null) {
            Session::flashErreurs(['Signalement introuvable.']);
            $this->rediriger('/admin/signalements');
        }

        if ($action === 'marquer_traite') {
            SignalementModel::changerStatut($id, 'traite');
            Session::flashSucces('Signalement marqué comme traité.');
        } elseif ($action === 'rejeter') {
            SignalementModel::changerStatut($id, 'rejete');
            Session::flashSucces('Signalement rejeté.');
        } elseif ($action === 'supprimer_contenu') {
            // supprimerCible() supprime le contenu ciblé ET marque automatiquement
            // comme 'traité' tous les autres signalements portant sur ce même contenu
            // (même cible_type + cible_id). Un seul clic traite donc toute la file.
            $ok = SignalementModel::supprimerCible(
                (string) $sig['cible_type'],
                (int) $sig['cible_id']
            );
            if ($ok) {
                Session::flashSucces('Contenu supprimé et signalement(s) traité(s).');
            } else {
                Session::flashErreurs(['Suppression du contenu impossible.']);
            }
        } else {
            Session::flashErreurs(['Action inconnue.']);
        }

        $this->rediriger('/admin/signalements');
    }

    // ── Garde modération ─────────────────────────────────────────────────────
    // Méthode privée (pas dans l'interface publique du contrôleur) car elle
    // n'est appelée qu'en interne avant index() et traiter().
    // Le routeur n'a pas de middleware d'auth : c'est ici que l'accès est gardé.
    private function exigerModerateur(): void
    {
        if (!Session::estModerateur()) {
            Session::flashErreurs(['Accès réservé aux administrateurs et modérateurs.']);
            $this->rediriger('/');
        }
    }
}
