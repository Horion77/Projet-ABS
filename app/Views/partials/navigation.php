<?php
/**
 * Barre de navigation principale.
 */
?>
    <header class="site-header">
        <div class="header-inner">
            <a class="logo" href="<?= e(url()) ?>">ABS</a>
            <button type="button" class="nav-toggle" aria-expanded="false" aria-controls="nav-site">Menu</button>
            <nav id="nav-site" class="nav-site" aria-label="Principale">
                <a href="<?= e(url()) ?>">Accueil</a>
                <a href="<?= e(url('carte')) ?>">Carte</a>
                <a href="<?= e(url('avis')) ?>">Avis</a>
                <?php if (isLoggedIn()) : ?>
                    <span class="nav-sep" aria-hidden="true"></span>
                    <?php
                    $nomNav = trim(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? ''));
                    if ($nomNav === '') {
                        $nomNav = 'Mon compte';
                    }
                    ?>
                    <a href="<?= e(url('profil')) ?>"><?= e($nomNav) ?></a>
                    <!-- POST : la déconnexion modifie l'état ; on évite un simple lien GET (bookmark, préchargement). -->
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
