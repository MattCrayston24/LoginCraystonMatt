<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
</head>
<body>

<?php 

require_once __DIR__ . '/Includes/Attempts/attempts_connexion.php';
require_once __DIR__ . '/Database/database.php';

// On démarre (au cas où) la session si elle n'est pas déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// On vérifie si l'utilisateur est verrouillé
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$afficherFormulaire = true; // Variable (bool donc true ou false que l'on met sur true d'office) pour contrôler l'affichage du formulaire

if ($email && isUserLockedOut($email)) {
    $afficherFormulaire = false; // Ne pas afficher le formulaire si l'utilisateur est verrouillé
    echo "Votre compte est actuellement verrouillé pendant 60 secondes.";
}

?>

<?php include 'Includes/traitement_connexion.php'; ?>

<?php if ($afficherFormulaire) : ?>
    <form method="POST">
        <label for="pseudo">Votre email :*</label>
        <input type="email" name="email" id="email" placeholder="Votre Email" required><br/>
        <label for="pseudo">Votre mot de passe :*</label>
        <input type="password" name="mdp" id="mdp" placeholder="Votre mot de passe" required><br/>
        <input type="submit" value="Se connecter" name="ok_connexion">
    </form>
<?php endif; ?>

</body>
</html>