<?php
/** Connexion (vue) — reçoit : $old */
$old = $old ?? [];
?>
    <div class="conteneur conteneur-auth">
        <h1 class="titre-auth">Se connecter</h1>
        <p class="sous-titre-auth">Entrez l’e-mail et le mot de passe de votre compte.</p>
        <?= displayErrors() ?>
        <?= displaySuccess() ?>
        <form class="form-auth" method="post" action="../actions/login_action.php" novalidate>
            <div class="groupe-champ">
                <label for="email">E-mail *</label>
                <input type="email" id="email" name="email" required autocomplete="email"
                       value="<?= e($old['email'] ?? '') ?>">
            </div>
            <div class="groupe-champ">
                <label for="password">Mot de passe *</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button class="btn-auth" type="submit">Se connecter</button>
        </form>
        <p class="lien-auth-bas">Pas encore inscrit ? <a href="inscription.php">Créer un compte</a></p>
    </div>
