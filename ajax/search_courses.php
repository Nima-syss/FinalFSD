<?php
require_once '../config/db.php';

header('Content-Type: application/json');

$keyword = $_GET['keyword'] ?? '';

if (strlen($keyword) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT course_id, course_name, course_code, category, level
        FROM courses
        WHERE course_name LIKE ? OR course_code LIKE ?
        ORDER BY course_name
        LIMIT 10
    ");
    
    $searchTerm = '%' . $keyword . '%';
    $stmt->execute([$searchTerm, $searchTerm]);
    $results = $stmt->fetchAll();
    
    echo json_encode($results);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>