const fs = require("fs");
const path = require("path");
const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  AlignmentType, LevelFormat, HeadingLevel, BorderStyle, WidthType,
  ShadingType, PageNumber, Header, Footer, PageBreak,
} = require("docx");

// ---- Helpers ----------------------------------------------------------------
// Les dimensions sont en twips (twentieth of a point) : 1440 twips = 1 pouce.
// US Letter = 12240 twips de large. Avec marges 1" de chaque côté (1440×2) :
// zone utile = 12240 - 2880 = 9360 twips.
const CONTENT_W = 9360;
const border = { style: BorderStyle.SINGLE, size: 1, color: "CCCCCC" };
const borders = { top: border, bottom: border, left: border, right: border };
const HEAD_FILL = "D9E2F3";        // bleu clair en-tête de tableau
const cellMargins = { top: 60, bottom: 60, left: 110, right: 110 };

// Interligne en twips : 240 = simple, 480 = double. 340 ≈ 1.4× (confort de lecture).
const LINE = 340;

function P(text, opts = {}) {
  return new Paragraph({
    spacing: { after: opts.after ?? 140, before: opts.before ?? 0, line: LINE, lineRule: "auto" },
    alignment: opts.align,
    children: [new TextRun({ text, bold: opts.bold, italics: opts.italics, size: opts.size })],
  });
}

function H(text, level) {
  return new Paragraph({ heading: level, children: [new TextRun(text)] });
}

function bullet(text) {
  return new Paragraph({
    numbering: { reference: "puces", level: 0 },
    spacing: { after: 90, line: LINE, lineRule: "auto" },
    children: textRuns(text),
  });
}

function num(text) {
  return new Paragraph({
    numbering: { reference: "etapes", level: 0 },
    spacing: { after: 110, line: LINE, lineRule: "auto" },
    children: textRuns(text),
  });
}

function code(text) {
  return new Paragraph({
    spacing: { after: 120, before: 40 },
    shading: { fill: "F2F2F2", type: ShadingType.CLEAR },
    children: [new TextRun({ text, font: "Consolas", size: 18 })],
  });
}

function figurePlaceholder(text) {
  return new Paragraph({
    spacing: { before: 120, after: 160 },
    alignment: AlignmentType.CENTER,
    border: { top: border, bottom: border, left: border, right: border },
    children: [new TextRun({ text, italics: true, color: "555555" })],
  });
}

// Parse inline `code` segments inside a string -> runs
function textRuns(text) {
  const parts = text.split(/(`[^`]+`)/g).filter(s => s !== "");
  return parts.map(seg => {
    if (seg.startsWith("`") && seg.endsWith("`")) {
      return new TextRun({ text: seg.slice(1, -1), font: "Consolas", size: 18 });
    }
    return new TextRun(seg);
  });
}

function para(text, opts = {}) {
  return new Paragraph({
    spacing: { after: opts.after ?? 140, line: LINE, lineRule: "auto" },
    alignment: opts.align ?? AlignmentType.JUSTIFIED,
    children: textRuns(text),
  });
}

// Table builder: header row + body rows. cols = [{w, label}], rows = [[...]]
function table(cols, rows) {
  const widths = cols.map(c => c.w);
  const headerRow = new TableRow({
    tableHeader: true,
    children: cols.map(c => new TableCell({
      borders, width: { size: c.w, type: WidthType.DXA }, margins: cellMargins,
      shading: { fill: HEAD_FILL, type: ShadingType.CLEAR },
      children: [new Paragraph({ children: [new TextRun({ text: c.label, bold: true, size: 19 })] })],
    })),
  });
  const bodyRows = rows.map(r => new TableRow({
    children: r.map((cell, i) => new TableCell({
      borders, width: { size: widths[i], type: WidthType.DXA }, margins: cellMargins,
      children: String(cell).split("\n").map(line => new Paragraph({
        children: textRuns(line).map(rn => { rn.constructor; return rn; }),
        // force taille 18 pour les cellules
      })).map(p => p),
    })),
  }));
  // bodyRows (ci-dessus) est inutilisé : la lib docx n'applique pas la taille de
  // police via textRuns imbriqués dans une cellule de tableau. bodyRows2 contourne
  // ce problème en passant par lineRuns() qui force size:18 sur chaque TextRun.
  // bodyRows est conservé pour référence mais seul bodyRows2 est passé au Table.
  const bodyRows2 = rows.map(r => new TableRow({
    children: r.map((cell, i) => new TableCell({
      borders, width: { size: widths[i], type: WidthType.DXA }, margins: cellMargins,
      children: String(cell).split("\n").map(line => new Paragraph({
        spacing: { after: 20 },
        children: lineRuns(line),
      })),
    })),
  }));
  return new Table({
    width: { size: CONTENT_W, type: WidthType.DXA },
    columnWidths: widths,
    rows: [headerRow, ...bodyRows2],
  });
}

function lineRuns(line) {
  const parts = line.split(/(`[^`]+`)/g).filter(s => s !== "");
  if (parts.length === 0) return [new TextRun({ text: "", size: 18 })];
  return parts.map(seg => {
    if (seg.startsWith("`") && seg.endsWith("`")) {
      return new TextRun({ text: seg.slice(1, -1), font: "Consolas", size: 17 });
    }
    return new TextRun({ text: seg, size: 18 });
  });
}

