<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

requireUserType('instructor');
checkSessionTimeout();

$instructorId = $_SESSION['instructor_id'];
$instructorName = $_SESSION['user_name'];

// Get instructor courses
try {
    $coursesSql = "SELECT c.course_id, c.course_code, c.course_name, c.max_students,
                   COUNT(e.enrollment_id) as enrolled_count
                   FROM courses c
                   LEFT JOIN enrollments e ON c.course_id = e.course_id AND e.status = 'Active'
                   WHERE c.instructor_id = ? AND c.is_active = 1
                   GROUP BY c.course_id";
    $courses = getAll($pdo, $coursesSql, [$instructorId]);
} catch (PDOException $e) {
    $courses = [];
}

// Get recent enrollments
try {
    $recentSql = "SELECT e.enrollment_date, 
                    CONCAT(s.first_name, ' ', s.last_name) as student_name,
                    c.course_code, c.course_name
                  FROM enrollments e
                  JOIN students s ON e.student_id = s.student_id
                  JOIN courses c ON e.course_id = c.course_id
                  WHERE c.instructor_id = ?
                  ORDER BY e.enrollment_date DESC LIMIT 10";
    $recentEnrollments = getAll($pdo, $recentSql, [$instructorId]);
} catch (PDOException $e) {
    $recentEnrollments = [];
}

include '../../includes/header.php';
?>

<div class="dashboard-header bg-primary text-white p-4 mb-4">
    <div class="container">
        <h1><i class="bi bi-speedometer2"></i> Instructor Dashboard</h1>
        <p class="mb-0">Welcome back, <?= h($instructorName) ?>!</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">My Courses</h6>
                    <h2><?= count($courses) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Total Students</h6>
                    <h2><?= array_sum(array_column($courses, 'enrolled_count')) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <a href="enroll_student.php" class="btn btn-primary w-100">
                        <i class="bi bi-person-plus-fill"></i> Enroll Student
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-book"></i> My Courses</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($courses)): ?>
                        <p class="text-muted">No courses assigned</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($courses as $course): ?>
                                <div class="list-group-item">
                                    <h6><?= h($course['course_code']) ?></h6>
                                    <p class="mb-1"><?= h($course['course_name']) ?></p>
                                    <small>
                                        <span class="badge bg-primary"><?= $course['enrolled_count'] ?>/<?= $course['max_students'] ?></span>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Enrollments</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recentEnrollments)): ?>
                        <p class="text-muted">No recent enrollments</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($recentEnrollments as $enrollment): ?>
                                <div class="list-group-item">
                                    <h6><?= h($enrollment['student_name']) ?></h6>
                                    <small><?= h($enrollment['course_code']) ?>: <?= h($enrollment['course_name']) ?></small><br>
                                    <small class="text-muted"><?= date('M d, Y', strtotime($enrollment['enrollment_date'])) ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
