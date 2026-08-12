<?php
// C:\xampp\htdocs\school-erp\includes\csrf.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function getCSRFInput() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8') . '">';
}
