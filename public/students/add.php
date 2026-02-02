<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
checkAuth();

// Block students from adding other students
if (isStudentRole()) {
    setMessage("Access denied. Students cannot add other students.", "error");
    redirect("list.php");
}

$pageTitle = 'Add New Student';
$errors = [];

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
    } elseif (emailExists($pdo, $email)) {
        $errors[] = "Email already exists";
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            // Check if status column exists, if not, don't include it
            $sql = "INSERT INTO students (first_name, last_name, email, phone, enrollment_date) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $studentId = insert($pdo, $sql, [
                $firstName,
                $lastName,
                $email,
                $phone,
                $enrollmentDate
            ]);
            
            setMessage("Student '{$firstName} {$lastName}' added successfully!");
            redirect('list.php');
            
        } catch (PDOException $e) {
            error_log("Add Student Error: " . $e->getMessage());
            // Show detailed error for debugging
            $errors[] = "Database Error: " . $e->getMessage();
        }
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0"><i class="bi bi-person-plus-fill"></i> Add New Student</h4>
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
                                   value="<?= h($_POST['first_name'] ?? '') ?>"
                                   required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="last_name" 
                                   name="last_name" 
                                   value="<?= h($_POST['last_name'] ?? '') ?>"
                                   required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="<?= h($_POST['email'] ?? '') ?>"
                               required>
                        <div id="email-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" 
                               class="form-control" 
                               id="phone" 
                               name="phone" 
                               value="<?= h($_POST['phone'] ?? '') ?>"
                               placeholder="555-0123">
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="enrollment_date" class="form-label">Enrollment Date</label>
                            <input type="date" 
                                   class="form-control" 
                                   id="enrollment_date" 
                                   name="enrollment_date" 
                                   value="<?= h($_POST['enrollment_date'] ?? date('Y-m-d')) ?>">
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="list.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-info text-white">
                            <i class="bi bi-check-circle"></i> Add Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>