<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

requireUserType('instructor');
checkSessionTimeout();

$instructorId = $_SESSION['instructor_id'];
$instructorName = $_SESSION['user_name'];
$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid form submission";
    } else {
        $studentId = $_POST['student_id'] ?? '';
        $courseId = $_POST['course_id'] ?? '';
        
        if (empty($studentId)) $errors[] = "Please select a student";
        if (empty($courseId)) $errors[] = "Please select a course";
        
        if (empty($errors)) {
            try {
                // Verify course belongs to instructor
                $verifySql = "SELECT course_id, course_name, course_code, max_students 
                             FROM courses WHERE course_id = ? AND instructor_id = ?";
                $course = getOne($pdo, $verifySql, [$courseId, $instructorId]);
                
                if (!$course) {
                    $errors[] = "Invalid course selection";
                } else {
                    // Check if already enrolled
                    if (isAlreadyEnrolled($pdo, $courseId, $studentId)) {
                        $errors[] = "Student already enrolled";
                    } else {
                        // Check capacity
                        $countSql = "SELECT COUNT(*) FROM enrollments WHERE course_id = ? AND status = 'Active'";
                        $stmt = $pdo->prepare($countSql);
                        $stmt->execute([$courseId]);
                        
                        if ($stmt->fetchColumn() >= $course['max_students']) {
                            $errors[] = "Course is full";
                        } else {
                            // Enroll student
                            $enrollSql = "INSERT INTO enrollments 
                                         (student_id, course_id, status, enrolled_by_instructor_id) 
                                         VALUES (?, ?, 'Active', ?)";
                            $pdo->prepare($enrollSql)->execute([$studentId, $courseId, $instructorId]);
                            
                            $studentSql = "SELECT CONCAT(first_name, ' ', last_name) as name FROM students WHERE student_id = ?";
                            $studentName = getOne($pdo, $studentSql, [$studentId])['name'];
                            
                            $success = "Successfully enrolled " . h($studentName) . " in " . h($course['course_code']);
                            $_POST = [];
                        }
                    }
                }
            } catch (PDOException $e) {
                error_log("Enrollment Error: " . $e->getMessage());
                $errors[] = "Enrollment failed";
            }
        }
    }
}

$students = getAllStudents($pdo);

try {
    $coursesSql = "SELECT c.course_id, c.course_code, c.course_name, c.max_students,
                  COUNT(e.enrollment_id) as enrolled_count
                  FROM courses c
                  LEFT JOIN enrollments e ON c.course_id = e.course_id AND e.status = 'Active'
                  WHERE c.instructor_id = ?
                  GROUP BY c.course_id";
    $courses = getAll($pdo, $coursesSql, [$instructorId]);
} catch (PDOException $e) {
    $courses = [];
}

$csrfToken = generateCSRFToken();
include '../../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-person-plus-fill"></i> Enroll Student in Course</h4>
                </div>
                <div class="card-body p-4">
                    <?php displayMessage(); ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= h($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                        
                        <div class="mb-4">
                            <label for="student_id" class="form-label"><i class="bi bi-person"></i> Select Student *</label>
                            <select class="form-select" id="student_id" name="student_id" required>
                                <option value="">-- Choose a student --</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= $student['student_id'] ?>">
                                        <?= h($student['name']) ?> - <?= h($student['email']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="course_id" class="form-label"><i class="bi bi-book"></i> Select Course *</label>
                            <select class="form-select" id="course_id" name="course_id" required>
                                <option value="">-- Choose a course --</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $course['course_id'] ?>" 
                                            <?= ($course['enrolled_count'] >= $course['max_students']) ? 'disabled' : '' ?>>
                                        <?= h($course['course_code']) ?> - <?= h($course['course_name']) ?>
                                        (<?= $course['enrolled_count'] ?>/<?= $course['max_students'] ?>)
                                        <?= ($course['enrolled_count'] >= $course['max_students']) ? ' - FULL' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-person-plus-fill"></i> Enroll Student
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
