    </main>
    <footer class="site-footer">
        <div class="footer-inner">
            <p>Projet ABS — Programmation Web, <?= (int) date('Y') ?></p>
            <p class="footer-mention">Avis de lieux et d’escapades, sans framework.</p>
        </div>
    </footer>
    <script>
    (function() {
        var t = document.querySelector('.nav-toggle');
        if (!t) return;
        var n = document.getElementById('nav-site');
        t.addEventListener('click', function() {
            var o = t.getAttribute('aria-expanded') === 'true';
            t.setAttribute('aria-expanded', !o);
            if (n) n.classList.toggle('nav-open', !o);
        });
    })();
    </script>
    <?php if (isset($prefixRacine) && is_string($prefixRacine)) : ?>
    <script src="<?= e($prefixRacine) ?>assets/js/validation.js" defer></script>
    <?php endif; ?>
</body>
</html>
