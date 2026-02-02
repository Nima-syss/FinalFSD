<?php
/**
 * Registration Page - ENHANCED with password hashing
 */

session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header('Location: public/index.php');
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid form submission";
    } else {
        $userType = $_POST['user_type'] ?? 'student';
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($firstName) || empty($lastName)) {
            $errors[] = "First name and last name are required";
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email is required";
        }
        
        if (empty($password) || strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters";
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = "Passwords do not match";
        }
        
        if (empty($errors)) {
            try {
                $checkSql = "SELECT 
                    (SELECT COUNT(*) FROM instructors WHERE email = ?) +
                    (SELECT COUNT(*) FROM students WHERE email = ?) as total";
                $stmt = $pdo->prepare($checkSql);
                $stmt->execute([$email, $email]);
                
                if ($stmt->fetchColumn() > 0) {
                    $errors[] = "Email already exists";
                }
            } catch (PDOException $e) {
                $errors[] = "Error checking email";
            }
        }
        
        if (empty($errors)) {
            try {
                $hashedPassword = hashPassword($password);
                
                if ($userType === 'instructor') {
                    $department = trim($_POST['department'] ?? '');
                    $sql = "INSERT INTO instructors (first_name, last_name, email, password, phone, department, is_active) 
                            VALUES (?, ?, ?, ?, ?, ?, 1)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$firstName, $lastName, $email, $hashedPassword, $phone, $department]);
                    $success = "Instructor account created! You can now login.";
                } else {
                    $sql = "INSERT INTO students (first_name, last_name, email, password, phone, is_active) 
                            VALUES (?, ?, ?, ?, ?, 1)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$firstName, $lastName, $email, $hashedPassword, $phone]);
                    $success = "Student account created! You can now login.";
                }
                
                $_POST = [];
                regenerateCSRFToken();
            } catch (PDOException $e) {
                error_log("Registration Error: " . $e->getMessage());
                $errors[] = "Registration failed. Please try again.";
            }
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Course Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        .register-card {
            max-width: 600px;
            width: 100%;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 2rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        .type-specific-fields {
            display: none;
        }
        .type-specific-fields.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-card mx-auto">
            <div class="card">
                <div class="card-header text-center">
                    <h3 class="mb-0"><i class="bi bi-person-plus-fill"></i><br>Create Account</h3>
                    <p class="mb-0 mt-2">Join Course Management System</p>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <strong><i class="bi bi-exclamation-triangle"></i> Error:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= h($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="bi bi-check-circle"></i> <?= h($success) ?>
                            <br><a href="login.php" class="alert-link fw-bold">Click here to login →</a>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="registrationForm">
                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold"><i class="bi bi-shield-check"></i> Account Type *</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="user_type" id="type_student" value="student" checked>
                                    <label class="btn btn-outline-primary w-100 py-3" for="type_student">
                                        <i class="bi bi-person fs-4"></i><br><strong>Student</strong>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="user_type" id="type_instructor" value="instructor">
                                    <label class="btn btn-outline-primary w-100 py-3" for="type_instructor">
                                        <i class="bi bi-person-workspace fs-4"></i><br><strong>Instructor</strong>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label"><i class="bi bi-person"></i> First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?= h($_POST['first_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label"><i class="bi bi-person"></i> Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?= h($_POST['last_name'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label"><i class="bi bi-envelope"></i> Email *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= h($_POST['email'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label"><i class="bi bi-telephone"></i> Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?= h($_POST['phone'] ?? '') ?>">
                        </div>

                        <div id="instructor-fields" class="type-specific-fields mb-3">
                            <label for="department" class="form-label"><i class="bi bi-building"></i> Department *</label>
                            <input type="text" class="form-control" id="department" name="department" 
                                   value="<?= h($_POST['department'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label"><i class="bi bi-lock"></i> Password *</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   minlength="8" required>
                            <small class="text-muted"><i class="bi bi-info-circle"></i> Minimum 8 characters</small>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label"><i class="bi bi-lock-fill"></i> Confirm Password *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   minlength="8" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-person-plus"></i> Create Account
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div class="text-center">
                        <p class="mb-0">Already have an account?</p>
                        <a href="login.php" class="btn btn-link"><i class="bi bi-box-arrow-in-right"></i> Sign In</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.querySelectorAll('input[name="user_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'instructor') {
                document.getElementById('instructor-fields').classList.add('active');
                document.getElementById('department').setAttribute('required', 'required');
            } else {
                document.getElementById('instructor-fields').classList.remove('active');
                document.getElementById('department').removeAttribute('required');
            }
        });
    });

    document.getElementById('registrationForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match!');
        }
    });
    </script>
</body>
</html>
