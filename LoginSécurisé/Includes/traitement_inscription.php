<?php

require_once __DIR__ . '/../Database/database.php';

// Vérification de la soumission du formulaire d'inscription
if (isset($_POST['ok_inscription'])) {
    $pseudo = trim($_POST['pseudo']);
    $email = trim($_POST['email']);
    $mdp = $_POST['mdp'];
    $cmdp = $_POST['cmdp'];
    $tel = preg_replace('/\D/', '', $_POST['tel']); 

    // Vérification que tous les champs sont remplis
    if (!empty($pseudo) && !empty($email) && !empty($mdp) && !empty($cmdp) && !empty($tel)) {
        // Vérification du format de l'email
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Vérification que les mots de passe correspondent
            if ($mdp === $cmdp) {
                // Vérification de la complexité du mot de passe
                $mdpPattern = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,}$/";
                if (preg_match($mdpPattern, $mdp)) {
                    // Hashage du mot de passe
                    $options = [
                        'cost' => 12,
                    ];
                    $hashmdp = password_hash($mdp, PASSWORD_BCRYPT, $options);

                    try {
                        // Vérification (basique mais nécessaire) de si l'email existe déjà dans la base de données
                        if (!emailExists($email)) {
                            $userId = addUser($pseudo, $email);
                            addPassword($userId, $hashmdp);
                            addPhoneNumber($userId, $tel);

                            echo "Le compte a été créé avec succès.";
                        } else {
                            echo "Email déjà existant !";
                        }
                    } catch (PDOException $e) {
                        echo "Erreur de base de données : " . $e->getMessage();
                    }
                } else {
                    echo "Le mot de passe doit contenir au moins 8 caractères, une lettre majuscule, une lettre minuscule, un chiffre et un caractère spécial.";
                }
            } else {
                echo "Les mots de passe ne correspondent pas.";
            }
        } else {
            echo "L'email n'est pas valide.";
        }
    } else {
        echo "Les champs ne sont pas tous remplis.";
    }
} else {
    echo "Il y a un problème avec l'inscription.";
}
?>