// ---- Document ---------------------------------------------------------------
const children = [];

// ============ SECTION III ============
children.push(H("III. Conception de la base de données", HeadingLevel.HEADING_1));
children.push(para(
  "La base de données du projet ABS est conçue pour le moteur MySQL 8.0 (encodage `utf8mb4`, " +
  "collation `utf8mb4_unicode_ci`) et repose entièrement sur le moteur de stockage InnoDB, qui " +
  "garantit le support des clés étrangères et des transactions. Le modèle relationnel a été " +
  "construit en partant d'un modèle conceptuel (MCD), puis traduit en modèle logique (MLD) avant " +
  "d'être implémenté sous forme de tables, de contraintes et de vues."
));

// III.1 MCD
children.push(H("III.1 Modèle Conceptuel de Données (MCD)", HeadingLevel.HEADING_2));
children.push(para(
  "Le MCD décrit les entités du domaine et leurs associations, indépendamment de toute " +
  "implémentation technique. Les entités principales identifiées sont les suivantes :"
));
children.push(bullet("Rôle : définit les droits d'un compte (administrateur, modérateur, utilisateur)."));
children.push(bullet("Utilisateur : compte d'un membre du site (identité, e-mail, mot de passe haché, avatar, biographie)."));
children.push(bullet("Pays, Ville, Lieu : la hiérarchie géographique. Un pays contient des villes, une ville contient des lieux (monuments, restaurants, musées…)."));
children.push(bullet("Catégorie de lieu : classe un lieu (Musée, Restaurant, Plage, Monument…)."));
children.push(bullet("Avis : note (1 à 5) et commentaire qu'un utilisateur attribue à un niveau géographique (un pays, une ville OU un lieu)."));
children.push(bullet("Photo d'avis : images associées à un avis."));
children.push(bullet("Commentaire : message posté sous un avis, avec possibilité de répondre à un autre commentaire (fil de discussion)."));
children.push(bullet("Like (avis et commentaire) : marque d'appréciation d'un utilisateur, unique par contenu."));
children.push(bullet("Signalement : remontée d'un contenu litigieux (avis ou commentaire) par un utilisateur, traité par la modération."));
children.push(bullet("Visite : journal personnel des lieux visités par un utilisateur."));
children.push(bullet("Collection et Élément de collection : listes thématiques (à la Letterboxd) regroupant des pays, villes ou lieux."));
children.push(para(
  "Les associations notables sont : un Utilisateur « rédige » plusieurs Avis ; un Avis « porte sur » " +
  "un seul niveau géographique ; un Commentaire « répond éventuellement à » un autre Commentaire " +
  "(association réflexive) ; un Utilisateur « signale » des contenus."
));
children.push(figurePlaceholder("Figure III.1 — Modèle Conceptuel de Données (MCD)"));

