<?php
// C:\xampp\htdocs\school-erp\includes\header.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/sidebar.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Active page determination helper
if (!isset($activePage)) {
    $activePage = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME) ?> - Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <!-- ChartJS if needed -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <div class="dashboard-wrapper">
        <!-- Render Sidebar -->
        <?php renderSidebar($activePage); ?>
        
        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Top Navbar inside main content -->
            <nav class="navbar navbar-expand navbar-light bg-white rounded-3 shadow-sm mb-4 px-4 py-3">
                <div class="container-fluid p-0">
                    <button type="button" id="sidebarCollapse" class="btn btn-indigo d-lg-none">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    
                    <div class="ms-auto d-flex align-items-center gap-3">
                        <div class="dropdown">
                            <a class="text-decoration-none text-dark fw-bold dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fa-solid fa-circle-user fa-lg me-1 text-indigo"></i>
                                <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                                <?php if ($_SESSION['role'] === ROLE_SCHOOL_ADMIN): ?>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>school/settings.php"><i class="fa-solid fa-gears me-2 text-muted"></i>Settings</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>auth/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Dynamic Page Content Starts Here -->
