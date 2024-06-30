<?php

require_once __DIR__ . '/../Database/database.php';
require_once __DIR__ . '/Attempts/attempts_connexion.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Fonction pour réinitialiser les tentatives de connexion et déverrouiller l'utilisateur
function unlockUser($email) {
    resetLoginAttempts($email);
    unset($_SESSION['user_locked']);
    unset($_SESSION['lockout_end']);
    unset($_SESSION['locked_email']);
    echo "Votre compte a été déverrouillé. Vous pouvez réessayer de vous connecter.";
    header('Refresh: 1'); 
    exit();
}

// On viens vérifier si la période de verrouillage est écoulée
if (isset($_SESSION['lockout_end']) && time() > $_SESSION['lockout_end']) {
    $email = $_SESSION['locked_email'];
    unlockUser($email);
}

// On vérifie si la requête de connexion est envoyée
if (isset($_POST['ok_connexion'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $mdp = $_POST['mdp'];

    if (!empty($email) && !empty($mdp)) {
        // Vérification également si l'utilisateur est verrouillé (au cas où il tente de spammer)
        if (isset($_SESSION['user_locked']) && $_SESSION['user_locked'] === true) {
            echo "Votre compte est encore verrouillé. Veuillez patienter.";
            exit();
        }

        $result = getUserByEmail($email);

        if ($result) {
            // Vérification du mot de passe (s'il correspond)
            if (isset($result['mot_de_passe']) && password_verify($mdp, $result['mot_de_passe'])) {
                // On Réinitialise les tentatives de connexion échouées
                resetLoginAttempts($email);
                unset($_SESSION['lockout_end']);
                unset($_SESSION['user_locked']);
                unset($_SESSION['locked_email']);

                // Création des tokens de session
                $session_token = bin2hex(random_bytes(32)); 
                $csrf_token = bin2hex(random_bytes(32));

                $_SESSION['email'] = $result['email'];
                $_SESSION['user_id'] = $result['id'];
                $_SESSION['session_token'] = $session_token;
                $_SESSION['csrf_token'] = $csrf_token;
                $_SESSION['login_time'] = time();

                // Déterminer si 'https' est utilisé
                $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

                // Définition des cookies avec condition pour HTTPS
                setcookie('session_token', $session_token, [
                    'expires' => time() + 1800, // 1/2 heure (pour l'exemple mais on peut choisir le temps qu'on souhaite)
                    'path' => '/',
                    'secure' => $is_https, // Si HTTPS
                    'httponly' => true, 
                    'samesite' => 'Strict', 
                ]);

                setcookie('csrf_token', $csrf_token, [
                    'expires' => time() + 1800, // 1/2 heure (pour l'exemple mais on peut choisir le temps qu'on souhaite également)
                    'path' => '/',
                    'secure' => $is_https, // Si HTTPS
                    'httponly' => true, 
                    'samesite' => 'Strict', 
                ]);

                // Assigner le rôle à l'utilisateur
                assignRole($result['id'], $email);

                // Redirection vers la page utilisateur
                header('Location: user.php');
                exit();
            } else {
                // On incrémente le nombre de tentatives de connexion échouées en appelant la fonction incrementLoginAttempts()
                incrementLoginAttempts($email);
                $attempts_left = $max_login_attempts - getLoginAttempts($email);

                // Vérifier si l'utilisateur doit être verrouillé
                if (isUserLockedOut($email)) {
                    $_SESSION['user_locked'] = true;
                    $_SESSION['lockout_end'] = time() + $lockout_duration;
                    $_SESSION['locked_email'] = $email;

                    // On réinitialise immédiatement les tentatives de connexion
                    resetLoginAttempts($email);

                    echo "Rafraîchissez la page quand le délai est terminé 
                    (toute tentative durant ce délai est inutile car votre compte est bloqué).";
                    exit();
                }

                echo "Mot de passe incorrect. Tentatives restantes : $attempts_left.";
            }
        } else {
            echo "Le compte portant l'email : " . htmlspecialchars($email) . " n'existe pas.";
        }
    } else {
        echo "Veuillez compléter l'ensemble des champs.";
    }
}
?>
