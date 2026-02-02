<?php
/**
 * Helper Functions for Course Management System - ENHANCED
 * Added: Password hashing, authentication, session management
 * 
 * NOTE: Core authentication functions (isLoggedIn, logoutUser, checkAuth, etc.)
 * are now in auth.php. This file automatically includes auth.php.
 * 
 * FIXED: Added require_once for auth.php to resolve function dependencies
 */

// Include authentication functions (REQUIRED for requireLogin, requireUserType, etc.)
require_once __DIR__ . '/auth.php';

// ============================================
// PASSWORD HASHING FUNCTIONS (NEW)
// ============================================

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function needsRehash($hash) {
    return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
}

// ============================================
// AUTHENTICATION FUNCTIONS (NEW)
// ============================================

function authenticateInstructor($pdo, $email, $password) {
    try {
        $sql = "SELECT * FROM instructors WHERE email = ? AND is_active = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $instructor = $stmt->fetch();
        
        if ($instructor && verifyPassword($password, $instructor['password'])) {
            $updateSql = "UPDATE instructors SET last_login = NOW() WHERE instructor_id = ?";
            $pdo->prepare($updateSql)->execute([$instructor['instructor_id']]);
            unset($instructor['password']);
            return $instructor;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Instructor auth error: " . $e->getMessage());
        return false;
    }
}

function authenticateStudent($pdo, $email, $password) {
    try {
        $sql = "SELECT * FROM students WHERE email = ? AND is_active = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $student = $stmt->fetch();
        
        if ($student && verifyPassword($password, $student['password'])) {
            $updateSql = "UPDATE students SET last_login = NOW() WHERE student_id = ?";
            $pdo->prepare($updateSql)->execute([$student['student_id']]);
            unset($student['password']);
            return $student;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Student auth error: " . $e->getMessage());
        return false;
    }
}

function loginUser($userData, $userType) {
    session_regenerate_id(true);
    $_SESSION['user_type'] = $userType;
    $_SESSION['logged_in'] = true;
    $_SESSION['last_activity'] = time();
    regenerateCSRFToken();
    
    if ($userType === 'instructor') {
        $_SESSION['instructor_id'] = $userData['instructor_id'];
        $_SESSION['user_id'] = $userData['instructor_id'];
        $_SESSION['user_name'] = $userData['first_name'] . ' ' . $userData['last_name'];
        $_SESSION['user_email'] = $userData['email'];
        $_SESSION['department'] = $userData['department'] ?? '';
        $_SESSION['role'] = 'instructor';
    } elseif ($userType === 'student') {
        $_SESSION['student_id'] = $userData['student_id'];
        $_SESSION['user_id'] = $userData['student_id'];
        $_SESSION['user_name'] = $userData['first_name'] . ' ' . $userData['last_name'];
        $_SESSION['user_email'] = $userData['email'];
        $_SESSION['role'] = 'student';
    }
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ../../login.php');
        exit();
    }
}

// ============================================
// ROLE-BASED ACCESS CONTROL HELPERS
// ============================================

/**
 * Check if current user is a student
 */
function isStudentRole() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

/**
 * Check if current user is an instructor
 */
function isInstructorRole() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'instructor';
}

/**
 * Get student-filtered courses (only enrolled courses)
 */
function getStudentCourses($pdo, $studentId) {
    $sql = "SELECT c.*, 
            CONCAT(i.first_name, ' ', i.last_name) as instructor_name,
            (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.course_id AND e.status = 'Active') as enrolled_count,
            e.status as enrollment_status,
            e.grade,
            e.enrollment_date,
            e.enrollment_id
            FROM courses c
            LEFT JOIN instructors i ON c.instructor_id = i.instructor_id
            INNER JOIN enrollments e ON c.course_id = e.course_id
            WHERE e.student_id = ?
            ORDER BY e.status = 'Active' DESC, c.course_name";
    
    return getAll($pdo, $sql, [$studentId]);
}

/**
 * Get student-filtered instructors (only teaching student's courses)
 */
function getStudentInstructors($pdo, $studentId) {
    $sql = "SELECT DISTINCT i.*,
            (SELECT COUNT(*) FROM courses c WHERE c.instructor_id = i.instructor_id) as course_count,
            (SELECT COUNT(*) FROM enrollments e 
             JOIN courses c ON e.course_id = c.course_id 
             WHERE c.instructor_id = i.instructor_id AND e.student_id = ?) as my_courses_count
            FROM instructors i
            INNER JOIN courses c ON i.instructor_id = c.instructor_id
            INNER JOIN enrollments e ON c.course_id = e.course_id
            WHERE e.student_id = ?
            ORDER BY i.first_name, i.last_name";
    
    return getAll($pdo, $sql, [$studentId, $studentId]);
}

/**
 * Get student-filtered students (only classmates)
 */
function getStudentClassmates($pdo, $studentId) {
    $sql = "SELECT DISTINCT s.student_id, s.first_name, s.last_name, s.email, s.phone, s.enrollment_date,
            (SELECT COUNT(*) FROM enrollments e WHERE e.student_id = s.student_id AND e.status = 'Active') as course_count,
            (SELECT COUNT(DISTINCT e2.course_id) 
             FROM enrollments e1
             JOIN enrollments e2 ON e1.course_id = e2.course_id
             WHERE e1.student_id = ? AND e2.student_id = s.student_id AND e2.status = 'Active') as shared_courses
            FROM students s
            INNER JOIN enrollments e ON s.student_id = e.student_id
            WHERE e.course_id IN (
                SELECT course_id FROM enrollments WHERE student_id = ? AND status = 'Active'
            )
            AND s.student_id != ?
            AND e.status = 'Active'
            ORDER BY s.first_name, s.last_name";
    
    return getAll($pdo, $sql, [$studentId, $studentId, $studentId]);
}

