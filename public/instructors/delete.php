<?php
session_start();
// ============================================
// FILE: public/instructors/delete.php
// ============================================
?>
<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
checkAuth();

// Block students from deleting instructors
if (isStudentRole()) {
    setMessage("Access denied. Students cannot delete instructors.", "error");
    redirect("list.php");
}

$instructorId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($instructorId <= 0) {
    setMessage("Invalid instructor ID", "error");
    redirect('list.php');
}

try {
    $instructor = getOne($pdo, "SELECT CONCAT(first_name, ' ', last_name) as name FROM instructors WHERE instructor_id = ?", [$instructorId]);
    
    if (!$instructor) {
        setMessage("Instructor not found", "error");
        redirect('list.php');
    }
    
    // Delete instructor (courses will be set to NULL due to ON DELETE SET NULL)
    $sql = "DELETE FROM instructors WHERE instructor_id = ?";
    $affectedRows = execute($pdo, $sql, [$instructorId]);
    
    if ($affectedRows > 0) {
        setMessage("Instructor '{$instructor['name']}' deleted successfully!");
    } else {
        setMessage("Unable to delete instructor", "error");
    }
    
} catch (PDOException $e) {
    error_log("Delete Instructor Error: " . $e->getMessage());
    setMessage("Error deleting instructor. Please try again.", "error");
}

redirect('list.php');
?>