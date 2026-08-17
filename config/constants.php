<?php
// C:\xampp\htdocs\school-erp\config\constants.php

if (!defined('BASE_URL')) {
    // Dynamically calculate the base directory path relative to document root
    $project_root = str_replace('\\', '/', dirname(__DIR__));
    $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    
    if (!empty($doc_root) && stripos($project_root, $doc_root) === 0) {
        $base_path = substr($project_root, strlen($doc_root));
        $base_url = '/' . trim($base_path, '/') . '/';
        $base_url = str_replace('//', '/', $base_url);
    } else {
        // Fallback for CLI or standard dev setup
        $base_url = '/school-erp/';
    }
    define('BASE_URL', $base_url);
}
define('APP_NAME', 'SchoolERP');
define('APP_VERSION', '1.0.0');

// Roles
define('ROLE_SUPER_ADMIN', 'SUPER_ADMIN');
define('ROLE_SCHOOL_ADMIN', 'SCHOOL_ADMIN');
define('ROLE_STUDENT', 'STUDENT');

// Upload Paths
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', BASE_URL . 'assets/uploads/');
