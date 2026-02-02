<?php
require_once '../config/db.php';

header('Content-Type: application/json');

$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($courseId <= 0) {
    echo json_encode(['error' => 'Invalid course ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            c.max_students,
            c.course_name,
            COUNT(e.enrollment_id) as enrolled_students,
            (c.max_students - COUNT(e.enrollment_id)) as available_slots
        FROM courses c
        LEFT JOIN enrollments e ON c.course_id = e.course_id AND e.status = 'Active'
        WHERE c.course_id = ?
        GROUP BY c.course_id
    ");
    $stmt->execute([$courseId]);
    $result = $stmt->fetch();
    
    if ($result) {
        echo json_encode([
            'course_name' => $result['course_name'],
            'max_students' => (int)$result['max_students'],
            'enrolled_students' => (int)$result['enrolled_students'],
            'available_slots' => (int)$result['available_slots'],
            'is_full' => $result['available_slots'] <= 0
        ]);
    } else {
        echo json_encode(['error' => 'Course not found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>