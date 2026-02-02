<?php
/**
 * Logout Page
 * FIXED: Added auth.php include to resolve logoutUser() function error
 */

session_start();
require_once 'includes/functions.php';
require_once 'includes/auth.php'; // FIXED: Added this include

logoutUser();

header('Location: login.php?logout=1');
exit();