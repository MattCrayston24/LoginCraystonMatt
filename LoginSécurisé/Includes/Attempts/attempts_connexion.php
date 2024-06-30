<?php

require_once __DIR__ . '/../../Database/database.php';

$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if ($email) {
    // Vérifier si l'utilisateur est verrouillé
    if (isUserLockedOut($email)) {
        echo "L'utilisateur avec l'email $email est verrouillé.";
    } else {
        // +1 sur les tentatives de connexion
        incrementLoginAttempts($email);
    }
} else {
    echo "Aucun email fourni.";
}
?>
