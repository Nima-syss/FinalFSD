
<?php
// ============================================
// FILE: public/courses/delete.php
// ============================================
?>
<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
checkAuth();

// Block students from deleting courses
if (isStudentRole()) {
    setMessage("Access denied. Students cannot delete courses.", "error");
    redirect('list.php');
}

// Get course ID
$courseId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($courseId <= 0) {
    setMessage("Invalid course ID", "error");
    redirect('list.php');
}

try {
    // Get course name for message
    $course = getOne($pdo, "SELECT course_name FROM courses WHERE course_id = ?", [$courseId]);
    
    if (!$course) {
        setMessage("Course not found", "error");
        redirect('list.php');
    }
    
    // Delete course (cascade will handle enrollments)
    $sql = "DELETE FROM courses WHERE course_id = ?";
    $affectedRows = execute($pdo, $sql, [$courseId]);
    
    if ($affectedRows > 0) {
        setMessage("Course '{$course['course_name']}' deleted successfully!");
    } else {
        setMessage("Unable to delete course", "error");
    }
    
} catch (PDOException $e) {
    error_log("Delete Course Error: " . $e->getMessage());
    setMessage("Error deleting course. Please try again.", "error");
}

redirect('list.php');