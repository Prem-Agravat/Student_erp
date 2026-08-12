<?php
// C:\xampp\htdocs\school-erp\config\constants.php

if (!defined('BASE_URL')) {
    define('BASE_URL', '/school-erp/');
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
