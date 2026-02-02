
<?php
session_start();
// ============================================
// FILE 4: public/students/delete.php
// ============================================
?>
<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
checkAuth();

// Block students from deleting other students
if (isStudentRole()) {
    setMessage("Access denied. Students cannot delete other students.", "error");
    redirect("list.php");
}

// Get student ID
$studentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($studentId <= 0) {
    setMessage("Invalid student ID", "error");
    redirect('list.php');
}

try {
    // Get student name for message
    $student = getOne($pdo, "SELECT first_name, last_name FROM students WHERE student_id = ?", [$studentId]);
    
    if (!$student) {
        setMessage("Student not found", "error");
        redirect('list.php');
    }
    
    // Delete student (cascade will handle enrollments)
    $sql = "DELETE FROM students WHERE student_id = ?";
    $affectedRows = execute($pdo, $sql, [$studentId]);
    
    if ($affectedRows > 0) {
        setMessage("Student '{$student['first_name']} {$student['last_name']}' deleted successfully!");
    } else {
        setMessage("Unable to delete student", "error");
    }
    
} catch (PDOException $e) {
    error_log("Delete Student Error: " . $e->getMessage());
    setMessage("Error deleting student. Please try again.", "error");
}

redirect('list.php');