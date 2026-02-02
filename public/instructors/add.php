<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Require authentication
checkAuth();

// Block students from adding instructors
if (isStudentRole()) {
    setMessage("Access denied. Students cannot add instructors.", "error");
    redirect("list.php");
}

$pageTitle = 'Add New Instructor';
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    
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
    } elseif (instructorEmailExists($pdo, $email)) {
        $errors[] = "Email already exists";
    }
    if (empty($department)) {
        $errors[] = "Department is required";
    }
    if (empty($specialization)) {
        $errors[] = "Specialization is required";
    }
    
    // If no errors, insert into database (WITHOUT bio column)
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO instructors (first_name, last_name, email, phone, department, specialization) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $instructorId = insert($pdo, $sql, [
                $firstName,
                $lastName,
                $email,
                $phone,
                $department,
                $specialization
            ]);
            
            setMessage("Instructor '{$firstName} {$lastName}' added successfully!");
            redirect('list.php');
            
        } catch (PDOException $e) {
            error_log("Add Instructor Error: " . $e->getMessage());
            
            if ($e->getCode() == 'HY000') {
                $errors[] = "Database connection lost. Please restart MySQL and try again.";
            } else {
                $errors[] = "Database Error: " . $e->getMessage();
            }
        }
    }
}

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0"><i class="bi bi-person-plus"></i> Add New Instructor</h4>
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
                        <div class="col-md-6 mb-3">
                            <label for="department" class="form-label">Department *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="department" 
                                   name="department" 
                                   value="<?= h($_POST['department'] ?? '') ?>"
                                   required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="specialization" class="form-label">Specialization *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="specialization" 
                                   name="specialization" 
                                   value="<?= h($_POST['specialization'] ?? '') ?>"
                                   required>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="list.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Add Instructor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>