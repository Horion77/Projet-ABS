# 07 — Optimisation des événements mousemove

## Contexte

Au début, on avait mis tout le code de mise à jour de la **hover-card pays**
(le petit cadre qui affiche "X lieux · ★ 4.2/5" quand on survole un pays)
directement dans le callback de `mousemove`.

Symptôme : la carte **laggait** dès qu'on bougeait la souris au-dessus d'un
pays. Saccades visibles, FPS qui chute, même sur un PC correct.

## Analyse du problème

L'événement `mousemove` peut se déclencher **60 fois par seconde** (ou plus
sur les écrans à haut rafraîchissement). À chaque trigger, on faisait :

1. `map.setFeatureState(...)` × 2 (ancien + nouveau pays)
2. `countries.find(...)` (recherche linéaire dans le tableau)
3. Reconstruction de tout le HTML de la hover-card (`innerHTML = ...`)
4. Repositionnement (`style.left`, `style.top`)

Les opérations 1, 2 et 3 sont **lourdes** :
- `setFeatureState` déclenche un rerender du canvas WebGL.
- `find()` est O(n) sur le nombre de pays.
- `innerHTML = ...` détruit puis reconstruit le sous-arbre DOM = reflow +
  repaint.

Faire ça 60 fois/seconde = catastrophe pour les perfs.

L'opération 4 (déplacement de la div) est **légère** parce qu'on modifie
juste 2 propriétés CSS positionnées en `absolute`, ce qui ne déclenche
qu'un repaint GPU (pas de reflow). 60 fois/seconde, le navigateur encaisse
sans broncher.

## Solution retenue : **change detection**

Le pattern (qu'on retrouve dans React, Angular, etc.) :

> Ne fais le travail lourd **que si quelque chose a vraiment changé**.

Ici, le "quelque chose qui change" c'est le pays sous le curseur. Tant
que la souris se balade dans le **même** pays, pas besoin de reconstruire
la hover-card : elle affiche déjà la bonne info, on a juste à la déplacer.

On garde donc le pays courant dans une variable globale `hoveredCountryId`,
et on compare à chaque `mousemove` :

```
si nouveau pays !== ancien pays :
    BLOC LOURD (setFeatureState + find + innerHTML)
BLOC LÉGER (toujours exécuté : juste deplacer la div)
```

Résultat : le bloc lourd s'exécute **1 fois par pays survolé**, au lieu de
60 fois par seconde. La carte est fluide.

## Alternatives écartées

| Alternative | Raison du refus |
|---|---|
| **Throttle de l'événement** (1 appel max toutes les 50 ms) | Le mouvement de la div suit alors moins bien le curseur (saccades visibles). |
| **Debounce du bloc lourd** (attendre 100 ms d'inactivité) | La hover-card mettrait 100 ms à apparaître, UX dégradée. |
| **Détacher complètement la hover-card du `mousemove`** | Faisable mais complexifie : il faudrait un nouvel événement custom à émettre quand le pays change. Change detection est plus simple. |
| **Ne pas faire de hover-card du tout** | Solution la plus simple mais on perd la feature. |

## Code expliqué ligne par ligne

```js
var hoveredCountryId = null;   // variable persistente entre les appels

map.on('mousemove', 'pays-fill', function (e) {
  if (!e.features.length) return;
  var id = e.features[0].id;

  // ── BLOC LOURD : exécuté seulement si le pays change ───────────────────
  if (hoveredCountryId !== id) {
    // Désactiver l'état hover sur l'ancien pays
    if (hoveredCountryId) {
      map.setFeatureState(
        { source: 'pays-source', sourceLayer: 'country_boundaries', id: hoveredCountryId },
        { hover: false }
      );
    }
    // Activer l'état hover sur le nouveau
    hoveredCountryId = id;
    map.setFeatureState(
      { source: 'pays-source', sourceLayer: 'country_boundaries', id: id },
      { hover: true }
    );
    map.getCanvas().style.cursor = 'pointer';

    // Reconstruction du contenu de la hover-card
    if (hoverCard) {
      var paysDB = trouverPaysDB(String(id));
      if (paysDB) {
        var nb       = parseInt(paysDB.places_count || 0, 10);
        var note     = paysDB.avg_rating;
        var statsTxt = nb + ' lieu' + (nb > 1 ? 'x' : '');
        if (note) statsTxt += ' · ★ ' + note + '/5';
        hoverCard.innerHTML =
          '<h5>' + escapeHtml(paysDB.nom) + '</h5>' +
          '<div class="phc-stats">' + statsTxt + '</div>';
        hoverCard.hidden = false;
      } else {
        hoverCard.hidden = true;
      }
    }
  }

  // ── BLOC LÉGER : exécuté à chaque pixel ────────────────────────────────
  if (hoverCard && !hoverCard.hidden) {
    hoverCard.style.left = (e.point.x + 16) + 'px';
    hoverCard.style.top  = (e.point.y + 16) + 'px';
  }
});
```

### Détail du flow

| Action utilisateur | Bloc lourd ? | Bloc léger ? |
|---|---|---|
| Souris entre dans la France | ✅ (id passe de null à "FRA") | ✅ |
| Souris bouge dans la France | ❌ | ✅ |
| Souris passe en Allemagne | ✅ (id "FRA" → "DEU") | ✅ |
| Souris sort de la couche `pays-fill` | ❌ (handler `mouseleave` séparé) | ❌ |

Sur un trajet de 1 seconde dans la même France, on a typiquement 60
appels au callback :
- **1 fois** seulement on rentre dans le bloc lourd.
- **60 fois** on exécute le bloc léger (= 120 propriétés CSS modifiées).

## Vocabulaire technique pour l'oral

Quelques termes à connaître pour expliquer ça à un prof :

- **Change detection** : pattern qui consiste à comparer l'ancien état au
  nouveau pour ne faire le rerender que si quelque chose a vraiment changé.
- **Hot path / cold path** : le "hot path" est le code exécuté très souvent
  (ici le bloc léger), le "cold path" plus rarement (ici le bloc lourd).
  On optimise en priorité le hot path.
- **Reflow / repaint** :
  - **Reflow** = recalcul de la position et de la taille des éléments du
    DOM. Très coûteux.
  - **Repaint** = redessin sans changer la géométrie. Beaucoup moins
    coûteux, peut être accéléré par le GPU.
  - Modifier `innerHTML` provoque reflow + repaint.
  - Modifier `style.left` sur un élément positionné en absolute provoque
    seulement un repaint.
- **GPU-accelerated CSS** : certaines propriétés (`transform`, `opacity`,
  `left`/`top` sur position: absolute) sont déléguées au GPU par le
  navigateur, ce qui rend leur mise à jour quasi gratuite.

## Évolutions possibles

- **Appliquer le même pattern à `regions-fill`** : actuellement la couche
  régions a déjà une simplification (pas de hover-card associée) mais on
  pourrait en ajouter une.
- **`requestAnimationFrame`** pour le bloc léger : aligner le déplacement
  de la hover-card sur le framerate du navigateur (déjà ~60 fps mais
  l'API garantit la synchronisation avec le repaint).
- **`will-change: transform`** en CSS sur la hover-card pour forcer le
  navigateur à promouvoir l'élément sur une couche GPU dédiée.
- **Migrer `style.left/top` vers `transform: translate(...)`** : encore
  un peu plus rapide pour le GPU.
