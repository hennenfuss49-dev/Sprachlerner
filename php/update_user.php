<?php
// filepath: c:\Schule\Schule\swp\3.Klasse web\XaMMP\htdocs\Sprachlerner1\php\update_user.php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false, 'message'=>'Nicht eingeloggt']);
    exit;
}

$user_id = $_SESSION['user_id'];
$new_username = trim($_POST['username'] ?? '');

if ($new_username === '') {
    echo json_encode(['success'=>false, 'message'=>'Benutzername darf nicht leer sein.']);
    exit;
}

// Prüfe, ob der Name schon vergeben ist (optional)
$stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = ? AND user_id != ?");
$stmt->execute([$new_username, $user_id]);
if ($stmt->fetch()) {
    echo json_encode(['success'=>false, 'message'=>'Benutzername bereits vergeben.']);
    exit;
}

// Update
$stmt = $pdo->prepare("UPDATE Users SET username = ? WHERE user_id = ?");
$stmt->execute([$new_username, $user_id]);
$_SESSION['username'] = $new_username;

echo json_encode(['success'=>true, 'username'=>$new_username]);