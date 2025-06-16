<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt']);
    exit;
}
$user_id = $_SESSION['user_id'];
// Profilbild speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_avatar']) && isset($_POST['avatar'])) {
    $avatar = $_POST['avatar'];
    $stmt = $pdo->prepare("UPDATE Users SET avatar = ? WHERE user_id = ?");
    $stmt->execute([$avatar, $user_id]);
    $_SESSION['avatar'] = $avatar;
    echo json_encode(['success'=>true]);
    exit;
}
$stmt = $pdo->prepare("SELECT username, email, avatar FROM Users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User nicht gefunden']);
    exit;
}
$stmt = $pdo->prepare("SELECT u.unit_name, u.description, COALESCE(uup.progress_level, 0) as progress_level, COALESCE(uup.last_practiced, '-') as last_practiced FROM Units u LEFT JOIN UserUnitProgress uup ON u.unit_id = uup.unit_id AND uup.user_id = ? ORDER BY u.unit_id ASC");
$stmt->execute([$user_id]);
$units = $stmt->fetchAll();
$response = [
    'success' => true,
    'username' => $user['username'],
    'email' => $user['email'],
    'avatar' => $user['avatar'] ?? null,
    'units' => $units
];
echo json_encode($response);
