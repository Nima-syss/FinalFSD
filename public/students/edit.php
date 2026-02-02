<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
checkAuth();

$pageTitle = 'Edit Student';
$errors = [];

// Get student ID
$studentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($studentId <= 0) {
    setMessage("Invalid student ID", "error");
    redirect('list.php');
}

// Check access permissions
if (isStudentRole()) {
    // Students can only edit their own profile
    if ($studentId != $_SESSION['user_id']) {
        setMessage("Access denied. You can only edit your own profile.", "error");
        redirect('list.php');
    }
    $pageTitle = 'Edit My Profile';
}

// Fetch existing student data
try {
    $student = getOne($pdo, "SELECT student_id, first_name, last_name, email, phone, enrollment_date FROM students WHERE student_id = ?", [$studentId]);
    
    if (!$student) {
        setMessage("Student not found", "error");
        redirect('list.php');
    }
} catch (PDOException $e) {
    error_log("Edit Student Error: " . $e->getMessage());
    setMessage("Error loading student", "error");
    redirect('list.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $enrollmentDate = $_POST['enrollment_date'] ?? date('Y-m-d');
    
    // Validation
    if (empty($firstName)) {
        $errors[] = "First name is required";
    }
    if (empty($lastName)) {
        $errors[] = "Last name is required";
    }
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!isValidEmail($email)) {
        $errors[] = "Invalid email format";
    } elseif (emailExists($pdo, $email, $studentId)) {
        $errors[] = "Email already exists";
    }
    
    // If no errors, update database
    if (empty($errors)) {
        try {
            $sql = "UPDATE students SET 
                    first_name = ?, 
                    last_name = ?, 
                    email = ?, 
                    phone = ?, 
                    enrollment_date = ?
                    WHERE student_id = ?";
            
            execute($pdo, $sql, [
                $firstName,
                $lastName,
                $email,
                $phone,
                $enrollmentDate,
                $studentId
            ]);
            
            setMessage("Student '{$firstName} {$lastName}' updated successfully!");
            redirect('list.php');
            
        } catch (PDOException $e) {
            error_log("Update Student Error: " . $e->getMessage());
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
    
    // Keep POST data for form
    $student = array_merge($student, $_POST);
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-warning">
                <h4 class="mb-0"><i class="bi bi-pencil"></i> Edit Student</h4>
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
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="first_name" 
                                   name="first_name" 
                                   value="<?= h($student['first_name']) ?>"
                                   required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="last_name" 
                                   name="last_name" 
                                   value="<?= h($student['last_name']) ?>"
                                   required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="<?= h($student['email']) ?>"
                               required>
                        <input type="hidden" id="student_id" value="<?= h($studentId) ?>">
                        <div id="email-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" 
                               class="form-control" 
                               id="phone" 
                               name="phone" 
                               value="<?= h($student['phone']) ?>"
                               placeholder="555-0123">
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="enrollment_date" class="form-label">Enrollment Date</label>
                            <input type="date" 
                                   class="form-control" 
                                   id="enrollment_date" 
                                   name="enrollment_date" 
                                   value="<?= h($student['enrollment_date']) ?>">
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="list.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-check-circle"></i> Update Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>