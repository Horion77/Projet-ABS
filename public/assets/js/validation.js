/**
 * Validation front (natif) — avis lieu + inscription.
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
