<?php
/**
 * Formulaire de connexion. Variable $old : tableau des champs saisis avant
 * l'erreur (Session::flashAncien), utilisé pour repopuler le champ e-mail
 * sans que l'utilisateur ait à le ressaisir.
 */
$old = $old ?? [];
?>
<div class="conteneur-auth">
    <div class="card-auth">
        <h1 class="titre-auth">Se connecter</h1>
        <p class="sous-titre-auth">Entrez l'e-mail et le mot de passe de votre compte.</p>
        <?php require __DIR__ . '/../partials/messages.php'; ?>
        <?php
        // novalidate : désactive la validation native du navigateur.
        // La validation est faite côté serveur (ConnexionController) ET côté JS
        // (validation.js) pour un contrôle plus fin et des messages cohérents.
        ?>
        <form class="form-auth" method="post" action="<?= e(url('connexion')) ?>" novalidate>
            <div class="groupe-champ">
                <label for="email">E-mail</label>
                <?php
                // autocomplete="email" : indique aux gestionnaires de mots de passe
                // et au navigateur qu'il s'agit d'un champ de connexion (pas inscription).
                // value repopulé avec $old['email'] pour éviter une ressaisie après erreur.
                ?>
                <input type="email" id="email" name="email" required autocomplete="email"
                       placeholder="votre@email.com"
                       value="<?= e((string) ($old['email'] ?? '')) ?>">
            </div>
            <div class="groupe-champ">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required
                       autocomplete="current-password" placeholder="••••••••">
            </div>
            <button class="btn-auth" type="submit">Se connecter</button>
        </form>
        <p class="lien-auth-bas">Pas encore inscrit ? <a href="<?= e(url('inscription')) ?>">Créer un compte</a></p>
    </div>
</div>