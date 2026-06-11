    </main><!-- fermeture du <main> ouvert dans partials/entete.php -->
    <footer class="site-footer">
        <div class="footer-inner">
            <!-- date('Y') : année courante dynamique, pas besoin de la mettre à jour chaque année -->
            <p>Projet ABS — Programmation Web, <?= (int) date('Y') ?></p>
            <p class="footer-mention">Avis de lieux et d'escapades, sans framework.</p>
        </div>
    </footer>
    <?php
    // JS inline (IIFE) pour le menu burger mobile : trop court pour justifier un fichier séparé.
    // Placé ici (en bas de page) pour que le DOM soit déjà parsé, pas besoin de DOMContentLoaded.
    ?>
    <script>
    (function () {
        var t = document.querySelector('.nav-toggle');
        if (!t) return;
        var n = document.getElementById('nav-site');
        t.addEventListener('click', function () {
            var o = t.getAttribute('aria-expanded') === 'true';
            t.setAttribute('aria-expanded', !o); // met a jour l'etat ARIA pour les lecteurs d'ecran
            if (n) n.classList.toggle('nav-open', !o);
        });
    })();
    </script>
    <!-- defer : chargement en parallele du HTML, execution apres le parsing complet du DOM -->
    <script src="<?= e(asset('js/validation.js')) ?>" defer></script>
    <?php
    // $bodyClass est injectée par les contrôleurs via extract() dans Vue::afficher().
    // Défaut vide : appel sécurisé à str_contains sans risque de variable indéfinie.
    $bodyClass = $bodyClass ?? '';
    // stars.js uniquement sur les pages avec effet étoiles (classe page-stars).
    // Évite de créer 200 éléments DOM inutiles sur les autres pages.
    if ($bodyClass !== '' && str_contains($bodyClass, 'page-stars')) : ?>
<script src="<?= e(asset('js/stars.js')) ?>" defer></script>
<?php endif; ?>
</body>
</html>
