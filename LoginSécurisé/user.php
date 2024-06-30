<?php
session_start();

$session_duration = 1800;

// On vérifie si l'utilisateur est connecté en vérifiant la session
if (isset($_SESSION['email'])) {
    // On vérifie si la session a expiré
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $session_duration)) {
        // Si oui, on détruit la session
        session_unset();
        session_destroy();

        // Et on redirige l'utilisateur sur la page de connexion
        header('Location: connexion.php');
        exit();
    } else {
        // Si non, on met à jour l'heure de la session
        $_SESSION['login_time'] = time();
        // Récupérer l'email de l'utilisateur connecté depuis la session
        $emailuser = htmlspecialchars($_SESSION['email']);
    }
} else {
    // Si l'utilisateur n'est pas connecté, redirigez-le vers la page de connexion
    header('Location: connexion.php');
    exit();
}

if (isset($_SESSION['error_message'])) {
    echo '<p>' . htmlspecialchars($_SESSION['error_message']) . '</p>';
    unset($_SESSION['error_message']);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord utilisateur</title>
</head>
<body>

    <p>Bienvenue sur votre session : <?php echo htmlspecialchars($emailuser); ?></p>

    <h2>Changer votre mot de passe :</h2>
    <form action="Includes/traitement_changementmdp.php" method="post">
        <label for="ancien_mdp">Ancien mot de passe :</label>
        <input type="password" id="ancien_mdp" name="ancien_mdp" required><br>

        <label for="nouveau_mdp">Nouveau mot de passe :</label>
        <input type="password" id="nouveau_mdp" name="nouveau_mdp" required><br>

        <label for="confirm_mdp">Confirmez le nouveau mot de passe :</label>
        <input type="password" id="confirm_mdp" name="confirm_mdp" required><br>

        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="submit" value="Changer le mot de passe"><br>
    </form>

    <h2>Se déconnecter :</h2>
    <form action="Includes/traitement_déconnexion.php" method="post">
        <input type="submit" value="Déconnexion">
    </form>

</body>
</html>
