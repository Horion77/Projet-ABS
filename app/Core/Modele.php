<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Classe de base abstraite pour tous les modèles de l'application.
 *
 * Tous les modèles sont statiques (pas d'instance) : on appelle
 * LieuModel::trouver($id) directement sans new LieuModel().
 * Cette approche convient à un projet de taille modeste où l'injection
 * de dépendances n'est pas requise.
 */
abstract class Modele
{
    /**
     * Retourne la connexion PDO partagée (singleton de BaseDeDonnees).
     * final empêche les sous-classes de surcharger cette méthode et
     * d'utiliser une connexion différente — garantit une seule source de vérité.
     * protected : accessible uniquement depuis Modele et ses enfants, jamais depuis l'extérieur.
     */
    final protected static function pdo(): PDO
    {
        return BaseDeDonnees::pdo();
    }
}
