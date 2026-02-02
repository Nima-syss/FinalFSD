<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
checkAuth();

$userRole = $_SESSION['role'] ?? 'student';
$userId = $_SESSION['user_id'] ?? null;

// Determine page title and fetch instructors based on role
if (isStudentRole()) {
    $pageTitle = 'My Instructors';
    $instructors = getStudentInstructors($pdo, $userId);
} else {
    $pageTitle = 'All Instructors';
    try {
        $sql = "SELECT i.*,
                (SELECT COUNT(*) FROM courses c WHERE c.instructor_id = i.instructor_id) as course_count
                FROM instructors i
                ORDER BY i.first_name, i.last_name";
        
        $instructors = getAll($pdo, $sql);
    } catch (PDOException $e) {
        error_log("Instructors List Error: " . $e->getMessage());
        setMessage("Error loading instructors", "error");
        $instructors = [];
    }
}

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-person-badge"></i> <?= h($pageTitle) ?></h1>
    <?php if (!isStudentRole()): ?>
        <a href="add.php" class="btn btn-success">
            <i class="bi bi-person-plus"></i> Add New Instructor
        </a>
    <?php endif; ?>
</div>

<?php if (isStudentRole() && empty($instructors)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> You don't have any instructors yet. Enroll in courses to see your instructors.
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-success">
                        <tr>
                            <?php if (!isStudentRole()): ?>
                                <th>ID</th>
                            <?php endif; ?>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Department</th>
                            <th>Bio</th>
                            <?php if (isStudentRole()): ?>
                                <th>My Courses</th>
                            <?php else: ?>
                                <th>Total Courses</th>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($instructors)): ?>
                            <tr>
                                <td colspan="<?= isStudentRole() ? '6' : '8' ?>" class="text-center text-muted">No instructors found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($instructors as $instructor): ?>
                                <tr>
                                    <?php if (!isStudentRole()): ?>
                                        <td><?= h($instructor['instructor_id']) ?></td>
                                    <?php endif; ?>
                                    <td><strong><?= h($instructor['first_name'] . ' ' . $instructor['last_name']) ?></strong></td>
                                    <td><?= h($instructor['email']) ?></td>
                                    <td><?= h($instructor['phone'] ?? '-') ?></td>
                                    <td><?= h($instructor['department'] ?? '-') ?></td>
                                    <td><?= h($instructor['bio'] ?? '-') ?></td>
                                    <?php if (isStudentRole()): ?>
                                        <td>
                                            <span class="badge bg-success"><?= h($instructor['my_courses_count']) ?> courses</span>
                                        </td>
                                    <?php else: ?>
                                        <td>
                                            <span class="badge bg-success"><?= h($instructor['course_count']) ?> courses</span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="edit.php?id=<?= h($instructor['instructor_id']) ?>" 
                                                   class="btn btn-outline-primary" 
                                                   title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-outline-danger" 
                                                        onclick="confirmDelete(<?= h($instructor['instructor_id']) ?>, '<?= h($instructor['first_name'] . ' ' . $instructor['last_name']) ?>')"
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
function confirmDelete(instructorId, instructorName) {
    if (confirm(`Are you sure you want to delete "${instructorName}"?\n\nTheir assigned courses will be set to "Not Assigned".`)) {
        window.location.href = `delete.php?id=${instructorId}`;
    }
}
</script>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>