/**
 * Validation front (natif) — avis lieu + inscription.
 * 
 * version : sara
 * date : 07/05/2026
 * // Validation front-end des formulaires


// Validation du formulaire d'avis
document.addEventListener('DOMContentLoaded', function() {
    const formulaireAvis = document.querySelector('#formulaire-avis');

    if (formulaireAvis) {
        formulaireAvis.addEventListener('submit', function(e) {
            const note = document.querySelector('input[name="rating"]:checked');
            const commentaire = document.querySelector('#commentaire');
            let erreurs = false;

            if (!note) {
                afficherErreur('erreur-note', 'Veuillez sélectionner une note.');
                erreurs = true;
            } else {
                supprimerErreur('erreur-note');
            }

            if (commentaire.value.trim() === '') {
                afficherErreur('erreur-commentaire', 'Le commentaire est obligatoire.');
                erreurs = true;
            } else {
                supprimerErreur('erreur-commentaire');
            }

            if (erreurs) {
                e.preventDefault();
            }
        });
    }
});

// Validation du formulaire d'inscription
document.addEventListener('DOMContentLoaded', function() {
    const formulaireInscription = document.querySelector('#formulaire-inscription');

    if (formulaireInscription) {
        formulaireInscription.addEventListener('submit', function(e) {
            const email = document.querySelector('#email');
            const motDePasse = document.querySelector('#password');
            const confirmation = document.querySelector('#password_confirm');
            let erreurs = false;

            // Vérifier le format de l'email
            const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!regexEmail.test(email.value)) {
                afficherErreur('erreur-email', 'Veuillez entrer un email valide.');
                erreurs = true;
            } else {
                supprimerErreur('erreur-email');
            }

            // Vérifier que les mots de passe correspondent
            if (motDePasse.value !== confirmation.value) {
                afficherErreur('erreur-password', 'Les mots de passe ne correspondent pas.');
                erreurs = true;
            } else {
                supprimerErreur('erreur-password');
            }

            // Vérifier la longueur du mot de passe
            if (motDePasse.value.length < 6) {
                afficherErreur('erreur-password', 'Le mot de passe doit faire au moins 6 caractères.');
                erreurs = true;
            }

            if (erreurs) {
                e.preventDefault();
            }
        });
    }
});


// Fonctions utilitaires pour afficher/supprimer les erreurs
function afficherErreur(id, message) {
    let erreur = document.getElementById(id);

    if (!erreur) {
        erreur = document.createElement('p');
        erreur.id = id;
        erreur.style.color = 'red';
        erreur.style.fontSize = '14px';
        erreur.style.marginTop = '4px';
    }

    erreur.textContent = message;
    const champ = document.querySelector('[data-erreur="' + id + '"]');
    if (champ) {
        champ.insertAdjacentElement('afterend', erreur);
    }
}

function supprimerErreur(id) {
    const erreur = document.getElementById(id);
    if (erreur) {
        erreur.remove();
    }
}
 */
(function () {
  'use strict';

  function showErr(id, show) {
    var el = document.getElementById(id);
    if (!el) return;
    el.hidden = !show;
  }

  function showInlineText(id, msg) {
    var el = document.getElementById(id);
    if (!el) return;
    if (msg) {
      el.textContent = msg;
      el.hidden = false;
    } else {
      el.textContent = '';
      el.hidden = true;
    }
  }

  function isValidEmail(s) {
    if (!s || typeof s !== 'string') return false;
    var t = s.trim();
    if (t.indexOf('@') < 1) return false;
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(t);
  }

  function bindStars(form) {
    var wrap = form.querySelector('#stars-input');
    var hidden = form.querySelector('#rating-value');
    if (!wrap || !hidden) return;

    var buttons = wrap.querySelectorAll('.star-btn');
    var current = parseInt(hidden.value, 10) || 0;

    function paint(hoverVal) {
      var ref = hoverVal > 0 ? hoverVal : current;
      buttons.forEach(function (btn, idx) {
        var v = idx + 1;
        btn.classList.toggle('is-on', v <= ref);
      });
    }

    buttons.forEach(function (btn, idx) {
      var v = idx + 1;
      btn.addEventListener('mouseenter', function () {
        paint(v);
      });
      btn.addEventListener('mouseleave', function () {
        paint(0);
      });
      btn.addEventListener('click', function () {
        current = v;
        hidden.value = String(v);
        paint(0);
        showErr('err-rating', false);
      });
    });

    wrap.addEventListener('mouseleave', function () {
      paint(0);
    });

    if (hidden.value) {
      current = parseInt(hidden.value, 10) || 0;
      paint(0);
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var formAvis = document.getElementById('form-avis-lieu');
    if (formAvis) {
      bindStars(formAvis);
      formAvis.addEventListener('submit', function (e) {
        var rating = (formAvis.querySelector('#rating-value') || {}).value;
        var commentEl = formAvis.querySelector('#avis-comment');
        var comment = commentEl ? String(commentEl.value || '').trim() : '';

        var ok = true;
        if (!rating || parseInt(rating, 10) < 1 || parseInt(rating, 10) > 5) {
          showErr('err-rating', true);
          ok = false;
        } else {
          showErr('err-rating', false);
        }
        if (!comment) {
          showErr('err-comment', true);
          ok = false;
        } else {
          showErr('err-comment', false);
        }
        if (!ok) {
          e.preventDefault();
        }
      });
    }

    var formIns = document.getElementById('form-inscription');
    if (formIns) {
      formIns.addEventListener('submit', function (e) {
        var email = (formIns.querySelector('[name="email"]') || {}).value || '';
        var p1 = (formIns.querySelector('[name="password"]') || {}).value || '';
        var p2 = (formIns.querySelector('[name="password_confirm"]') || {}).value || '';
        showInlineText('err-ins-email', '');
        showInlineText('err-ins-pass', '');
        var ok = true;
        if (!isValidEmail(email)) {
          ok = false;
          showInlineText('err-ins-email', 'Veuillez saisir une adresse e-mail valide.');
        }
        if (p1 !== p2) {
          ok = false;
          showInlineText('err-ins-pass', 'Les mots de passe ne correspondent pas.');
        }
        if (!ok) {
          e.preventDefault();
        }
      });
    }
  });
})();
