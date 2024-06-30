<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
</head>
<body>

<?php 
require_once __DIR__ . '/Database/database.php';
global $db;
?>
    
<form method="POST">

    <?php 
    include __DIR__ . '/Includes/traitement_inscription.php'; 
    ?>

    <label for="pseudo">Votre pseudo :*</label>
    <input type="text" id="pseudo" name="pseudo" placeholder="Entrez votre pseudo" required><br>

    <label for="email">Votre email :*</label>
    <input type="email" id="email" name="email" placeholder="Entrez votre email" required><br>

    <label for="mdp">Votre mot de passe :*</label>
    <input type="password" id="mdp" name="mdp" 
           placeholder="Entrez votre mot de passe" 
           required 
           pattern="^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,}$" 
           title="Votre mot de passe doit contenir au moins 8 caractères, une lettre majuscule, une lettre minuscule, un chiffre et un caractère spécial."><br>

    <label for="cmdp">Confirmation de votre mot de passe :*</label>
    <input type="password" id="cmdp" name="cmdp" placeholder="Confirmez votre mot de passe" required><br>

    <label for="tel">Votre numéro de téléphone :*</label>
    <input type="tel" id="tel" name="tel" 
           placeholder="Entrez votre numéro de téléphone" 
           required 
           pattern="\d{10}" 
           maxlength="10" 
           title="Votre numéro de téléphone doit contenir exactement 10 chiffres."><br>

    <input type="submit" value="M'inscrire" name="ok_inscription">
</form>

</body>
</html>
