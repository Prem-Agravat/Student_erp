<?php
// C:\xampp\htdocs\school-erp\auth\student-login.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === ROLE_SUPER_ADMIN) redirect('admin/dashboard.php');
    if ($_SESSION['role'] === ROLE_SCHOOL_ADMIN) redirect('school/dashboard.php');
    if ($_SESSION['role'] === ROLE_STUDENT) redirect('student/dashboard.php');
}

$error = '';
$db = getDBConnection();

// Fetch approved schools for selector
$stmtSchools = $db->query("SELECT id, school_name, school_code, city FROM schools WHERE status = 'approved' ORDER BY school_name ASC");
$approvedSchools = $stmtSchools->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    
    $school_id = intval($_POST['school_id'] ?? 0);
    $username_email = sanitizeInput($_POST['username_email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($school_id <= 0 || empty($username_email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Find school and verify approved
        $stmtSch = $db->prepare("SELECT id, status, school_name FROM schools WHERE id = ?");
        $stmtSch->execute([$school_id]);
        $school = $stmtSch->fetch();
        
        if (!$school || $school['status'] !== 'approved') {
            $error = "Selected school is inactive or suspended.";
        } else {
            // Find student in school
            $stmtStudent = $db->prepare("SELECT * FROM students WHERE school_id = ? AND (username = ? OR email = ?)");
            $stmtStudent->execute([$school_id, $username_email, $username_email]);
            $student = $stmtStudent->fetch();
            
            if ($student && password_verify($password, $student['password_hash'])) {
                if ($student['status'] === 'active') {
                    session_regenerate_id(true);
                    $_SESSION['student_id'] = $student['id'];
                    $_SESSION['school_id'] = $student['school_id'];
                    $_SESSION['username'] = $student['username'];
                    $_SESSION['student_name'] = $student['first_name'] . ' ' . $student['last_name'];
                    $_SESSION['school_name'] = $school['school_name'];
                    $_SESSION['role'] = ROLE_STUDENT;
                    
                    logActivity("Student Login", "Student logged in.", $school_id);
                    redirect('student/dashboard.php');
                } else {
                    $error = "Your student account is currently inactive or archived.";
                }
            } else {
                $error = "Invalid school, username/email or password.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - SchoolERP</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .searchable-dropdown {
            position: relative;
        }
        .searchable-menu {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1000;
            display: none;
            max-height: 250px;
            overflow-y: auto;
            background: white;
            border: 1px solid #ced4da;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .searchable-menu.show {
            display: block;
        }
        .searchable-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
        }
        .searchable-item:hover {
            background-color: #f8fafc;
            color: #4f46e5;
        }
    </style>
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="text-center mb-4">
                    <a href="../index.php" class="h2 fw-bold text-decoration-none text-indigo"><i class="fa-solid fa-graduation-cap me-2"></i>SchoolERP</a>
                    <h4 class="mt-2 fw-bold">Student Portal Login</h4>
                    <p class="text-secondary">Enter credentials and select your school to access dashboard</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="glass-card p-4">
                    <?= getCSRFInput() ?>
                    
                    <!-- Searchable School Selector -->
                    <div class="mb-3 searchable-dropdown">
                        <label class="form-label font-semibold">Select Your School <span class="text-danger">*</span></label>
                        <input type="text" id="schoolSearchInput" class="form-control rounded-3" placeholder="Type school name to search..." autocomplete="off">
                        <input type="hidden" name="school_id" id="selectedSchoolId" required>
                        
                        <div class="searchable-menu" id="schoolMenu">
                            <?php if (empty($approvedSchools)): ?>
                                <div class="p-3 text-muted text-center">No active schools registered yet.</div>
                            <?php else: ?>
                                <?php foreach ($approvedSchools as $sch): ?>
                                    <div class="searchable-item" data-id="<?= $sch['id'] ?>" data-search="<?= strtolower($sch['school_name'] . ' ' . $sch['school_code'] . ' ' . $sch['city']) ?>">
                                        <div class="fw-bold"><?= htmlspecialchars($sch['school_name']) ?></div>
                                        <small class="text-muted">Code: <code><?= htmlspecialchars($sch['school_code']) ?></code> — <?= htmlspecialchars($sch['city']) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label font-semibold">Portal Username or Email <span class="text-danger">*</span></label>
                        <input type="text" name="username_email" class="form-control rounded-3" required placeholder="e.g. abc0010001">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label font-semibold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control rounded-3" required placeholder="••••••••">
                    </div>
                    
                    <button type="submit" class="btn btn-indigo w-100 rounded-pill py-2.5">Access Dashboard</button>
                    <div class="text-center mt-3" style="font-size: 14px;">
                        <a href="../index.php" class="text-decoration-none text-indigo"><i class="fa-solid fa-arrow-left me-1"></i>Back to Homepage</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Searchable selector script -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const searchInput = document.getElementById("schoolSearchInput");
        const hiddenInput = document.getElementById("selectedSchoolId");
        const menu = document.getElementById("schoolMenu");
        const items = document.querySelectorAll(".searchable-item");
        
        searchInput.addEventListener("focus", function() {
            menu.classList.add("show");
        });
        
        searchInput.addEventListener("input", function() {
            const query = searchInput.value.toLowerCase().trim();
            items.forEach(item => {
                const searchContent = item.getAttribute("data-search");
                if (searchContent.includes(query)) {
                    item.style.display = "block";
                } else {
                    item.style.display = "none";
                }
            });
        });
        
        items.forEach(item => {
            item.addEventListener("click", function() {
                const name = item.querySelector(".fw-bold").textContent;
                const id = item.getAttribute("data-id");
                
                searchInput.value = name;
                hiddenInput.value = id;
                menu.classList.remove("show");
            });
        });
        
        // Close dropdown when clicking outside
        document.addEventListener("click", function(e) {
            if (!searchInput.contains(e.target) && !menu.contains(e.target)) {
                menu.classList.remove("show");
            }
        });
    });
    </script>
</body>
</html>
