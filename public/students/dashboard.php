<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

requireUserType('student');
checkSessionTimeout();

$studentId = $_SESSION['student_id'];
$studentName = $_SESSION['user_name'];

// Get enrolled courses
try {
    $enrollmentsSql = "SELECT e.enrollment_id, e.enrollment_date, e.status, e.grade,
                        c.course_id, c.course_code, c.course_name, c.description, c.credits,
                        c.category, c.level,
                        CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
                        i.email as instructor_email, i.department,
                        CONCAT(enrolled_by.first_name, ' ', enrolled_by.last_name) as enrolled_by_name
                       FROM enrollments e
                       JOIN courses c ON e.course_id = c.course_id
                       LEFT JOIN instructors i ON c.instructor_id = i.instructor_id
                       LEFT JOIN instructors enrolled_by ON e.enrolled_by_instructor_id = enrolled_by.instructor_id
                       WHERE e.student_id = ?
                       ORDER BY e.status = 'Active' DESC, e.enrollment_date DESC";
    
    $enrollments = getAll($pdo, $enrollmentsSql, [$studentId]);
    $activeEnrollments = array_filter($enrollments, fn($e) => $e['status'] === 'Active');
    $otherEnrollments = array_filter($enrollments, fn($e) => $e['status'] !== 'Active');
} catch (PDOException $e) {
    $enrollments = [];
    $activeEnrollments = [];
    $otherEnrollments = [];
}

$totalCredits = array_sum(array_column($activeEnrollments, 'credits'));
$completedCount = count(array_filter($enrollments, fn($e) => $e['status'] === 'Completed'));

include '../../includes/header.php';
?>

<div class="dashboard-header bg-primary text-white p-4 mb-4">
    <div class="container">
        <h1><i class="bi bi-speedometer2"></i> Student Dashboard</h1>
        <p class="mb-0">Welcome back, <?= h($studentName) ?>!</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted">Active Courses</h6>
                    <h2><?= count($activeEnrollments) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted">Total Credits</h6>
                    <h2><?= $totalCredits ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted">Completed</h6>
                    <h2><?= $completedCount ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <h6 class="text-muted">Total Courses</h6>
                    <h2><?= count($enrollments) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <h3 class="mb-3"><i class="bi bi-book-half"></i> My Active Courses</h3>
    <?php if (empty($activeEnrollments)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> You are not enrolled in any courses yet.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($activeEnrollments as $enrollment): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 border-start border-primary border-4">
                        <div class="card-body">
                            <h5 class="card-title"><?= h($enrollment['course_code']) ?></h5>
                            <h6 class="text-muted mb-3"><?= h($enrollment['course_name']) ?></h6>
                            
                            <p class="card-text small">
                                <?= h(substr($enrollment['description'] ?? 'No description', 0, 100)) ?>...
                            </p>
                            
                            <div class="mb-2">
                                <small><i class="bi bi-person"></i> <strong>Instructor:</strong> 
                                <?= h($enrollment['instructor_name']) ?></small>
                            </div>
                            
                            <div class="mb-2">
                                <small><i class="bi bi-tag"></i> <strong>Category:</strong> 
                                <?= h($enrollment['category'] ?? 'N/A') ?></small>
                            </div>
                            
                            <div class="mb-2">
                                <small><i class="bi bi-bar-chart"></i> <strong>Level:</strong> 
                                <span class="badge bg-info"><?= h($enrollment['level']) ?></span></small>
                            </div>
                            
                            <div class="mb-2">
                                <small><i class="bi bi-award"></i> <strong>Credits:</strong> 
                                <?= h($enrollment['credits']) ?></small>
                            </div>
                            
                            <?php if ($enrollment['grade']): ?>
                                <div class="mb-2">
                                    <small><i class="bi bi-graph-up"></i> <strong>Grade:</strong> 
                                    <span class="badge bg-success"><?= h($enrollment['grade']) ?></span></small>
                                </div>
                            <?php endif; ?>
                            
                            <hr>
                            
                            <small class="text-muted">
                                <i class="bi bi-calendar-event"></i> Enrolled: 
                                <?= date('M d, Y', strtotime($enrollment['enrollment_date'])) ?>
                            </small>
                            
                            <?php if ($enrollment['enrolled_by_name']): ?>
                                <br><small class="text-muted">
                                    <i class="bi bi-person-check"></i> By: <?= h($enrollment['enrolled_by_name']) ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($otherEnrollments)): ?>
        <h3 class="mb-3 mt-5"><i class="bi bi-clock-history"></i> Course History</h3>
        <div class="card">
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Instructor</th>
                            <th>Status</th>
                            <th>Grade</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($otherEnrollments as $enrollment): ?>
                            <tr>
                                <td><?= h($enrollment['course_code']) ?></td>
                                <td><?= h($enrollment['course_name']) ?></td>
                                <td><?= h($enrollment['instructor_name']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $enrollment['status'] == 'Completed' ? 'success' : 'secondary' ?>">
                                        <?= h($enrollment['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($enrollment['grade']): ?>
                                        <span class="badge bg-primary"><?= h($enrollment['grade']) ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($enrollment['enrollment_date'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>
