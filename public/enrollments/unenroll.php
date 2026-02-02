<?php
// ============================================
// FILE: public/enrollments/unenroll.php
// ============================================
?>
<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Get enrollment ID
$enrollmentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($enrollmentId <= 0) {
    setMessage("Invalid enrollment ID", "error");
    redirect('list.php');
}

try {
    // Get enrollment details for message
    $enrollment = getOne($pdo, 
        "SELECT e.*, CONCAT(s.first_name, ' ', s.last_name) as student_name, c.course_name
         FROM enrollments e
         JOIN students s ON e.student_id = s.student_id
         JOIN courses c ON e.course_id = c.course_id
         WHERE e.enrollment_id = ?", 
        [$enrollmentId]
    );
    
    if (!$enrollment) {
        setMessage("Enrollment not found", "error");
        redirect('list.php');
    }
    
    // Update status to 'Dropped' instead of deleting
    // OR you can delete: DELETE FROM enrollments WHERE enrollment_id = ?
    $sql = "UPDATE enrollments SET status = 'Dropped' WHERE enrollment_id = ?";
    $affectedRows = execute($pdo, $sql, [$enrollmentId]);
    
    if ($affectedRows > 0) {
        setMessage("{$enrollment['student_name']} has been unenrolled from {$enrollment['course_name']}");
    } else {
        setMessage("Unable to unenroll student", "error");
    }
    
} catch (PDOException $e) {
    error_log("Unenroll Error: " . $e->getMessage());
    setMessage("Error unenrolling student. Please try again.", "error");
}

redirect('list.php');
?>