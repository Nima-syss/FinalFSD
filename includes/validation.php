<?php
/**
 * Validation Functions for Course Management System
 * Server-side validation for all forms
 */

/**
 * Validate course data
 */
function validateCourse($data, $pdo, $excludeId = null) {
    $errors = [];
    
    // Course name
    if (empty($data['course_name'])) {
        $errors[] = "Course name is required";
    } elseif (strlen($data['course_name']) < 3) {
        $errors[] = "Course name must be at least 3 characters";
    } elseif (strlen($data['course_name']) > 200) {
        $errors[] = "Course name must not exceed 200 characters";
    }
    
    // Course code
    if (empty($data['course_code'])) {
        $errors[] = "Course code is required";
    } elseif (!preg_match('/^[A-Z]{2,4}[0-9]{3}$/i', $data['course_code'])) {
        $errors[] = "Course code must be in format: CS101, MATH201, etc.";
    } elseif (courseCodeExists($pdo, $data['course_code'], $excludeId)) {
        $errors[] = "Course code already exists";
    }
    
    // Category
    if (empty($data['category'])) {
        $errors[] = "Category is required";
    }
    
    // Level
    $validLevels = ['Beginner', 'Intermediate', 'Advanced'];
    if (!in_array($data['level'] ?? '', $validLevels)) {
        $errors[] = "Invalid course level";
    }
    
    // Credits
    $credits = intval($data['credits'] ?? 0);
    if ($credits < 1 || $credits > 6) {
        $errors[] = "Credits must be between 1 and 6";
    }
    
    // Max students
    $maxStudents = intval($data['max_students'] ?? 0);
    if ($maxStudents < 1 || $maxStudents > 200) {
        $errors[] = "Max students must be between 1 and 200";
    }
    
    return $errors;
}

/**
 * Validate instructor data
 */
function validateInstructor($data, $pdo, $excludeId = null) {
    $errors = [];
    
    // First name
    if (empty($data['first_name'])) {
        $errors[] = "First name is required";
    } elseif (strlen($data['first_name']) < 2) {
        $errors[] = "First name must be at least 2 characters";
    } elseif (!preg_match('/^[a-zA-Z\s\-\']+$/', $data['first_name'])) {
        $errors[] = "First name can only contain letters, spaces, hyphens, and apostrophes";
    }
    
    // Last name
    if (empty($data['last_name'])) {
        $errors[] = "Last name is required";
    } elseif (strlen($data['last_name']) < 2) {
        $errors[] = "Last name must be at least 2 characters";
    } elseif (!preg_match('/^[a-zA-Z\s\-\']+$/', $data['last_name'])) {
        $errors[] = "Last name can only contain letters, spaces, hyphens, and apostrophes";
    }
    
    // Email
    if (empty($data['email'])) {
        $errors[] = "Email is required";
    } elseif (!isValidEmail($data['email'])) {
        $errors[] = "Invalid email format";
    } elseif (instructorEmailExists($pdo, $data['email'], $excludeId)) {
        $errors[] = "Email already exists";
    }
    
    // Phone (optional but validate if provided)
    if (!empty($data['phone'])) {
        $phone = preg_replace('/[^0-9]/', '', $data['phone']);
        if (strlen($phone) < 10) {
            $errors[] = "Phone number must contain at least 10 digits";
        }
    }
    
    // Department
    if (empty($data['department'])) {
        $errors[] = "Department is required";
    }
    
    // Specialization
    if (empty($data['specialization'])) {
        $errors[] = "Specialization is required";
    }
    
    return $errors;
}

/**
 * Validate student data
 */
