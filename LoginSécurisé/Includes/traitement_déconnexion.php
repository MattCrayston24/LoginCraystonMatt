<?php
session_start();

// On détruit toutes les variables de session
$_SESSION = array();

// On éfface égamlement le cookie de session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Pour finir, on détruit la session
session_destroy();

// On redirige quand même l'utilisateur vers la page de connexion (malgrè qu'il est décidé de se déco)
header('Location: ../connexion.php');
exit();
?>
