<?php
// ============================================
// FILE: public/enrollments/list.php
// ============================================
?>
<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';

$pageTitle = 'All Enrollments';

try {
    $sql = "SELECT e.*, 
            CONCAT(s.first_name, ' ', s.last_name) as student_name,
            s.email as student_email,
            c.course_name,
            c.course_code,
            CONCAT(i.first_name, ' ', i.last_name) as instructor_name
            FROM enrollments e
            JOIN students s ON e.student_id = s.student_id
            JOIN courses c ON e.course_id = c.course_id
            LEFT JOIN instructors i ON c.instructor_id = i.instructor_id
            ORDER BY e.enrollment_date DESC";
    
    $enrollments = getAll($pdo, $sql);
} catch (PDOException $e) {
    error_log("Enrollments List Error: " . $e->getMessage());
    setMessage("Error loading enrollments", "error");
    $enrollments = [];
}

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-card-checklist"></i> All Enrollments</h1>
    <a href="enroll.php" class="btn btn-warning">
        <i class="bi bi-clipboard-check"></i> Enroll New Student
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-warning">
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Instructor</th>
                        <th>Enrollment Date</th>
                        <th>Status</th>
                        <th>Grade</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($enrollments)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No enrollments found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($enrollments as $enrollment): ?>
                            <tr>
                                <td><?= h($enrollment['enrollment_id']) ?></td>
                                <td>
                                    <strong><?= h($enrollment['student_name']) ?></strong>
                                    <br><small class="text-muted"><?= h($enrollment['student_email']) ?></small>
                                </td>
                                <td>
                                    <strong><?= h($enrollment['course_code']) ?></strong>
                                    <br><small><?= h($enrollment['course_name']) ?></small>
                                </td>
                                <td><?= h($enrollment['instructor_name'] ?? 'Not Assigned') ?></td>
                                <td><?= formatDate($enrollment['enrollment_date']) ?></td>
                                <td>
                                    <span class="badge 
                                        <?= $enrollment['status'] == 'Active' ? 'bg-success' : 
                                            ($enrollment['status'] == 'Completed' ? 'bg-info' : 'bg-secondary') ?>">
                                        <?= h($enrollment['status']) ?>
                                    </span>
                                </td>
                                <td><?= h($enrollment['grade'] ?? '-') ?></td>
                                <td>
                                    <?php if ($enrollment['status'] == 'Active'): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmUnenroll(<?= h($enrollment['enrollment_id']) ?>, '<?= h($enrollment['student_name']) ?>', '<?= h($enrollment['course_name']) ?>')">
                                            <i class="bi bi-x-circle"></i> Unenroll
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function confirmUnenroll(enrollmentId, studentName, courseName) {
    if (confirm(`Unenroll ${studentName} from ${courseName}?`)) {
        window.location.href = `unenroll.php?id=${enrollmentId}`;
    }
}
</script>

<?php include '../../includes/footer.php'; ?>