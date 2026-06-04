<?php
/**
 * Barre de navigation principale.
 */
?>
    <header class="site-header">
        <div class="header-inner">
            <a class="logo" href="<?= e(url()) ?>">ABS</a>
            <?php
            // aria-expanded="false" : état initial du menu mobile, mis à jour par
            // le JS inline de pied.php (toggle entre true/false au clic).
            ?>
            <button type="button" class="nav-toggle" aria-expanded="false" aria-controls="nav-site">Menu</button>
            <nav id="nav-site" class="nav-site" aria-label="Principale">
                <a href="<?= e(url()) ?>">Accueil</a>
                <a href="<?= e(url('carte')) ?>">Carte</a>
                <a href="<?= e(url('decouvrir')) ?>">Découvrir</a>
                <?php
                // isModerateur() : vrai pour admin (rôle 1) ET modérateur (rôle 2).
                // Le lien Modération n'est donc pas visible pour un utilisateur simple.
                if (isModerateur()) : ?>
                    <a href="<?= e(url('admin/signalements')) ?>" class="nav-admin">Modération</a>
                <?php endif; ?>
                <?php if (isLoggedIn()) : ?>
                    <span class="nav-sep" aria-hidden="true"></span>
                    <?php
                    // Prénom + nom lus directement depuis $_SESSION (stockés à la connexion
                    // et mis à jour par ProfilController::modifierInfos). Fallback 'Mon compte'
                    // si la session est ancienne et ne contient pas encore ces clés.
                    $nomNav = trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
                    if ($nomNav === '') {
                        $nomNav = 'Mon compte';
                    }
                    ?>
                    <a href="<?= e(url('profil')) ?>"><?= e($nomNav) ?></a>
                    <!-- Déconnexion en POST : un GET serait déclenché par les bots de
                         préchargement, les bookmarks ou le bouton retour du navigateur.
                         POST garantit une action intentionnelle de l'utilisateur. -->
                    <form class="nav-deconnexion" method="post" action="<?= e(url('deconnexion')) ?>">
                        <button type="submit" class="nav-auth nav-deconnexion-btn">Déconnexion</button>
                    </form>
                <?php else : ?>
                    <span class="nav-sep" aria-hidden="true"></span>
                    <a class="nav-auth" href="<?= e(url('connexion')) ?>">Connexion</a>
                    <a class="nav-auth" href="<?= e(url('inscription')) ?>">Inscription</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
