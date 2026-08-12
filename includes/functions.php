<?php
// C:\xampp\htdocs\school-erp\includes\functions.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect($path) {
    header("Location: " . BASE_URL . ltrim($path, '/'));
    exit;
}

function logActivity($action, $description, $school_id = null) {
    $db = getDBConnection();
    
    // Get IP address
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    // Check role and user_id based on session
    $role = null;
    $user_id = 0;
    
    if (isset($_SESSION['super_admin_id'])) {
        $role = ROLE_SUPER_ADMIN;
        $user_id = $_SESSION['super_admin_id'];
    } elseif (isset($_SESSION['school_admin_id'])) {
        $role = ROLE_SCHOOL_ADMIN;
        $user_id = $_SESSION['school_admin_id'];
        $school_id = $_SESSION['school_id'];
    } elseif (isset($_SESSION['student_id'])) {
        $role = ROLE_STUDENT;
        $user_id = $_SESSION['student_id'];
        $school_id = $_SESSION['school_id'];
    }
    
    if ($role !== null) {
        try {
            $stmt = $db->prepare("INSERT INTO activity_logs (school_id, user_id, role, action, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$school_id, $user_id, $role, $action, $description, $ip]);
        } catch (PDOException $e) {
            // Silently ignore log insertion errors to prevent app crash
        }
    }
}

function generateStudentID($school_code, $academic_year_name, $count) {
    // Expected output e.g., SCH001-2026-0001
    // Extract year part, e.g., "2025-26" -> "2026" or "2025"
    $year_parts = explode('-', $academic_year_name);
    $year = $year_parts[0] ?? date('Y');
    
    $student_num = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    return strtoupper($school_code) . '-' . $year . '-' . $student_num;
}

function calculateGrade($marks_obtained, $max_marks) {
    if ($max_marks <= 0) return 'F';
    $percentage = ($marks_obtained / $max_marks) * 100;
    
    if ($percentage >= 90) return 'A+';
    if ($percentage >= 80) return 'A';
    if ($percentage >= 70) return 'B+';
    if ($percentage >= 60) return 'B';
    if ($percentage >= 50) return 'C';
    if ($percentage >= 40) return 'D';
    return 'F';
}

function getResultStatus($grade) {
    return ($grade === 'F') ? 'Fail' : 'Pass';
}

function getAlert($type, $message) {
    return '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">' .
           htmlspecialchars($message) .
           '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}