/**
 * Check if student can access a specific course
 */
function studentCanAccessCourse($pdo, $studentId, $courseId) {
    $sql = "SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND course_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$studentId, $courseId]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Check if student can access a specific instructor
 */
function studentCanAccessInstructor($pdo, $studentId, $instructorId) {
    $sql = "SELECT COUNT(*) FROM enrollments e
            JOIN courses c ON e.course_id = c.course_id
            WHERE e.student_id = ? AND c.instructor_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$studentId, $instructorId]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Check if student can access another student (same course)
 */
function studentCanAccessStudent($pdo, $studentId, $targetStudentId) {
    if ($studentId == $targetStudentId) return true; // Can access own profile
    
    $sql = "SELECT COUNT(*) FROM enrollments e1
            JOIN enrollments e2 ON e1.course_id = e2.course_id
            WHERE e1.student_id = ? AND e2.student_id = ? AND e1.status = 'Active' AND e2.status = 'Active'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$studentId, $targetStudentId]);
    return $stmt->fetchColumn() > 0;
}


function requireUserType($allowedTypes) {
    if (!isLoggedIn()) {
        header('Location: ../../login.php');
        exit();
    }
    
    if (!is_array($allowedTypes)) {
        $allowedTypes = [$allowedTypes];
    }
    
    if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], $allowedTypes)) {
        http_response_code(403);
        die('Access Denied: You do not have permission to access this page.');
    }
}

function checkSessionTimeout($timeout = 1800) {
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > $timeout) {
            logoutUser();
            return false;
        }
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 900) {
    $key = 'login_attempts_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time()];
    }
    
    $data = $_SESSION[$key];
    
    if (time() - $data['first_attempt'] > $timeWindow) {
        $_SESSION[$key] = ['attempts' => 1, 'first_attempt' => time()];
        return true;
    }
    
    if ($data['attempts'] >= $maxAttempts) {
        return false;
    }
    
    $_SESSION[$key]['attempts']++;
    return true;
}

function getRateLimitRemainingTime($identifier, $timeWindow = 900) {
    $key = 'login_attempts_' . md5($identifier);
    if (!isset($_SESSION[$key])) {
        return 0;
    }
    $elapsed = time() - $_SESSION[$key]['first_attempt'];
    return max(0, $timeWindow - $elapsed);
}

function resetRateLimit($identifier) {
    $key = 'login_attempts_' . md5($identifier);
    unset($_SESSION[$key]);
}

// ============================================
// ORIGINAL FUNCTIONS
// ============================================

function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function setMessage($message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

function displayMessage() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'success';
        $alertClass = $type === 'success' ? 'alert-success' : 'alert-danger';
        echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
        echo h($_SESSION['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function emailExists($pdo, $email, $excludeId = null) {
    $sql = "SELECT COUNT(*) FROM students WHERE email = ?";
    $params = [$email];
    if ($excludeId) {
        $sql .= " AND student_id != ?";
        $params[] = $excludeId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

function instructorEmailExists($pdo, $email, $excludeId = null) {
    $sql = "SELECT COUNT(*) FROM instructors WHERE email = ?";
    $params = [$email];
    if ($excludeId) {
        $sql .= " AND instructor_id != ?";
        $params[] = $excludeId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

function courseCodeExists($pdo, $code, $excludeId = null) {
    $sql = "SELECT COUNT(*) FROM courses WHERE course_code = ?";
    $params = [$code];
    if ($excludeId) {
        $sql .= " AND course_id != ?";
        $params[] = $excludeId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

function isAlreadyEnrolled($pdo, $courseId, $studentId) {
    $sql = "SELECT COUNT(*) FROM enrollments WHERE course_id = ? AND student_id = ? AND status = 'Active'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$courseId, $studentId]);
    return $stmt->fetchColumn() > 0;
}

function getAvailableSlots($pdo, $courseId) {
    $sql = "SELECT max_students, 
            (SELECT COUNT(*) FROM enrollments WHERE course_id = ? AND status = 'Active') as enrolled
            FROM courses WHERE course_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$courseId, $courseId]);
    $result = $stmt->fetch();
    
    if ($result) {
        return $result['max_students'] - $result['enrolled'];
    }
    return 0;
}

function formatDate($date) {
    if (empty($date)) return '';
    return date('M d, Y', strtotime($date));
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function regenerateCSRFToken() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function getAllInstructors($pdo) {
    $sql = "SELECT instructor_id, CONCAT(first_name, ' ', last_name) as name 
            FROM instructors WHERE is_active = 1 ORDER BY first_name, last_name";
    return getAll($pdo, $sql);
}

function getAllStudents($pdo) {
    $sql = "SELECT student_id, CONCAT(first_name, ' ', last_name) as name, email 
            FROM students WHERE is_active = 1 ORDER BY first_name, last_name";
    return getAll($pdo, $sql);
}

function getCategories() {
    return [
        'Web Development', 'Data Science', 'Database', 'Mobile Development', 'Programming',
        'Project Management', 'Cloud Computing', 'Cybersecurity', 'Networking', 'Software Engineering'
    ];
}

function getLevels() {
    return ['Beginner', 'Intermediate', 'Advanced'];
}

function getEnrollmentStatuses() {
    return ['Active', 'Completed', 'Dropped'];
}

function sanitizeSearch($input) {
    return trim(strip_tags($input));
}

function getOne($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

function getAll($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function insert($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $pdo->lastInsertId();
}

function execute($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}