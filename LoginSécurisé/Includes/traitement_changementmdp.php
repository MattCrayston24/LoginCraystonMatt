<?php
require_once __DIR__ . '/../Database/database.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// On vérifie d'abord si l'utilisateur est connecté
if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];

    // Ensuite on vérifie si la requête POST a été envoyée
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // On vérifie le token CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('Erreur de validation CSRF');
        }

        $ancien_mdp = $_POST['ancien_mdp'];
        $nouveau_mdp = $_POST['nouveau_mdp'];
        $confirm_mdp = $_POST['confirm_mdp'];

        if (empty($ancien_mdp) || empty($nouveau_mdp) || empty($confirm_mdp)) {
            die("Tous les champs sont obligatoires.");
        }

        // Vérification de la force du nouveau mot de passe (pareil que dans l'inscription pour la sécurité du mdp de l'utilisateur)
        if (strlen($nouveau_mdp) < 8 || !preg_match('/[A-Z]/', $nouveau_mdp) || !preg_match('/[a-z]/', $nouveau_mdp) || !preg_match('/\d/', $nouveau_mdp)) {
            die("Le nouveau mot de passe doit comporter au moins 8 caractères, incluant une majuscule, une minuscule et un chiffre.");
        }

        $result = getUserByEmail($email);

        if ($result) {
            // On vérifie si l'ancien mot de passe est bien cohérent
            if (password_verify($ancien_mdp, $result['mot_de_passe'])) {
                // Vérification que le nouveau mdp et l'ancien mdp correspondent
                if ($nouveau_mdp === $confirm_mdp) {
                    $nouveau_mdp_hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);
                    updatePassword($result['id'], $nouveau_mdp_hash);

                    echo "Votre mot de passe a été changé avec succès.";

                    session_destroy();

                    header('Location: ../connexion.php');
                    exit();
                } else {
                    echo "Le nouveau mot de passe et la confirmation ne correspondent pas.";
                }
            } else {
                $_SESSION['error_message'] = "L'ancien mot de passe est incorrect.";
                
                header('Location: ../user.php');
                exit();
            }
        } else {
            echo "Utilisateur non trouvé.";
        }
    } else {
        echo "Méthode de requête non valide.";
    }
} else {
    // On redirige vers la page de connexion si l'utilisateur n'est pas connecté (au cas où)
    header('Location: ../connexion.php');
    exit();
}
?>