function validateStudent($data, $pdo, $excludeId = null) {
    $errors = [];
    
    // First name
    if (empty($data['first_name'])) {
        $errors[] = "First name is required";
    } elseif (strlen($data['first_name']) < 2) {
        $errors[] = "First name must be at least 2 characters";
    } elseif (!preg_match('/^[a-zA-Z\s\-\']+$/', $data['first_name'])) {
        $errors[] = "First name can only contain letters, spaces, hyphens, and apostrophes";
    }
    
    // Last name
    if (empty($data['last_name'])) {
        $errors[] = "Last name is required";
    } elseif (strlen($data['last_name']) < 2) {
        $errors[] = "Last name must be at least 2 characters";
    } elseif (!preg_match('/^[a-zA-Z\s\-\']+$/', $data['last_name'])) {
        $errors[] = "Last name can only contain letters, spaces, hyphens, and apostrophes";
    }
    
    // Email
    if (empty($data['email'])) {
        $errors[] = "Email is required";
    } elseif (!isValidEmail($data['email'])) {
        $errors[] = "Invalid email format";
    } elseif (emailExists($pdo, $data['email'], $excludeId)) {
        $errors[] = "Email already exists";
    }
    
    // Phone (optional but validate if provided)
    if (!empty($data['phone'])) {
        $phone = preg_replace('/[^0-9]/', '', $data['phone']);
        if (strlen($phone) < 10) {
            $errors[] = "Phone number must contain at least 10 digits";
        }
    }
    
    // Enrollment date (optional but validate if provided)
    if (!empty($data['enrollment_date'])) {
        $date = strtotime($data['enrollment_date']);
        if (!$date) {
            $errors[] = "Invalid enrollment date";
        } elseif ($date > time()) {
            $errors[] = "Enrollment date cannot be in the future";
        }
    }
    
    // Status
    $validStatuses = ['Active', 'Inactive', 'Graduated'];
    if (isset($data['status']) && !in_array($data['status'], $validStatuses)) {
        $errors[] = "Invalid student status";
    }
    
    return $errors;
}

/**
 * Validate enrollment data
 */
function validateEnrollment($data, $pdo) {
    $errors = [];
    
    $studentId = intval($data['student_id'] ?? 0);
    $courseId = intval($data['course_id'] ?? 0);
    
    // Student ID
    if ($studentId <= 0) {
        $errors[] = "Please select a student";
    } else {
        // Check if student exists
        $student = getOne($pdo, "SELECT student_id FROM students WHERE student_id = ?", [$studentId]);
        if (!$student) {
            $errors[] = "Selected student does not exist";
        }
    }
    
    // Course ID
    if ($courseId <= 0) {
        $errors[] = "Please select a course";
    } else {
        // Check if course exists
        $course = getOne($pdo, "SELECT course_id FROM courses WHERE course_id = ?", [$courseId]);
        if (!$course) {
            $errors[] = "Selected course does not exist";
        }
    }
    
    // Check if already enrolled
    if ($studentId > 0 && $courseId > 0) {
        if (isAlreadyEnrolled($pdo, $courseId, $studentId)) {
            $errors[] = "Student is already enrolled in this course";
        }
        
        // Check course capacity
        $availableSlots = getAvailableSlots($pdo, $courseId);
        if ($availableSlots <= 0) {
            $errors[] = "Course is full - no available slots";
        }
    }
    
    // Enrollment date
    if (!empty($data['enrollment_date'])) {
        $date = strtotime($data['enrollment_date']);
        if (!$date) {
            $errors[] = "Invalid enrollment date";
        }
    }
    
    return $errors;
}

/**
 * Sanitize string input
 */
function sanitizeString($input) {
    return trim(strip_tags($input));
}

/**
 * Sanitize integer input
 */
function sanitizeInt($input) {
    return intval($input);
}

/**
 * Sanitize email input
 */
function sanitizeEmail($input) {
    return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
}

/**
 * Validate file upload (for future file upload features)
 */
function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'], $maxSize = 5242880) {
    $errors = [];
    
    // Check if file was uploaded
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['No file uploaded'];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['File upload error: ' . $file['error']];
    }
    
    // Check file size (default 5MB)
    if ($file['size'] > $maxSize) {
        $errors[] = "File size must not exceed " . ($maxSize / 1048576) . "MB";
    }
    
    // Check file type
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedTypes)) {
        $errors[] = "Invalid file type. Allowed types: " . implode(', ', $allowedTypes);
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedMimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'pdf' => 'application/pdf'
    ];
    
    if (isset($allowedMimes[$fileExtension]) && $mimeType !== $allowedMimes[$fileExtension]) {
        $errors[] = "File content does not match extension";
    }
    
    return $errors;
}

/**
 * Validate date format
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate phone number
 */
function validatePhone($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it has at least 10 digits
    if (strlen($phone) < 10) {
        return false;
    }
    
    return true;
}

/**
 * Validate URL
 */
function validateURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Check password strength (for future authentication features)
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return $errors;
}