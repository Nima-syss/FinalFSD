<?php
// ============================================
// FILE: public/courses/add.php
// ============================================
?>
<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
checkAuth();

// Block students from adding courses
if (isStudentRole()) {
    setMessage("Access denied. Students cannot add courses.", "error");
    redirect('list.php');
}

$pageTitle = 'Add New Course';
$errors = [];

// Get instructors and categories for form
$instructors = getAllInstructors($pdo);
$categories = getCategories();
$levels = getLevels();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
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
    } elseif (courseCodeExists($pdo, $courseCode)) {
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
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO courses (course_name, course_code, description, category, level, credits, instructor_id, max_students) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $courseId = insert($pdo, $sql, [
                $courseName,
                $courseCode,
                $description,
                $category,
                $level,
                $credits,
                $instructorId,
                $maxStudents
            ]);
            
            setMessage("Course '{$courseName}' added successfully!");
            redirect('list.php');
            
        } catch (PDOException $e) {
            error_log("Add Course Error: " . $e->getMessage());
            $errors[] = "Error adding course. Please try again.";
        }
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-plus-circle"></i> Add New Course</h4>
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
                                   value="<?= h($_POST['course_name'] ?? '') ?>"
                                   required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="course_code" class="form-label">Course Code *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="course_code" 
                                   name="course_code" 
                                   value="<?= h($_POST['course_code'] ?? '') ?>"
                                   placeholder="e.g., CS101"
                                   required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" 
                                  id="description" 
                                  name="description" 
                                  rows="3"><?= h($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label">Category *</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= h($cat) ?>" 
                                            <?= (isset($_POST['category']) && $_POST['category'] == $cat) ? 'selected' : '' ?>>
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
                                            <?= (isset($_POST['level']) && $_POST['level'] == $lv) ? 'selected' : '' ?>>
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
                                   value="<?= h($_POST['credits'] ?? '3') ?>"
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
                                   value="<?= h($_POST['max_students'] ?? '30') ?>"
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
                                            <?= (isset($_POST['instructor_id']) && $_POST['instructor_id'] == $instructor['instructor_id']) ? 'selected' : '' ?>>
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
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Add Course
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>