// III.2 MLD
children.push(H("III.2 Modèle Logique de Données (MLD)", HeadingLevel.HEADING_2));
children.push(para(
  "Le passage au MLD traduit chaque entité en relation (table) et chaque association en clé " +
  "étrangère. La clé primaire est soulignée par la mention (PK) et les clés étrangères sont " +
  "préfixées par le symbole #. Le schéma relationnel obtenu est le suivant :"
));
const mld = [
  "role (id_role PK, libelle)",
  "utilisateur (id_utilisateur PK, nom, prenom, email, telephone, password_hash, avatar_url, bio, created_at, #id_role)",
  "pays (id_pays PK, nom, code_iso, continent)",
  "ville (id_ville PK, nom, #id_pays)",
  "categorie_lieu (id_categorie PK, libelle)",
  "lieu (id_lieu PK, type, icon, nom, description, latitude, longitude, adresse, image_url, #id_categorie, #id_ville)",
  "avis (id_avis PK, note, titre, description, visibility, created_at, #id_utilisateur, #id_pays, #id_ville, #id_lieu)",
  "photo_avis (id_photo PK, url, ordre, #id_avis)",
  "commentaire (id_commentaire PK, texte, created_at, #id_utilisateur, #id_avis, #id_parent)",
  "like_commentaire (id_like PK, created_at, #id_utilisateur, #id_commentaire)",
  "like_avis (#id_utilisateur, #id_avis PK composite, created_at)",
  "signalement (id_signalement PK, cible_type, cible_id, motif, details, statut, created_at, #id_utilisateur)",
  "visite (id_visite PK, date_visite, note_perso, #id_utilisateur, #id_pays, #id_ville, #id_lieu)",
  "collection (id_collection PK, titre, description, couverture_url, visibility, created_at, #id_utilisateur)",
  "collection_item (id_item PK, ordre, created_at, #id_collection, #id_pays, #id_ville, #id_lieu)",
];
mld.forEach(r => children.push(code(r)));
children.push(figurePlaceholder("Figure III.2 — Modèle Logique de Données (MLD)"));

// III.3 Description des tables et contraintes
children.push(H("III.3 Description des tables et contraintes", HeadingLevel.HEADING_2));
children.push(para(
  "La base compte 15 tables. Le tableau ci-dessous synthétise le rôle de chacune ainsi que ses " +
  "principales contraintes d'intégrité."
));
children.push(table(
  [{ w: 2200, label: "Table" }, { w: 4360, label: "Rôle" }, { w: 2800, label: "Contraintes principales" }],
  [
    ["role", "Niveaux de droits du site.", "PK `id_role` ; `libelle` UNIQUE. Données : admin, moderateur, utilisateur."],
    ["utilisateur", "Comptes des membres.", "PK ; `email` UNIQUE ; FK `id_role` (ON DELETE RESTRICT, défaut 3)."],
    ["pays", "Pays référencés.", "PK ; `code_iso` UNIQUE ; CHECK `CHAR_LENGTH(code_iso)=3`."],
    ["ville", "Villes rattachées à un pays.", "PK ; FK `id_pays` (ON DELETE CASCADE)."],
    ["categorie_lieu", "Catégories de lieux.", "PK ; `libelle` UNIQUE."],
    ["lieu", "Points d'intérêt géolocalisés.", "PK ; FK `id_categorie` (RESTRICT), `id_ville` (CASCADE) ; CHECK latitude/longitude dans les bornes GPS."],
    ["avis", "Notes et avis des utilisateurs.", "PK ; CHECK `note BETWEEN 1 AND 5` ; UNIQUE (utilisateur+pays), (utilisateur+ville), (utilisateur+lieu) ; FK vers utilisateur/pays/ville/lieu (CASCADE)."],
    ["photo_avis", "Photos jointes à un avis.", "PK ; FK `id_avis` (CASCADE)."],
    ["commentaire", "Commentaires sur les avis (fils).", "PK ; FK `id_utilisateur`, `id_avis` (CASCADE) ; FK réflexive `id_parent` (ON DELETE SET NULL)."],
    ["like_commentaire", "Likes sur les commentaires.", "PK ; UNIQUE (utilisateur+commentaire) ; FK (CASCADE)."],
    ["like_avis", "Likes sur les avis.", "PK composite (utilisateur+avis) ; FK (CASCADE)."],
    ["signalement", "Contenus signalés à la modération.", "PK ; polymorphe (`cible_type`+`cible_id`, sans FK) ; UNIQUE (utilisateur+cible) ; FK `id_utilisateur` (CASCADE)."],
    ["visite", "Journal des lieux visités.", "PK ; FK utilisateur/pays/ville/lieu (CASCADE)."],
    ["collection", "Listes thématiques de l'utilisateur.", "PK ; FK `id_utilisateur` (CASCADE)."],
    ["collection_item", "Éléments d'une collection.", "PK ; UNIQUE (collection+pays/ville/lieu) ; FK (CASCADE)."],
  ]
));
children.push(para(
  "Plusieurs choix de conception méritent d'être soulignés :", { after: 80 }
));
children.push(bullet("Avis « polymorphe » : un avis cible exactement un niveau géographique grâce aux trois clés étrangères optionnelles `id_pays`, `id_ville`, `id_lieu`. Les contraintes d'unicité empêchent un même utilisateur de noter deux fois le même pays, la même ville ou le même lieu."));
children.push(bullet("Commentaires en fil : la clé étrangère réflexive `id_parent` permet de répondre à un commentaire. Le `ON DELETE SET NULL` conserve les réponses si le commentaire parent est supprimé."));
children.push(bullet("Signalement volontairement sans clé étrangère sur `cible_id` : on accepte qu'un signalement devienne orphelin si le contenu visé est supprimé, ce qui simplifie la modération."));
children.push(bullet("Suppression en cascade : la suppression d'un utilisateur entraîne celle de ses avis, commentaires, likes et signalements, garantissant l'absence de données orphelines."));
children.push(para(
  "Enfin, trois vues SQL facilitent les classements en encapsulant les agrégations :", { after: 80 }
));
children.push(bullet("`vue_classement_pays` : note moyenne et nombre d'avis publics par pays."));
children.push(bullet("`vue_classement_villes` : idem au niveau ville."));
children.push(bullet("`vue_classement_lieux` : idem au niveau lieu, avec catégorie, ville et pays."));
children.push(para(
  "Ces vues ne prennent en compte que les avis publics (`visibility = 'public'`), de sorte que les " +
  "avis privés n'apparaissent pas dans les classements."
));

