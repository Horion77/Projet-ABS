<?php
/**
 * Modèle du fichier de configuration Mapbox.
 * Copier en mapbox.php (non versionné) et remplacer le jeton ci-dessous par le vôtre.
 *
 * Le jeton doit commencer par pk. (public) — ne jamais utiliser un jeton sk. (secret) ici
 * car cette valeur est injectée dans le HTML et visible dans le source de la page.
 *
 * Étapes :
 *   cp app/Config/mapbox.exemple.php app/Config/mapbox.php
 *   # puis remplacer la valeur retournée par votre jeton pk.
 *
 * @see https://account.mapbox.com/access-tokens/
 */
return 'pk.VOTRE_JETON_PUBLIC_ICI';
