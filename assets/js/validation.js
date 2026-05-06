// Validation front-end des formulaires


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