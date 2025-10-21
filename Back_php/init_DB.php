<?php
require 'Database.php';

try {
    // Chaque copain entre ses identifiants une seule fois
    $pdo = Database::connect('Axel', 'zaza123'); 
    echo "Connexion initiale réussie !";
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