// III.4 Flux d'une requête (générique)
children.push(H("III.4 Flux d'une requête (diagramme de séquence UML)", HeadingLevel.HEADING_2));
children.push(para(
  "L'application suit une architecture Modèle-Vue-Contrôleur (MVC) avec un contrôleur frontal " +
  "unique. Toute requête HTTP suit le même cheminement :"
));
children.push(num("Le navigateur envoie une requête (GET ou POST) ; Apache la redirige vers `public/index.php`, le contrôleur frontal."));
children.push(num("`index.php` charge le `bootstrap` (autoload, session, fonctions d'aide) puis instancie le `Routeur` avec la table des routes."));
children.push(num("Le `Routeur` compare la méthode et le chemin de la requête aux routes déclarées, puis appelle la méthode du contrôleur correspondant (ou renvoie une erreur 404/405)."));
children.push(num("Le contrôleur valide les données reçues et sollicite un ou plusieurs Modèles."));
children.push(num("Le Modèle exécute des requêtes SQL préparées (PDO) sur la base MySQL et retourne les résultats."));
children.push(num("Le contrôleur transmet les données à une Vue, qui produit le HTML renvoyé au navigateur."));
children.push(figurePlaceholder("Figure III.3 — Diagramme de séquence générique d'une requête (MVC)"));

// III.5 Séquence soumission d'un avis
children.push(H("III.5 Diagramme de séquence — soumission d'un avis", HeadingLevel.HEADING_2));
children.push(para(
  "Pour illustrer ce cheminement sur un cas concret, voici le traitement d'une soumission d'avis " +
  "(`POST /avis`), de l'envoi du formulaire à l'enregistrement en base :"
));
children.push(num("L'utilisateur soumet le formulaire d'avis depuis la fiche d'un lieu ; la requête `POST /avis` arrive sur `index.php` puis le `Routeur`."));
children.push(num("Le `Routeur` appelle `AvisController::traiterSoumission()`."));
children.push(num("Le contrôleur vérifie que la requête est bien un POST, puis que l'utilisateur est connecté via `Session::estConnecte()` (sinon redirection vers `/connexion`)."));
children.push(num("Il récupère et valide les champs : identifiant du lieu, note comprise entre 1 et 5, commentaire non vide et d'au plus 8000 caractères, visibilité (public/privé)."));
children.push(num("Il interroge le Modèle : `AvisModel::lieuExiste()` confirme que le lieu existe, puis `AvisModel::utilisateurADejaAvisSurLieu()` empêche les doublons."));
children.push(num("Si tout est valide, `AvisModel::creerPourLieu()` exécute un `INSERT INTO avis (...)` via une requête PDO préparée et retourne l'identifiant créé."));
children.push(num("Le contrôleur enregistre un message de succès avec `Session::flashSucces()` et redirige l'utilisateur vers la fiche du lieu (`/lieu?id=X`), où l'avis s'affiche."));
children.push(para(
  "À chaque étape de validation, en cas d'erreur, un message est stocké en session " +
  "(`Session::flashErreurs()`) et l'utilisateur est immédiatement redirigé vers la page d'origine, " +
  "sans qu'aucune écriture n'ait lieu en base."
));
children.push(figurePlaceholder("Figure III.4 — Diagramme de séquence : soumission d'un avis"));

