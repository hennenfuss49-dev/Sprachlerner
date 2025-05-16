<?php
// Datenbankverbindung
$host = 'localhost';
$dbname = 'auth_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Fehler bei der Verbindung zur Datenbank: " . $e->getMessage());
}

// Authentifizierungshandling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if ($action === 'login') {
        // Login-Logik
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            echo "Erfolgreich eingeloggt. Willkommen, " . htmlspecialchars($user['email']) . "!";
        } else {
            echo "Ungültige E-Mail oder Passwort.";
        }
    } elseif ($action === 'register') {
        // Registrierungs-Logik
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);

        if ($stmt->rowCount() > 0) {
            echo "Diese E-Mail ist bereits registriert.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (:email, :password)");
            $stmt->execute(['email' => $email, 'password' => $hashedPassword]);
            echo "Registrierung erfolgreich. Sie können sich jetzt einloggen.";
        }
    }
}
?>