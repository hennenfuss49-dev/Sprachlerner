<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt']);
    exit;
}
$user_id = $_SESSION['user_id'];
$achievements = [];
// 1. Registrierung immer erreicht
$achievements[] = [
    'icon' => 'fa-star',
    'title' => 'Erste Schritte',
    'desc' => 'Du hast dich erfolgreich registriert!',
    'achieved' => true
];
// 2. 3 Tage in Folge gelernt
$stmt = $pdo->prepare("SELECT last_practiced FROM UserUnitProgress WHERE user_id = ? AND last_practiced IS NOT NULL ORDER BY last_practiced DESC LIMIT 3");
$stmt->execute([$user_id]);
$dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
$streak = false;
if (count($dates) >= 3) {
    $d1 = new DateTime($dates[0]);
    $d2 = new DateTime($dates[1]);
    $d3 = new DateTime($dates[2]);
    $streak = (
        $d1->diff($d2)->days === 1 &&
        $d2->diff($d3)->days === 1
    );
}
$achievements[] = [
    'icon' => 'fa-fire',
    'title' => '3 Tage in Folge',
    'desc' => 'Du hast 3 Tage hintereinander gelernt.',
    'achieved' => $streak
];
// 3. Level 5 in einer Unit
$stmt = $pdo->prepare("SELECT COUNT(*) FROM UserUnitProgress WHERE user_id = ? AND progress_level >= 5");
$stmt->execute([$user_id]);
$hasLevel5 = $stmt->fetchColumn() > 0;
$achievements[] = [
    'icon' => 'fa-trophy',
    'title' => 'Level 5 erreicht',
    'desc' => 'Du hast Level 5 in einer Unit erreicht.',
    'achieved' => $hasLevel5
];
// 4. Alle Units abgeschlossen (Level 4 oder höher in allen Units)
$stmt = $pdo->query("SELECT COUNT(*) FROM Units");
$totalUnits = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM UserUnitProgress WHERE user_id = ? AND progress_level >= 4");
$stmt->execute([$user_id]);
$completedUnits = $stmt->fetchColumn();
$allDone = ($totalUnits > 0 && $completedUnits == $totalUnits);
$achievements[] = [
    'icon' => 'fa-crown',
    'title' => 'Alle Units abgeschlossen',
    'desc' => 'Du hast alle Units abgeschlossen!',
    'achieved' => $allDone
];
echo json_encode(['success'=>true, 'achievements'=>$achievements]);
