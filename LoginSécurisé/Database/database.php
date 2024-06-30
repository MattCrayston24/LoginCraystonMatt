<?php
$dotenvPath = 'C:\wamp64\www\.env';
$dotenv = parse_ini_file($dotenvPath);

if ($dotenv === false) {
    die("Erreur lors du chargement du fichier .env");
}

// On récupère les variables nécéssaires

$host = $dotenv['DB_HOST'] ?? 'localhost';  
$dbName = $dotenv['DB_NAME'] ?? 'test';     
$user = $dotenv['DB_USER'] ?? 'root';       
$pass = $dotenv['DB_PASS'] ?? '';           

$max_login_attempts = intval($dotenv['MAX_LOGIN_ATTEMPTS'] ?? 3);
$lockout_duration = intval($dotenv['LOCKOUT_DURATION'] ?? 30);

$admin_email = $dotenv['ADMIN_EMAIL'] ?? '';

// Connexion à la base de données MySQL avec PDO
try {
    $db = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Échec de la connexion à la base de données : ' . $e->getMessage());
}

// Fonction qui récupére les informations d'un utilisateur à partir de son email
function getUserByEmail($email) {
    global $db;
    $q = $db->prepare("
        SELECT utilisateurs.id, utilisateurs.email, mot_de_passe.mot_de_passe
        FROM utilisateurs 
        JOIN mot_de_passe ON utilisateurs.id = mot_de_passe.id_user 
        WHERE utilisateurs.email = ?
    ");
    $q->execute([$email]);
    return $q->fetch(PDO::FETCH_ASSOC);
}

// Dans cette fonction on vérifie si l'email entré existe dans la base de données des utilisateurs
function emailExists($email) {
    global $db;
    $stmt = $db->prepare("SELECT email FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->rowCount() > 0;
}

// Ajout d'un nouvel utilisateur avec son pseudo et son email
function addUser($pseudo, $email) {
    global $db;
    $stmt = $db->prepare("INSERT INTO utilisateurs (pseudo, email) VALUES (?, ?)");
    $stmt->execute([$pseudo, $email]);
    return $db->lastInsertId();
}

// Ajout également et séparemment du mot de passe de l'utilisateur
function addPassword($userId, $hashmdp) {
    global $db;
    $stmt = $db->prepare("INSERT INTO mot_de_passe (id_user, mot_de_passe) VALUES (?, ?)");
    $stmt->execute([$userId, $hashmdp]);
}

// On ajoute un numéro de téléphone de l'utilisateur
function addPhoneNumber($userId, $tel) {
    global $db;
    $stmt = $db->prepare("INSERT INTO tel (id_user, tel) VALUES (?, ?)");
    $stmt->execute([$userId, $tel]);
}

// On incrémente le nombre de tentatives de connexion pour la sécurité
function incrementLoginAttempts($email) {
    global $db;

    try {
        $q = $db->prepare("UPDATE utilisateurs SET login_attempts = login_attempts + 1, last_attempt_time = NOW() WHERE email = ?");
        $q->execute([$email]);
    } catch (PDOException $e) {
        echo "Erreur lors de l'incrémentation des tentatives de connexion : " . $e->getMessage();
        exit();
    }
}

// Réinitialise les tentatives de connexion pour l'email en question
function resetLoginAttempts($email) {
    global $db;

    try {
        $q = $db->prepare("UPDATE utilisateurs SET login_attempts = 0, last_attempt_time = NULL WHERE email = ?");
        $q->execute([$email]);
    } catch (PDOException $e) {
        echo "Erreur lors de la réinitialisation des tentatives de connexion : " . $e->getMessage();
        exit();
    }
}

// Vérifie si un utilisateur est bloqué en raison de tentatives de connexion infructueuses
function isUserLockedOut($email) {
    global $db, $max_login_attempts, $lockout_duration;

    try {
        $q = $db->prepare("SELECT login_attempts, last_attempt_time FROM utilisateurs WHERE email = ?");
        $q->execute([$email]);
        $result = $q->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['login_attempts'] >= $max_login_attempts) {
            if ($result['last_attempt_time']) {
                $current_time = time();
                $last_attempt_time = strtotime($result['last_attempt_time']);
                if (($current_time - $last_attempt_time) < $lockout_duration) {
                    return true; // L'utilisateur est verrouillé
                } else {
                    resetLoginAttempts($email); // Réinitialiser les tentatives si le temps de verrouillage est écoulé
                }
            }
        }
    } catch (PDOException $e) {
        echo "Erreur lors de la vérification du verrouillage de l'utilisateur : " . $e->getMessage();
        exit();
    }

    return false; // L'utilisateur n'est pas verrouillé
}

// Fonction pour récupérer le nombre de tentatives de connexion
function getLoginAttempts($email) {
    global $db;

    try {
        $q = $db->prepare("SELECT login_attempts FROM utilisateurs WHERE email = ?");
        $q->execute([$email]);
        $result = $q->fetch(PDO::FETCH_ASSOC);

        if ($result && isset($result['login_attempts'])) {
            return intval($result['login_attempts']); 
        } else {
            return 0; 
        }
    } catch (PDOException $e) {
        echo "Erreur lors de la récupération des tentatives de connexion : " . $e->getMessage();
        exit();
    }
}

// Fonction pour changer le mot de passe dans la base de données en sécurité 
function updatePassword($userId, $newPassword) {
    global $db;
    try {
        $stmt = $db->prepare("UPDATE mot_de_passe SET mot_de_passe = ? WHERE id_user = ?");
        $stmt->execute([$newPassword, $userId]);
    } catch (PDOException $e) {
        echo "Erreur lors de la mise à jour du mot de passe : " . $e->getMessage();
        exit();
    }
}

// Attribue un rôle à un utilisateur en fonction de son email
function assignRole($userId, $email) {
    global $db, $admin_email;

    // Par défaut, tout le monde est "utilisateur"
    $role = 'utilisateur';

    //L'email "admin_email" correspond à l'email de l'admin
    if ($email === $admin_email) {
        $role = 'admin';
    }

    try {
        // On vérifie si le rôle existe déjà pour l'utilisateur
        $stmt = $db->prepare("SELECT * FROM user_roles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $existing_role = $stmt->fetch();

        if (!$existing_role) {
            // Si le rôle n'existe pas, on l'ajoute
            $stmt = $db->prepare("INSERT INTO user_roles (user_id, role) VALUES (?, ?)");
            $stmt->execute([$userId, $role]);
        } else {
            // on met aussi à jour le rôle si nécessaire
            $stmt = $db->prepare("UPDATE user_roles SET role = ? WHERE user_id = ?");
            $stmt->execute([$role, $userId]);
        }
    } catch (PDOException $e) {
        echo "Erreur lors de l'attribution du rôle : " . $e->getMessage();
        exit();
    }
}

?>
