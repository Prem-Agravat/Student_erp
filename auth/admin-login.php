<?php
// C:\xampp\htdocs\school-erp\auth\admin-login.php

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF validation failed.");
    }
    
    $username_email = sanitizeInput($_POST['username_email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username_email) || empty($password)) {
        $error = "Please enter both credentials.";
    } else {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT * FROM super_admins WHERE username = ? OR email = ?");
        $stmt->execute([$username_email, $username_email]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['super_admin_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['role'] = ROLE_SUPER_ADMIN;
            
            logActivity("Super Admin Login", "Super Admin logged in.");
            redirect('admin/dashboard.php');
        } else {
            $error = "Invalid username/email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login - SchoolERP</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="text-center mb-4">
                    <a href="../index.php" class="h2 fw-bold text-decoration-none text-indigo"><i class="fa-solid fa-graduation-cap me-2"></i>SchoolERP</a>
                    <h4 class="mt-2 fw-bold">Platform Login</h4>
                    <p class="text-secondary">Super Admin access panel</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="glass-card p-4">
                    <?= getCSRFInput() ?>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Username or Email</label>
                        <input type="text" name="username_email" class="form-control rounded-3" required placeholder="e.g. admin">
                    </div>
                    <div class="mb-4">
                        <label class="form-label font-semibold">Password</label>
                        <input type="password" name="password" class="form-control rounded-3" required placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn btn-indigo w-100 rounded-pill py-2.5">Login as Super Admin</button>
                    <div class="text-center mt-3">
                        <a href="../index.php" class="text-decoration-none text-indigo" style="font-size: 14px;"><i class="fa-solid fa-arrow-left me-1"></i>Back to Home</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