// Saut de page avant la section VIII
children.push(new Paragraph({ children: [new PageBreak()] }));

// ============ SECTION VIII ============
children.push(H("VIII. Installation et déploiement", HeadingLevel.HEADING_1));
children.push(para(
  "Cette section décrit les prérequis logiciels et la procédure complète permettant d'installer " +
  "et de lancer l'application ABS sur un poste local."
));

// VIII.1 Prérequis
children.push(H("VIII.1 Prérequis", HeadingLevel.HEADING_2));
children.push(para("L'environnement de développement repose sur les outils suivants :"));
children.push(table(
  [{ w: 3000, label: "Composant" }, { w: 6360, label: "Détail" }],
  [
    ["Serveur web local", "MAMP, WAMP ou XAMPP (pile Apache + PHP + MySQL)."],
    ["Apache", "Module `mod_rewrite` activé (réécriture des URL vers le contrôleur frontal)."],
    ["PHP", "Version 8.1 ou supérieure, avec l'extension PDO MySQL."],
    ["MySQL", "Version 8.0 ou supérieure."],
    ["Node.js / npm", "Pour compiler les feuilles de style Tailwind CSS."],
    ["Git", "Pour cloner et versionner le dépôt."],
  ]
));

// VIII.2 Guide d'installation
children.push(H("VIII.2 Guide d'installation", HeadingLevel.HEADING_2));

children.push(P("1. Cloner le dépôt", { bold: true, after: 60 }));
children.push(code("git clone <url-du-depot> Projet-ABS"));

children.push(P("2. Configurer le serveur web", { bold: true, after: 60 }));
children.push(para(
  "Faire pointer le DocumentRoot du serveur sur le sous-dossier `public/` du projet (et non sur la " +
  "racine), afin que le code applicatif ne soit jamais exposé directement. Selon le serveur : MAMP " +
  "via Préférences → Serveur Web ; XAMPP en éditant `httpd.conf` ; WAMP via l'alias dans " +
  "`httpd-vhosts.conf`. Un fichier `.htaccess` à la racine sert de filet de sécurité en redirigeant " +
  "vers `public/`."
));

children.push(P("3. Créer la base de données et charger les données", { bold: true, after: 60 }));
children.push(para(
  "En ligne de commande, en forçant l'encodage UTF-8 (indispensable sous Windows pour préserver les " +
  "accents) :"
));
children.push(code("mysql --default-character-set=utf8mb4 -u root -p < sql/schema/database.sql"));
children.push(code("mysql --default-character-set=utf8mb4 -u root -p abs_db < sql/seeds/seed_avis_complet.sql"));
children.push(code("mysql --default-character-set=utf8mb4 -u root -p abs_db < sql/seeds/seed_monde_vivant.sql"));
children.push(para(
  "Le script `database.sql` crée la base `abs_db` et l'ensemble des tables et vues ; les fichiers de " +
  "`seeds` la remplissent avec un jeu de données de démonstration. L'import est également possible " +
  "via phpMyAdmin, dans le même ordre."
));

children.push(P("4. Configurer la connexion à la base", { bold: true, after: 60 }));
children.push(code("cp app/Config/bdd.exemple.php app/Config/bdd.php"));
children.push(para("Puis éditer `app/Config/bdd.php` pour y renseigner l'hôte, le nom de la base (`abs_db`), l'utilisateur et le mot de passe MySQL."));

children.push(P("5. Configurer la carte (Mapbox)", { bold: true, after: 60 }));
children.push(code("cp app/Config/mapbox.exemple.php app/Config/mapbox.php"));
children.push(para("Renseigner un jeton public Mapbox (ou la variable d'environnement `MAPBOX_TOKEN`) pour activer la carte interactive."));

children.push(P("6. Compiler les feuilles de style", { bold: true, after: 60 }));
children.push(para("Les sources CSS se trouvent dans `resources/css/` ; les fichiers servis sont générés dans `public/assets/css/`."));
children.push(code("npm install"));
children.push(code("npm run build:css"));
children.push(para("Pendant le travail sur le design, `npm run watch:css` recompile automatiquement à chaque modification."));

