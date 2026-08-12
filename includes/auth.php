<?php
// C:\xampp\htdocs\school-erp\includes\auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/functions.php';

function checkAuthentication($requiredRole) {
    if (!isset($_SESSION['role'])) {
        // Redirect to appropriate login page based on role
        if ($requiredRole === ROLE_SUPER_ADMIN) {
            redirect('auth/admin-login.php');
        } elseif ($requiredRole === ROLE_SCHOOL_ADMIN) {
            redirect('auth/school-login.php');
        } elseif ($requiredRole === ROLE_STUDENT) {
            redirect('auth/student-login.php');
        }
        redirect('index.php');
    }
    
    if ($_SESSION['role'] !== $requiredRole) {
        // Forbidden
        http_response_code(403);
        die("Unauthorized access. You do not have permission to view this page.");
    }
    
    // Check school active status if not super admin
    if ($requiredRole !== ROLE_SUPER_ADMIN && isset($_SESSION['school_id'])) {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT status FROM schools WHERE id = ?");
        $stmt->execute([$_SESSION['school_id']]);
        $school = $stmt->fetch();
        
        if (!$school || $school['status'] !== 'approved') {
            session_destroy();
            redirect('index.php?error=' . urlencode("Your school account is currently inactive, suspended, or pending approval."));
        }
    }
}
