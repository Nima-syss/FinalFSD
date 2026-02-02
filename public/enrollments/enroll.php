<?php
// ============================================
// FILE: public/enrollments/enroll.php
// ============================================
?>
<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';

$pageTitle = 'Enroll Student';
$errors = [];

// Get students and courses for dropdowns
$students = getAllStudents($pdo);
$courses = getAll($pdo, "SELECT course_id, course_name, course_code FROM courses ORDER BY course_name");

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = intval($_POST['student_id'] ?? 0);
    $courseId = intval($_POST['course_id'] ?? 0);
    $enrollmentDate = $_POST['enrollment_date'] ?? date('Y-m-d');
    
    // Validation
    if ($studentId <= 0) {
        $errors[] = "Please select a student";
    }
    if ($courseId <= 0) {
        $errors[] = "Please select a course";
    }
    
    // Check if already enrolled
    if ($studentId > 0 && $courseId > 0) {
        if (isAlreadyEnrolled($pdo, $courseId, $studentId)) {
            $errors[] = "Student is already enrolled in this course";
        }
        
        // Check course capacity
        $availableSlots = getAvailableSlots($pdo, $courseId);
        if ($availableSlots <= 0) {
            $errors[] = "Course is full - no available slots";
        }
    }
    
    // If no errors, insert enrollment
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO enrollments (course_id, student_id, enrollment_date, status) 
                    VALUES (?, ?, ?, 'Active')";
            
            $enrollmentId = insert($pdo, $sql, [$courseId, $studentId, $enrollmentDate]);
            
            setMessage("Student enrolled successfully!");
            redirect('list.php');
            
        } catch (PDOException $e) {
            error_log("Enrollment Error: " . $e->getMessage());
            $errors[] = "Error enrolling student. Please try again.";
        }
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-warning">
                <h4 class="mb-0"><i class="bi bi-clipboard-check"></i> Enroll Student in Course</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= h($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Select Student *</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="">-- Choose Student --</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= h($student['student_id']) ?>"
                                        <?= (isset($_POST['student_id']) && $_POST['student_id'] == $student['student_id']) ? 'selected' : '' ?>>
                                    <?= h($student['name']) ?> (<?= h($student['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="course_id" class="form-label">Select Course *</label>
                        <select class="form-select" id="course_id" name="course_id" required>
                            <option value="">-- Choose Course --</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= h($course['course_id']) ?>"
                                        <?= (isset($_POST['course_id']) && $_POST['course_id'] == $course['course_id']) ? 'selected' : '' ?>>
                                    <?= h($course['course_code']) ?> - <?= h($course['course_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="capacity-info"></div>
                    </div>

                    <div class="mb-3">
                        <label for="enrollment_date" class="form-label">Enrollment Date</label>
                        <input type="date" 
                               class="form-control" 
                               id="enrollment_date" 
                               name="enrollment_date" 
                               value="<?= h($_POST['enrollment_date'] ?? date('Y-m-d')) ?>">
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Note:</strong> The system will check if the student is already enrolled 
                        and if the course has available capacity.
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="list.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check-circle"></i> Enroll Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
