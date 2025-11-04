<?php
require_once 'Database.php';

try {
    $pdo = Database::connect('Axel', 'zaza123');

    // ✅ Afficher "Connexion réussie" uniquement si on ouvre directement init_DB.php
    if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
        echo "Connexion initiale réussie !";
    }

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
