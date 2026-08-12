<?php
// C:\xampp\htdocs\school-erp\api\marks.php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security validations
if (!isset($_SESSION['role']) || $_SESSION['role'] !== ROLE_SCHOOL_ADMIN) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit;
}

$school_id = $_SESSION['school_id'];
$db = getDBConnection();

$action = $_GET['action'] ?? '';

if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed.']);
        exit;
    }
    
    $exam_id = intval($_POST['exam_id'] ?? 0);
    $student_id = intval($_POST['student_id'] ?? 0);
    $subject_id = intval($_POST['subject_id'] ?? 0);
    $marks_obtained = floatval($_POST['marks_obtained'] ?? 0);
    $max_marks = floatval($_POST['max_marks'] ?? 100);
    $remarks = sanitizeInput($_POST['remarks'] ?? '');
    
    // Ownership checks
    $stmtStu = $db->prepare("SELECT id FROM students WHERE id = ? AND school_id = ?");
    $stmtStu->execute([$student_id, $school_id]);
    if (!$stmtStu->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Student mismatch.']);
        exit;
    }
    
    $stmtEx = $db->prepare("SELECT id FROM exams WHERE id = ? AND school_id = ?");
    $stmtEx->execute([$exam_id, $school_id]);
    if (!$stmtEx->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Exam mismatch.']);
        exit;
    }
    
    $stmtSub = $db->prepare("SELECT id FROM subjects WHERE id = ? AND school_id = ?");
    $stmtSub->execute([$subject_id, $school_id]);
    if (!$stmtSub->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Subject mismatch.']);
        exit;
    }
    
    // Calculate grade
    $grade = calculateGrade($marks_obtained, $max_marks);
    $admin_id = $_SESSION['school_admin_id'];
    
    try {
        $stmtUpsert = $db->prepare("
            INSERT INTO marks (school_id, student_id, exam_id, subject_id, marks_obtained, max_marks, grade, remarks, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE marks_obtained = VALUES(marks_obtained), max_marks = VALUES(max_marks), grade = VALUES(grade), remarks = VALUES(remarks), created_by = VALUES(created_by)
        ");
        $stmtUpsert->execute([$school_id, $student_id, $exam_id, $subject_id, $marks_obtained, $max_marks, $grade, $remarks, $admin_id]);
        
        echo json_encode(['success' => true, 'grade' => $grade]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid endpoint action.']);
