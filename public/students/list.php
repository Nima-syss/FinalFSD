<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
checkAuth();

$userRole = $_SESSION['role'] ?? 'student';
$userId = $_SESSION['user_id'] ?? null;

// Determine page title and fetch students based on role
if (isStudentRole()) {
    $pageTitle = 'My Classmates';
    $students = getStudentClassmates($pdo, $userId);
} else {
    $pageTitle = 'All Students';
    try {
        $sql = "SELECT s.student_id, s.first_name, s.last_name, s.email, s.phone, s.enrollment_date,
                (SELECT COUNT(*) FROM enrollments e WHERE e.student_id = s.student_id AND e.status = 'Active') as course_count
                FROM students s
                ORDER BY s.first_name, s.last_name";
        
        $students = getAll($pdo, $sql);
    } catch (PDOException $e) {
        error_log("Students List Error: " . $e->getMessage());
        setMessage("Error loading students", "error");
        $students = [];
    }
}

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-people"></i> <?= h($pageTitle) ?></h1>
    <?php if (!isStudentRole()): ?>
        <a href="add.php" class="btn btn-primary">
            <i class="bi bi-person-plus-fill"></i> Add New Student
        </a>
    <?php endif; ?>
</div>

<?php if (isStudentRole() && empty($students)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> You don't have any classmates yet. Enroll in courses to see other students.
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-info">
                        <tr>
                            <?php if (!isStudentRole()): ?>
                                <th>ID</th>
                            <?php endif; ?>
                            <th>Name</th>
                            <th>Email</th>
                            <?php if (!isStudentRole()): ?>
                                <th>Phone</th>
                                <th>Enrollment Date</th>
                            <?php endif; ?>
                            <?php if (isStudentRole()): ?>
                                <th>Shared Courses</th>
                            <?php else: ?>
                                <th>Active Courses</th>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="<?= isStudentRole() ? '3' : '7' ?>" class="text-center text-muted">No students found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <?php if (!isStudentRole()): ?>
                                        <td><?= h($student['student_id']) ?></td>
                                    <?php endif; ?>
                                    <td><strong><?= h($student['first_name'] . ' ' . $student['last_name']) ?></strong></td>
                                    <td><?= h($student['email']) ?></td>
                                    <?php if (!isStudentRole()): ?>
                                        <td><?= h($student['phone'] ?? '-') ?></td>
                                        <td><?= formatDate($student['enrollment_date']) ?></td>
                                    <?php endif; ?>
                                    
                                    <?php if (isStudentRole()): ?>
                                        <td>
                                            <span class="badge bg-primary"><?= h($student['shared_courses']) ?> courses</span>
                                        </td>
                                    <?php else: ?>
                                        <td>
                                            <span class="badge bg-primary"><?= h($student['course_count']) ?> courses</span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="edit.php?id=<?= h($student['student_id']) ?>" 
                                                   class="btn btn-outline-primary" 
                                                   title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-outline-danger" 
                                                        onclick="confirmDelete(<?= h($student['student_id']) ?>, '<?= h($student['first_name'] . ' ' . $student['last_name']) ?>')"
                                                        title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!isStudentRole()): ?>
<script>
function confirmDelete(studentId, studentName) {
    if (confirm(`Are you sure you want to delete "${studentName}"?\n\nThis will also remove all course enrollments for this student.`)) {
        window.location.href = `delete.php?id=${studentId}`;
    }
}
</script>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
