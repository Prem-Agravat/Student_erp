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
    // Activity logging has been disabled
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

function getOrInsertDefaultSectionId($db, $school_id, $standard_id) {
    // Check if a section already exists for this standard
    $stmt = $db->prepare("SELECT id FROM sections WHERE school_id = ? AND standard_id = ? ORDER BY id ASC LIMIT 1");
    $stmt->execute([$school_id, $standard_id]);
    $section = $stmt->fetch();
    
    if ($section) {
        return intval($section['id']);
    }
    
    // If not, insert a default section 'A'
    $stmtIns = $db->prepare("INSERT INTO sections (school_id, standard_id, name, capacity, status) VALUES (?, ?, 'A', 100, 'active')");
    $stmtIns->execute([$school_id, $standard_id]);
    return intval($db->lastInsertId());
}
