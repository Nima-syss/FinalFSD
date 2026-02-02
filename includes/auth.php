<?php
/**
 * Authentication Middleware - FIXED VERSION
 * Include this at the top of protected pages
 * 
 * FIXES:
 * - Dynamic redirect paths that work from any directory level
 * - Improved security checks
 * - Better session handling
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get the correct login URL based on current file location
 * This dynamically calculates the path to login.php
 */
function getLoginUrl() {
    // Check if we're in a subdirectory
    $currentPath = $_SERVER['PHP_SELF'];
    $pathParts = explode('/', trim($currentPath, '/'));
    
    // Count how many levels deep we are from the root
    $depth = count($pathParts) - 1;
    
    // If we're in /public/ or deeper, go up one level
    if (in_array('public', $pathParts)) {
        return '../login.php';
    }
    
    // If already at root level
    return 'login.php';
}

/**
 * Get the correct dashboard URL
 */
function getDashboardUrl() {
    $currentPath = $_SERVER['PHP_SELF'];
    $pathParts = explode('/', trim($currentPath, '/'));
    
    // If we're in a subdirectory of public (like /public/courses/)
    if (in_array('public', $pathParts)) {
        $position = array_search('public', $pathParts);
        $levelsDeep = count($pathParts) - $position - 1;
        
        if ($levelsDeep > 0) {
            return '../index.php';
        } else {
            return 'index.php';
        }
    }
    
    return 'public/index.php';
}

/**
 * Check if user is logged in
 * Redirects to login page if not authenticated
 */
function checkAuth() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        // Store the attempted URL for redirect after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        $loginUrl = getLoginUrl();
        header("Location: $loginUrl");
        exit();
    }
    
    // Optional: Check if session is still valid (session timeout)
    // if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
    //     // Session expired after 1 hour of inactivity
    //     session_unset();
    //     session_destroy();
    //     header("Location: " . getLoginUrl() . "?timeout=1");
    //     exit();
    // }
    // $_SESSION['last_activity'] = time();
}

/**
 * Check if user has required role
 * @param array $allowedRoles Array of allowed roles (e.g., ['admin', 'instructor'])
 */
function checkRole($allowedRoles = []) {
    // First check if user is authenticated
    checkAuth();
    
    // If no specific roles required, just being authenticated is enough
    if (empty($allowedRoles)) {
        return true;
    }
    
    $userRole = $_SESSION['role'] ?? 'student';
    
    // Check if user's role is in the allowed roles
    if (!in_array($userRole, $allowedRoles)) {
        // Redirect to dashboard with error message
        $_SESSION['message'] = "You don't have permission to access that page.";
        $_SESSION['message_type'] = 'error';
        
        $dashboardUrl = getDashboardUrl();
        header("Location: $dashboardUrl");
        exit();
    }
    
    return true;
}

/**
 * Get current user info
 * @return array User information from session
 */
function getCurrentUser() {
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ];
}

/**
 * Check if user is admin
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if user is instructor
 * @return bool
 */
function isInstructor() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'instructor';
}

/**
 * Check if user is student
 * @return bool
 */
function isStudent() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

/**
 * Require admin access (helper function)
 * Use this at the top of admin-only pages
 */
function requireAdmin() {
    checkRole(['admin']);
}

/**
 * Require instructor or admin access (helper function)
 */
function requireInstructor() {
    checkRole(['admin', 'instructor']);
}

/**
 * Check if user is logged in (without redirect)
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get user's role
 * @return string|null
 */
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Logout current user
 * Clears all session data and redirects to login
 */
function logoutUser() {
    // Unset all session variables
    $_SESSION = [];
    
    // Delete the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login
    header("Location: " . getLoginUrl());
    exit();
}