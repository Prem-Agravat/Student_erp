<?php
// C:\xampp\htdocs\school-erp\api\students.php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== ROLE_SCHOOL_ADMIN) {
    echo json_encode([]);
    exit;
}

$school_id = $_SESSION['school_id'];
$db = getDBConnection();

$action = $_GET['action'] ?? '';

if ($action === 'get_standard_subjects') {
    $standard_id = intval($_GET['standard_id'] ?? 0);
    
    if ($standard_id > 0) {
        try {
            $stmt = $db->prepare("
                SELECT sub.id, sub.name 
                FROM standard_subjects ss 
                JOIN subjects sub ON ss.subject_id = sub.id 
                WHERE ss.standard_id = ? AND ss.school_id = ? AND sub.status = 'active'
                ORDER BY sub.name ASC
            ");
            $stmt->execute([$standard_id, $school_id]);
            $subjects = $stmt->fetchAll();
            echo json_encode($subjects);
        } catch (PDOException $e) {
            echo json_encode([]);
        }
    } else {
        echo json_encode([]);
    }
    exit;
}

echo json_encode([]);
