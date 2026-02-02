<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set base URL - CHANGE THIS TO YOUR PROJECT FOLDER NAME
$base_url = '/~NP03CS4A240013/FinalFSD/public';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>Course Management System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        body { min-height: 100vh; display: flex; flex-direction: column; background-color: #f8f9fa; }
        .container { flex: 1; }
        .navbar-brand { font-weight: bold; font-size: 1.3rem; }
        .card { box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); border: none; margin-bottom: 1.5rem; }
        .card-header { font-weight: 600; border-bottom: 2px solid rgba(0, 0, 0, 0.125); }
        .table-hover tbody tr:hover { background-color: rgba(0, 0, 0, 0.03); }
        .card.text-white h2 { font-size: 2.5rem; font-weight: bold; }
        .badge { font-weight: 500; padding: 0.35rem 0.65rem; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= $base_url ?>/index.php">
                <i class="bi bi-mortarboard-fill"></i> Course Management
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $base_url ?>/index.php">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-book"></i> Courses
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= $base_url ?>/courses/list.php">
                                <?= isset($_SESSION['role']) && $_SESSION['role'] === 'student' ? 'My Courses' : 'View All Courses' ?>
                            </a></li>
                            <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student'): ?>
                                <li><a class="dropdown-item" href="<?= $base_url ?>/courses/add.php">Add New Course</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="<?= $base_url ?>/courses/search.php">Search Courses</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-badge"></i> Instructors
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= $base_url ?>/instructors/list.php">
                                <?= isset($_SESSION['role']) && $_SESSION['role'] === 'student' ? 'My Instructors' : 'View All Instructors' ?>
                            </a></li>
                            <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student'): ?>
                                <li><a class="dropdown-item" href="<?= $base_url ?>/instructors/add.php">Add New Instructor</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-people"></i> Students
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= $base_url ?>/students/list.php">
                                <?= isset($_SESSION['role']) && $_SESSION['role'] === 'student' ? 'My Classmates' : 'View All Students' ?>
                            </a></li>
                            <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student'): ?>
                                <li><a class="dropdown-item" href="<?= $base_url ?>/students/add.php">Add New Student</a></li>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= $base_url ?>/students/edit.php?id=<?= $_SESSION['user_id'] ?>">
                                    <i class="bi bi-person-circle"></i> My Profile
                                </a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-card-checklist"></i> Enrollments
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?= $base_url ?>/enrollments/list.php">View Enrollments</a></li>
                                <li><a class="dropdown-item" href="<?= $base_url ?>/enrollments/enroll.php">Enroll Student</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
                <!-- User Profile Dropdown -->
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> 
                            <?= isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 
                                (isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text">
                                <strong>Role:</strong> <?= isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'N/A' ?>
                            </span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= $base_url ?>/../logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container mt-4">
        <?php 
        // Display flash messages
        if (isset($_SESSION['message'])) {
            $type = $_SESSION['message_type'] ?? 'success';
            $alertClass = $type === 'success' ? 'alert-success' : 'alert-danger';
            echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
            echo htmlspecialchars($_SESSION['message']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        ?>
