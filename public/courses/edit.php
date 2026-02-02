<?php
// ============================================
// FILE: public/courses/edit.php
// ============================================
?>
<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
checkAuth();

// Block students from editing courses
if (isStudentRole()) {
    setMessage("Access denied. Students cannot edit courses.", "error");
    redirect('list.php');
}

$pageTitle = 'Edit Course';
$errors = [];

// Get course ID
$courseId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($courseId <= 0) {
    setMessage("Invalid course ID", "error");
    redirect('list.php');
}

// Get instructors and categories for form
$instructors = getAllInstructors($pdo);
$categories = getCategories();
$levels = getLevels();

// Fetch existing course data
try {
    $course = getOne($pdo, "SELECT * FROM courses WHERE course_id = ?", [$courseId]);
    
    if (!$course) {
        setMessage("Course not found", "error");
        redirect('list.php');
    }
} catch (PDOException $e) {
    error_log("Edit Course Error: " . $e->getMessage());
    setMessage("Error loading course", "error");
    redirect('list.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseName = trim($_POST['course_name'] ?? '');
    $courseCode = trim($_POST['course_code'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $level = $_POST['level'] ?? 'Beginner';
    $credits = intval($_POST['credits'] ?? 3);
    $instructorId = !empty($_POST['instructor_id']) ? intval($_POST['instructor_id']) : null;
    $maxStudents = intval($_POST['max_students'] ?? 30);
    
    // Validation
    if (empty($courseName)) {
        $errors[] = "Course name is required";
    }
    if (empty($courseCode)) {
        $errors[] = "Course code is required";
    } elseif (courseCodeExists($pdo, $courseCode, $courseId)) {
        $errors[] = "Course code already exists";
    }
    if (empty($category)) {
        $errors[] = "Category is required";
    }
    if ($credits < 1 || $credits > 6) {
        $errors[] = "Credits must be between 1 and 6";
    }
    if ($maxStudents < 1 || $maxStudents > 200) {
        $errors[] = "Max students must be between 1 and 200";
    }
    
    // If no errors, update database
    if (empty($errors)) {
        try {
            $sql = "UPDATE courses SET 
                    course_name = ?, 
                    course_code = ?, 
                    description = ?, 
                    category = ?, 
                    level = ?, 
                    credits = ?, 
                    instructor_id = ?, 
                    max_students = ?
                    WHERE course_id = ?";
            
            execute($pdo, $sql, [
                $courseName,
                $courseCode,
                $description,
                $category,
                $level,
                $credits,
                $instructorId,
                $maxStudents,
                $courseId
            ]);
            
            setMessage("Course '{$courseName}' updated successfully!");
            redirect('list.php');
            
        } catch (PDOException $e) {
            error_log("Update Course Error: " . $e->getMessage());
            $errors[] = "Error updating course. Please try again.";
        }
    }
    
    // Keep POST data for form
    $course = array_merge($course, $_POST);
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-warning">
                <h4 class="mb-0"><i class="bi bi-pencil"></i> Edit Course</h4>
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
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="course_name" class="form-label">Course Name *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="course_name" 
                                   name="course_name" 
                                   value="<?= h($course['course_name']) ?>"
                                   required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="course_code" class="form-label">Course Code *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="course_code" 
                                   name="course_code" 
                                   value="<?= h($course['course_code']) ?>"
                                   required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" 
                                  id="description" 
                                  name="description" 
                                  rows="3"><?= h($course['description']) ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label">Category *</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= h($cat) ?>" 
                                            <?= $course['category'] == $cat ? 'selected' : '' ?>>
                                        <?= h($cat) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="level" class="form-label">Level *</label>
                            <select class="form-select" id="level" name="level" required>
                                <?php foreach ($levels as $lv): ?>
                                    <option value="<?= h($lv) ?>" 
                                            <?= $course['level'] == $lv ? 'selected' : '' ?>>
                                        <?= h($lv) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="credits" class="form-label">Credits *</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="credits" 
                                   name="credits" 
                                   value="<?= h($course['credits']) ?>"
                                   min="1" 
                                   max="6"
                                   required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="max_students" class="form-label">Max Students *</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="max_students" 
                                   name="max_students" 
                                   value="<?= h($course['max_students']) ?>"
                                   min="1" 
                                   max="200"
                                   required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="instructor_id" class="form-label">Instructor</label>
                            <select class="form-select" id="instructor_id" name="instructor_id">
                                <option value="">Not Assigned</option>
                                <?php foreach ($instructors as $instructor): ?>
                                    <option value="<?= h($instructor['instructor_id']) ?>"
                                            <?= $course['instructor_id'] == $instructor['instructor_id'] ? 'selected' : '' ?>>
                                        <?= h($instructor['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="list.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check-circle"></i> Update Course
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>