<?php
/** Inscription (vue) — reçoit : $old (tableau) */
$old = $old ?? [];
?>
    <div class="conteneur conteneur-auth">
        <h1 class="titre-auth">Créer un compte</h1>
        <p class="sous-titre-auth">Renseignez vos identifiants pour participer aux avis.</p>
        <?= displayErrors() ?>
        <?= displaySuccess() ?>
        <form class="form-auth" method="post" action="../actions/inscription_action.php" novalidate>
            <div class="groupe-champ">
                <label for="prenom">Prénom *</label>
                <input type="text" id="prenom" name="prenom" required minlength="2" maxlength="80" autocomplete="given-name"
                       value="<?= e($old['prenom'] ?? '') ?>">
            </div>
            <div class="groupe-champ">
                <label for="nom">Nom *</label>
                <input type="text" id="nom" name="nom" required minlength="2" maxlength="80" autocomplete="family-name"
                       value="<?= e($old['nom'] ?? '') ?>">
            </div>
            <div class="groupe-champ">
                <label for="email">E-mail *</label>
                <input type="email" id="email" name="email" required autocomplete="email"
                       value="<?= e($old['email'] ?? '') ?>">
            </div>
            <div class="groupe-champ">
                <label for="password">Mot de passe *</label>
                <input type="password" id="password" name="password" required minlength="6" autocomplete="new-password">
            </div>
            <div class="groupe-champ">
                <label for="password_confirm">Confirmer le mot de passe *</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="6" autocomplete="new-password">
            </div>
            <button class="btn-auth" type="submit">S’inscrire</button>
        </form>
        <p class="lien-auth-bas">Déjà inscrit ? <a href="login.php">Se connecter</a></p>
    </div>
