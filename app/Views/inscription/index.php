<?php
/**
 * Vue : formulaire d'inscription. Variables : $old
 */
$old = $old ?? [];
?>
<div class="conteneur-auth">
    <div class="card-auth">
        <div class="auth-logo-wrap" aria-hidden="true"></div>
        <h1 class="titre-auth">Créer un compte</h1>
        <p class="sous-titre-auth">Rejoignez la communauté et partagez vos découvertes.</p>
        <?php require __DIR__ . '/../partials/messages.php'; ?>
        <form id="form-inscription" class="form-auth" method="post" action="<?= e(url('inscription')) ?>" novalidate>
            <div class="groupe-champ">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" required minlength="2" maxlength="80"
                       autocomplete="given-name" placeholder="Votre prénom"
                       value="<?= e((string) ($old['prenom'] ?? '')) ?>">
            </div>
            <div class="groupe-champ">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" required minlength="2" maxlength="80"
                       autocomplete="family-name" placeholder="Votre nom"
                       value="<?= e((string) ($old['nom'] ?? '')) ?>">
            </div>
            <div class="groupe-champ">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required autocomplete="email"
                       placeholder="votre@email.com"
                       value="<?= e((string) ($old['email'] ?? '')) ?>">
                <p class="field-error" id="err-ins-email" hidden></p>
            </div>
            <div class="groupe-champ">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required minlength="6"
                       autocomplete="new-password" placeholder="6 caractères minimum">
            </div>
            <div class="groupe-champ">
                <label for="password_confirm">Confirmer le mot de passe</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="6"
                       autocomplete="new-password" placeholder="••••••••">
                <p class="field-error" id="err-ins-pass" hidden></p>
            </div>

            <!-- CGU -->
            <div class="cgu-wrap">
                <label class="cgu-label" for="cgu">
                    <input type="checkbox" id="cgu" name="cgu" required>
                    <span>J'accepte les <button type="button" class="cgu-lien" id="ouvrir-cgu">conditions générales d'utilisation</button></span>
                </label>
                <p class="field-error" id="err-cgu" hidden>Vous devez accepter les CGU pour continuer.</p>
            </div>

            <button class="btn-auth" type="submit" id="btn-inscrire" disabled>Créer mon compte</button>
        </form>
        <p class="lien-auth-bas">Déjà inscrit ? <a href="<?= e(url('connexion')) ?>">Se connecter</a></p>
    </div>
</div>

<!-- Modal CGU -->
<div class="cgu-overlay" id="cgu-overlay" role="dialog" aria-modal="true" aria-labelledby="cgu-titre" hidden>
    <div class="cgu-modal">
        <div class="cgu-modal-entete">
            <h2 id="cgu-titre">Conditions Générales d'Utilisation</h2>
            <button type="button" class="cgu-fermer" id="cgu-fermer" aria-label="Fermer">✕</button>
        </div>
        <div class="cgu-modal-corps">
            <p class="cgu-date">Dernière mise à jour : mai 2026</p>

            <h3>1. Objet</h3>
            <p>ABS est une plateforme communautaire permettant aux utilisateurs de découvrir, noter et partager des avis sur des lieux à travers le monde. En vous inscrivant, vous acceptez les présentes conditions.</p>

            <h3>2. Inscription et compte</h3>
            <p>L'inscription est gratuite et ouverte à toute personne majeure. Vous êtes responsable de la confidentialité de vos identifiants et de toute activité effectuée depuis votre compte.</p>

            <h3>3. Contenu publié</h3>
            <p>Vous vous engagez à publier des avis sincères, respectueux et basés sur des expériences réelles. Tout contenu diffamatoire, offensant ou contraire aux bonnes mœurs sera supprimé sans préavis.</p>

            <h3>4. Données personnelles</h3>
            <p>Vos données (nom, prénom, e-mail) sont collectées uniquement pour le fonctionnement du service et ne sont jamais cédées à des tiers. Vous disposez d'un droit d'accès, de rectification et de suppression.</p>

            <h3>5. Propriété intellectuelle</h3>
            <p>Les avis publiés restent votre propriété, mais vous accordez à ABS une licence d'affichage non exclusive sur la plateforme.</p>

            <h3>6. Responsabilité</h3>
            <p>ABS ne saurait être tenu responsable des informations publiées par les utilisateurs. La plateforme est fournie "en l'état" sans garantie de disponibilité continue.</p>

            <h3>7. Modification des CGU</h3>
            <p>ABS se réserve le droit de modifier les présentes conditions à tout moment. Les utilisateurs seront informés des changements importants.</p>
        </div>
        <div class="cgu-modal-pied">
            <button type="button" class="btn-auth" id="cgu-accepter">J'accepte les CGU</button>
            <button type="button" class="cgu-refuser" id="cgu-refuser">Fermer</button>
        </div>
    </div>
</div>

<script>
(function () {
    var checkbox  = document.getElementById('cgu');
    var btnSubmit = document.getElementById('btn-inscrire');
    var overlay   = document.getElementById('cgu-overlay');
    var btnOuvrir = document.getElementById('ouvrir-cgu');
    var btnFermer = document.getElementById('cgu-fermer');
    var btnAccepter = document.getElementById('cgu-accepter');
    var btnRefuser  = document.getElementById('cgu-refuser');

    // Activer/désactiver le bouton selon la case
    function majBouton() {
        btnSubmit.disabled = !checkbox.checked;
        btnSubmit.classList.toggle('btn-disabled', !checkbox.checked);
    }
    checkbox.addEventListener('change', majBouton);
    majBouton();

    // Ouvrir le modal
    btnOuvrir.addEventListener('click', function () {
        overlay.hidden = false;
        document.body.style.overflow = 'hidden';
        btnFermer.focus();
    });

    // Fermer le modal
    function fermerModal() {
        overlay.hidden = true;
        document.body.style.overflow = '';
    }
    btnFermer.addEventListener('click', fermerModal);
    btnRefuser.addEventListener('click', fermerModal);
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) fermerModal();
    });

    // Accepter depuis le modal → coche la case + ferme
    btnAccepter.addEventListener('click', function () {
        checkbox.checked = true;
        majBouton();
        fermerModal();
    });

    // Fermer avec Échap
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !overlay.hidden) fermerModal();
    });
})();
</script>