<?php
/**
 * Login Page - ENHANCED with password hashing and role-based authentication
 * FIXED: Added auth.php include to resolve isLoggedIn() function error
 */

session_start();

if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php'; // FIXED: Added this include

// Check if already logged in
if (isLoggedIn()) {
    if ($_SESSION['user_type'] === 'instructor') {
        header('Location: public/instructors/dashboard.php');
    } elseif ($_SESSION['user_type'] === 'student') {
        header('Location: public/students/dashboard.php');
    } else {
        header('Location: public/index.php');
    }
    exit();
}

$error = '';
$info = '';

if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    $info = "You have been successfully logged out.";
}

if (isset($_GET['timeout']) && $_GET['timeout'] == '1') {
    $error = "Your session has expired. Please login again.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($submittedToken)) {
        $error = "Invalid form submission. Please try again.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $userType = $_POST['user_type'] ?? 'student';
        
        if (empty($email) || empty($password)) {
            $error = "Please enter both email and password";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address";
        } else {
            if (!checkRateLimit($email)) {
                $remainingTime = getRateLimitRemainingTime($email);
                $minutesLeft = ceil($remainingTime / 60);
                $error = "Too many failed login attempts. Please try again in $minutesLeft minutes.";
            } else {
                try {
                    $userData = false;
                    
                    if ($userType === 'instructor') {
                        $userData = authenticateInstructor($pdo, $email, $password);
                    } elseif ($userType === 'student') {
                        $userData = authenticateStudent($pdo, $email, $password);
                    }
                    
                    if ($userData) {
                        resetRateLimit($email);
                        loginUser($userData, $userType);
                        
                        if ($userType === 'instructor') {
                            header('Location: public/instructors/dashboard.php');
                        } else {
                            header('Location: public/students/dashboard.php');
                        }
                        exit();
                    } else {
                        $error = "Invalid email or password";
                        error_log("Failed login attempt for: $email as $userType");
                    }
                } catch (Exception $e) {
                    error_log("Login Error: " . $e->getMessage());
                    $error = "An error occurred. Please try again.";
                }
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
    <title>Login - Course Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 450px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card mx-auto">
            <div class="card">
                <div class="card-header text-center">
                    <h3 class="mb-0"><i class="bi bi-mortarboard-fill"></i><br>Course Management</h3>
                    <p class="mb-0 mt-2">Sign in to continue</p>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> <?= h($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($info): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="bi bi-info-circle"></i> <?= h($info) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="bi bi-person-badge"></i> Login as:</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="user_type" id="type_student" value="student" 
                                           <?= (!isset($_POST['user_type']) || $_POST['user_type'] == 'student') ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-primary w-100" for="type_student">
                                        <i class="bi bi-person"></i><br><small>Student</small>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="user_type" id="type_instructor" value="instructor"
                                           <?= (isset($_POST['user_type']) && $_POST['user_type'] == 'instructor') ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-primary w-100" for="type_instructor">
                                        <i class="bi bi-person-workspace"></i><br><small>Instructor</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label"><i class="bi bi-envelope"></i> Email Address</label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                   value="<?= h($_POST['email'] ?? '') ?>" placeholder="Enter your email" required autofocus>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label"><i class="bi bi-lock"></i> Password</label>
                            <input type="password" class="form-control form-control-lg" id="password" name="password" 
                                   placeholder="Enter password" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right"></i> Sign In
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div class="text-center">
                        <p class="mb-0">Don't have an account?</p>
                        <a href="register.php" class="btn btn-link"><i class="bi bi-person-plus"></i> Create Account</a>
                    </div>

                    <div class="alert alert-light mt-3 text-center">
                        <small class="text-muted">
                            <strong><i class="bi bi-key"></i> Demo Credentials (password: password123):</strong><br>
                            <strong>Instructor:</strong> john.smith@university.edu<br>
                            <strong>Student:</strong> emma.wilson@student.edu
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>