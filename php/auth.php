<?php
session_start();
require 'db.php';

$action = $_POST['action'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$username = $_POST['username'] ?? '';

// Registrierung
if ($action === 'register') {
    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['success'=>false, 'message'=>'Alle Felder ausfüllen!']);
        exit;
    }
    // E-Mail bereits vergeben?
    $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success'=>false, 'message'=>'E-Mail existiert bereits!']);
        exit;
    }
    // Nutzername bereits vergeben?
    $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode(['success'=>false, 'message'=>'Nutzername existiert bereits!']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO Users (username, email, password_hash) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $password_hash]);
    echo json_encode(['success'=>true, 'message'=>'Registrierung erfolgreich!']);
    exit;
}

// Login
if ($action === 'login') {
    $stmt = $pdo->prepare("SELECT user_id, username, password_hash FROM Users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && $password == $user['password_hash']) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        echo json_encode(['success'=>true, 'username'=>$user['username']]);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Login fehlgeschlagen!']);
    }
    exit;
}

// Logout
if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success'=>true]);
    exit;
}

// Abfrage ob eingeloggt
if ($action === 'check') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode(['success'=>true, 'username'=>$_SESSION['username']]);
    } else {
        echo json_encode(['success'=>false]);
    }
    exit;
}
?>  