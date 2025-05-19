<?php
// KEINE Session nötig, alle Units werden für jeden angezeigt (ohne User-Fortschritt)
$pdo = new PDO('mysql:host=localhost;dbname=sprachlerner;charset=utf8', 'root', 'root');

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
    $units[] = [
        'unit_id' => $row['unit_id'],
        'unit_name' => $row['unit_name'],
        'description' => $row['description'],
        'progress_level' => 0,
        'last_practiced' => null,
        'progress_percent' => 0
    ];
}

echo json_encode([
    'success' => true,
    'units' => $units
]);
?>