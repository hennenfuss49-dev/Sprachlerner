<?php
session_start();
require_once 'db.php';

// Für Testzwecke: Wenn kein User eingeloggt ist, nimm user_id = 1
$user_id = $_SESSION['user_id'] ?? 1;

// Hole alle Units
$stmt = $pdo->query("
    SELECT 
        u.unit_id,
        u.unit_name,
        u.description
    FROM Units u
    ORDER BY u.unit_id ASC
");

$units = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $progress_level = 0;
    $last_practiced = null;
    $progress_percent = 0;

    // Hole Fortschritt für den User (auch Testuser)
    $progressStmt = $pdo->prepare("SELECT progress_level, last_practiced FROM UserUnitProgress WHERE user_id = ? AND unit_id = ?");
    $progressStmt->execute([$user_id, $row['unit_id']]);
    $progress = $progressStmt->fetch(PDO::FETCH_ASSOC);
    if ($progress) {
        $progress_level = (int)$progress['progress_level'];
        $last_practiced = $progress['last_practiced'];
        $progress_percent = min($progress_level * 25, 100); // z.B. 4 Level = 100%
    }

    $units[] = [
        'unit_id' => $row['unit_id'],
        'unit_name' => $row['unit_name'],
        'description' => $row['description'],
        'progress_level' => $progress_level,
        'last_practiced' => $last_practiced,
        'progress_percent' => $progress_percent
    ];
}

echo json_encode([
    'success' => true,
    'units' => $units
]);
?>