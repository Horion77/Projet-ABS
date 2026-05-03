<?php
/**
 * Barre de navigation principale.
 */
?>
    <header class="site-header">
        <div class="header-inner">
            <a class="logo" href="/">ABS</a>
            <button type="button" class="nav-toggle" aria-expanded="false" aria-controls="nav-site">Menu</button>
            <nav id="nav-site" class="nav-site" aria-label="Principale">
                <a href="/">Accueil</a>
                <a href="/carte">Carte</a>
                <a href="/avis">Avis</a>
                <?php if (isLoggedIn()) : ?>
                    <span class="nav-sep" aria-hidden="true"></span>
                    <?php
                    $nomNav = trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
                    if ($nomNav === '') {
                        $nomNav = 'Mon compte';
                    }
                    ?>
                    <a href="/profil"><?= e($nomNav) ?></a>
                    <form class="nav-deconnexion" method="post" action="/deconnexion">
                        <button type="submit" class="nav-auth nav-deconnexion-btn">Déconnexion</button>
                    </form>
                <?php else : ?>
                    <span class="nav-sep" aria-hidden="true"></span>
                    <a class="nav-auth" href="/connexion">Connexion</a>
                    <a class="nav-auth" href="/inscription">Inscription</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
