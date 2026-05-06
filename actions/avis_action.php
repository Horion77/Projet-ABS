<?php
// Traitement formulaire avis
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
// Utilisateur connecté ou pas
if (!isLoggedIn()) {
    redirect('../pages/login.php');
}
// Vérifier que la requête est bien un POST (que le formulaire a bien ete envoyé)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php');
}
// On va récuperer les données du formulaire et les nettoyer avec la fonction de Basma (sanitize)
$place_id = intval($_POST['place_id']);
$rating   = intval($_POST['rating']);
$title    = sanitize($_POST['title'] ?? '');
$comment  = sanitize($_POST['comment'] ?? '');
$user_id  = $_SESSION['user_id'];

// Valider les données
// là je crée un tableau errors pour stocker les erreurs et rediriger vers la page du lieu
$errors = [];

if ($place_id <= 0) {
    $errors[] = "Lieu invalide.";
}
if ($rating < 1 || $rating > 5) {
    $errors[] = "La note doit être entre 1 et 5.";
}
if (empty($comment)) {
    $errors[] = "Le commentaire est obligatoire.";
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    redirect('../pages/place.php?id=' . $place_id);
}

// Enregistre l'avis dans la base de données
// les : c pour la sécurité et la fonction prepare c pour prepaper à mettre dans la BDD
$stmt = $pdo->prepare("INSERT INTO reviews (user_id, place_id, rating, title, comment) 
                        VALUES (:user_id, :place_id, :rating, :title, :comment)");

$stmt->execute([
    ':user_id'  => $user_id,
    ':place_id' => $place_id,
    ':rating'   => $rating,
    ':title'    => $title,
    ':comment'  => $comment
]);

// On renvoie à la page du lieu en disant que ça a bien été enregistré
$_SESSION['success'] = "Votre avis a bien été enregistré !";
redirect('../pages/place.php?id=' . $place_id);