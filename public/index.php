<?php
// Require authentication
require_once '../includes/auth.php';
checkAuth(); // Redirect to login if not authenticated

require_once '../config/db.php';
require_once '../includes/functions.php';

$pageTitle = 'Dashboard';

// Get statistics with better error handling
try {
    // Total counts - simple queries first
    $totalCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    $totalInstructors = $pdo->query("SELECT COUNT(*) FROM instructors")->fetchColumn();
    $totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $totalEnrollments = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'Active'")->fetchColumn();
    
    // Recent courses
    $recentCourses = getAll($pdo, "
        SELECT c.course_id, c.course_name, c.course_code, c.level, c.max_students,
               CONCAT(COALESCE(i.first_name, ''), ' ', COALESCE(i.last_name, '')) as instructor_name,
               (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.course_id AND e.status = 'Active') as enrolled_count
        FROM courses c
        LEFT JOIN instructors i ON c.instructor_id = i.instructor_id
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    
    // Popular courses (most enrolled)
    $popularCourses = getAll($pdo, "
        SELECT c.course_name, c.course_code,
               CONCAT(COALESCE(i.first_name, ''), ' ', COALESCE(i.last_name, '')) as instructor_name,
               COUNT(e.enrollment_id) as enrollment_count
        FROM courses c
        LEFT JOIN enrollments e ON c.course_id = e.course_id AND e.status = 'Active'
        LEFT JOIN instructors i ON c.instructor_id = i.instructor_id
        GROUP BY c.course_id, c.course_name, c.course_code, i.first_name, i.last_name
        HAVING COUNT(e.enrollment_id) > 0
        ORDER BY enrollment_count DESC
        LIMIT 5
    ");
    
} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $totalCourses = 0;
    $totalInstructors = 0;
    $totalStudents = 0;
    $totalEnrollments = 0;
    $recentCourses = [];
    $popularCourses = [];
}

include '../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4"><i class="bi bi-speedometer2"></i> Dashboard</h1>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-0">Total Courses</h6>
                        <h2 class="mt-2 mb-0"><?= h($totalCourses) ?></h2>
                    </div>
                    <div class="text-white-50">
                        <i class="bi bi-book" style="font-size: 3rem;"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-primary bg-opacity-75">
                <a href="courses/list.php" class="text-white text-decoration-none">
                    View all courses <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-0">Instructors</h6>
                        <h2 class="mt-2 mb-0"><?= h($totalInstructors) ?></h2>
                    </div>
                    <div class="text-white-50">
                        <i class="bi bi-person-badge" style="font-size: 3rem;"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-success bg-opacity-75">
                <a href="instructors/list.php" class="text-white text-decoration-none">
                    View all instructors <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-0">Active Students</h6>
                        <h2 class="mt-2 mb-0"><?= h($totalStudents) ?></h2>
                    </div>
                    <div class="text-white-50">
                        <i class="bi bi-people" style="font-size: 3rem;"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-info bg-opacity-75">
                <a href="students/list.php" class="text-white text-decoration-none">
                    View all students <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-0">Enrollments</h6>
                        <h2 class="mt-2 mb-0"><?= h($totalEnrollments) ?></h2>
                    </div>
                    <div class="text-white-50">
                        <i class="bi bi-card-checklist" style="font-size: 3rem;"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-warning bg-opacity-75">
                <a href="enrollments/list.php" class="text-white text-decoration-none">
                    View all enrollments <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent and Popular Courses -->
<div class="row">
    <!-- Recent Courses -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Courses</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentCourses)): ?>
                    <p class="text-muted">No courses available.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentCourses as $course): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= h($course['course_name']) ?></h6>
                                    <small class="badge bg-info"><?= h($course['level']) ?></small>
                                </div>
                                <p class="mb-1 text-muted">
                                    <small>
                                        <strong>Code:</strong> <?= h($course['course_code']) ?> | 
                                        <strong>Instructor:</strong> <?= h(trim($course['instructor_name']) ?: 'Not assigned') ?>
                                    </small>
                                </p>
                                <small class="text-muted">
                                    Enrolled: <?= h($course['enrolled_count']) ?>/<?= h($course['max_students']) ?> students
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Popular Courses -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Most Popular Courses</h5>
            </div>
            <div class="card-body">
                <?php if (empty($popularCourses)): ?>
                    <p class="text-muted">No enrollment data available.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($popularCourses as $course): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= h($course['course_name']) ?></h6>
                                    <span class="badge bg-success rounded-pill"><?= h($course['enrollment_count']) ?> enrolled</span>
                                </div>
                                <p class="mb-1 text-muted">
                                    <small>
                                        <strong>Code:</strong> <?= h($course['course_code']) ?> | 
                                        <strong>Instructor:</strong> <?= h(trim($course['instructor_name']) ?: 'Not assigned') ?>
                                    </small>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <a href="courses/add.php" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle"></i> Add New Course
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="instructors/add.php" class="btn btn-success w-100">
                            <i class="bi bi-person-plus"></i> Add Instructor
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="students/add.php" class="btn btn-info w-100">
                            <i class="bi bi-person-plus-fill"></i> Add Student
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="enrollments/enroll.php" class="btn btn-warning w-100">
                            <i class="bi bi-clipboard-check"></i> Enroll Student
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>