children.push(P("7. Lancer l'application", { bold: true, after: 60 }));
children.push(para("Avec MAMP/WAMP/XAMPP (Apache démarré), ouvrir l'URL correspondant au DocumentRoot configuré, par exemple :"));
children.push(code("http://localhost/Projet-ABS/public/"));
children.push(para("Sans serveur dédié, le serveur PHP intégré peut être utilisé depuis la racine du projet :"));
children.push(code("npm run start"));
children.push(para("L'application est alors accessible sur `http://localhost:8000/`. Les fichiers `bdd.php` et `mapbox.php` doivent exister au préalable (copiés depuis les exemples)."));

// VIII.3 Accès au site (côté visiteur)
children.push(H("VIII.3 Accès au site et utilisation", HeadingLevel.HEADING_2));
children.push(para(
  "Les étapes précédentes concernent l'installation du serveur. Du côté d'un visiteur, en revanche, " +
  "ABS étant une application web, aucune installation n'est nécessaire : il suffit d'ouvrir un " +
  "navigateur (Chrome, Firefox, Edge ou Safari) et de saisir l'adresse du site."
));
children.push(para(
  "En développement, cette adresse est une URL locale (par exemple `http://localhost:8000/`). Une fois " +
  "le site mis en ligne sur un hébergeur, l'adresse devient un nom de domaine public du type " +
  "`https://abs.exemple.com/`, que l'on peut partager avec n'importe quel utilisateur."
));
children.push(para(
  "Grâce à la conception « responsive » des pages (Tailwind CSS), la mise en page s'adapte " +
  "automatiquement à la taille de l'écran. Le site est donc utilisable aussi bien sur ordinateur que " +
  "sur tablette ou smartphone : la carte interactive, les menus (présentés en tiroir sur mobile) et " +
  "les formulaires restent lisibles et utilisables sur petit écran, sans application à installer."
));
children.push(para(
  "À titre d'exemple, un utilisateur qui souhaite publier un avis suit le parcours suivant :", { after: 90 }
));
children.push(num("Ouvrir l'adresse du site dans son navigateur (ordinateur ou mobile)."));
children.push(num("Créer un compte via la page d'inscription (`/inscription`), ou se connecter s'il en possède déjà un (`/connexion`)."));
children.push(num("Explorer la carte interactive (`/carte`) ou la page « Découvrir » (`/decouvrir`) pour trouver un lieu, puis ouvrir sa fiche (`/lieu?id=...`)."));
children.push(num("Remplir le formulaire d'avis présent sur la fiche : note de 1 à 5, titre et commentaire, puis valider."));
children.push(num("L'avis est enregistré et s'affiche immédiatement sur la fiche du lieu, visible par les autres visiteurs."));

// ---- Assemblage -------------------------------------------------------------
const doc = new Document({
  creator: "Equipe ABS",
  title: "Rapport ABS — Sections III et VIII",
  styles: {
    default: { document: { run: { font: "Arial", size: 22 } } },
    paragraphStyles: [
      { id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 30, bold: true, font: "Arial", color: "1F3864" },
        paragraph: { spacing: { before: 240, after: 200 }, outlineLevel: 0 } },
      { id: "Heading2", name: "Heading 2", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 25, bold: true, font: "Arial", color: "2E5496" },
        paragraph: { spacing: { before: 200, after: 120 }, outlineLevel: 1 } },
    ],
  },
  numbering: {
    config: [
      { reference: "puces", levels: [{ level: 0, format: LevelFormat.BULLET, text: "•",
        alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 540, hanging: 280 } } } }] },
      { reference: "etapes", levels: [{ level: 0, format: LevelFormat.DECIMAL, text: "%1.",
        alignment: AlignmentType.LEFT, style: { paragraph: { indent: { left: 540, hanging: 280 } } } }] },
    ],
  },
  sections: [{
    properties: {
      page: {
        size: { width: 12240, height: 15840 },
        margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 },
      },
    },
    footers: {
      default: new Footer({ children: [new Paragraph({
        alignment: AlignmentType.CENTER,
        children: [new TextRun({ text: "Projet ABS — ", size: 18 }),
          new TextRun({ text: "Page ", size: 18 }),
          new TextRun({ children: [PageNumber.CURRENT], size: 18 })],
      })] }),
    },
    children,
  }],
});

Packer.toBuffer(doc).then(buf => {
  const out = path.join(__dirname, "Rapport_ABS_Section_III_et_VIII.docx");
  fs.writeFileSync(out, buf);
  console.log("OK ->", out);
});
