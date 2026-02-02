<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
checkAuth();

$userRole = $_SESSION['role'] ?? 'student';
$userId = $_SESSION['user_id'] ?? null;

// Determine page title and fetch courses based on role
if (isStudentRole()) {
    $pageTitle = 'My Enrolled Courses';
    $courses = getStudentCourses($pdo, $userId);
} else {
    $pageTitle = 'All Courses';
    try {
        $sql = "SELECT c.*, 
                CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
                (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.course_id AND e.status = 'Active') as enrolled_count
                FROM courses c
                LEFT JOIN instructors i ON c.instructor_id = i.instructor_id
                ORDER BY c.course_name";
        
        $courses = getAll($pdo, $sql);
    } catch (PDOException $e) {
        error_log("Courses List Error: " . $e->getMessage());
        setMessage("Error loading courses", "error");
        $courses = [];
    }
}

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-book"></i> <?= h($pageTitle) ?></h1>
    <?php if (!isStudentRole()): ?>
        <a href="add.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Course
        </a>
    <?php endif; ?>
</div>

<?php if (isStudentRole() && empty($courses)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> You are not enrolled in any courses yet. Contact your instructor to get enrolled.
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-primary">
                        <tr>
                            <th>Code</th>
                            <th>Course Name</th>
                            <th>Category</th>
                            <th>Level</th>
                            <th>Credits</th>
                            <th>Instructor</th>
                            <?php if (isStudentRole()): ?>
                                <th>Status</th>
                                <th>Grade</th>
                                <th>Enrolled</th>
                            <?php else: ?>
                                <th>Enrolled/Max</th>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($courses)): ?>
                            <tr>
                                <td colspan="<?= isStudentRole() ? '9' : '8' ?>" class="text-center text-muted">No courses found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($courses as $course): ?>
                                <?php
                                if (!isStudentRole()) {
                                    $availableSlots = $course['max_students'] - $course['enrolled_count'];
                                    $slotClass = $availableSlots > 10 ? 'text-success' : ($availableSlots > 0 ? 'text-warning' : 'text-danger');
                                }
                                ?>
                                <tr>
                                    <td><strong><?= h($course['course_code']) ?></strong></td>
                                    <td><?= h($course['course_name']) ?></td>
                                    <td><?= h($course['category']) ?></td>
                                    <td>
                                        <span class="badge 
                                            <?= $course['level'] == 'Beginner' ? 'bg-success' : 
                                                ($course['level'] == 'Intermediate' ? 'bg-warning' : 'bg-danger') ?>">
                                            <?= h($course['level']) ?>
                                        </span>
                                    </td>
                                    <td><?= h($course['credits']) ?></td>
                                    <td><?= h($course['instructor_name'] ?? 'Not Assigned') ?></td>
                                    
                                    <?php if (isStudentRole()): ?>
                                        <td>
                                            <span class="badge bg-<?= $course['enrollment_status'] == 'Active' ? 'success' : 
                                                ($course['enrollment_status'] == 'Completed' ? 'primary' : 'secondary') ?>">
                                                <?= h($course['enrollment_status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($course['grade'])): ?>
                                                <span class="badge bg-info"><?= h($course['grade']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?= date('M d, Y', strtotime($course['enrollment_date'])) ?></small></td>
                                    <?php else: ?>
                                        <td class="<?= $slotClass ?>">
                                            <strong><?= h($course['enrolled_count']) ?>/<?= h($course['max_students']) ?></strong>
                                            <?php if ($availableSlots == 0): ?>
                                                <span class="badge bg-danger ms-1">Full</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="edit.php?id=<?= h($course['course_id']) ?>" 
                                                   class="btn btn-outline-primary" 
                                                   title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-outline-danger" 
                                                        onclick="confirmDelete(<?= h($course['course_id']) ?>, '<?= h($course['course_name']) ?>')"
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
function confirmDelete(courseId, courseName) {
    if (confirm(`Are you sure you want to delete "${courseName}"?\n\nThis will also remove all student enrollments for this course.`)) {
        window.location.href = `delete.php?id=${courseId}`;
    }
}
</script>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
