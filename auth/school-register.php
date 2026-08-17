<?php
// C:\xampp\htdocs\school-erp\auth\school-register.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF verification
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    
    // School Details
    $school_name = sanitizeInput($_POST['school_name'] ?? '');
    $school_code = sanitizeInput($_POST['school_code'] ?? '');
    $school_email = sanitizeInput($_POST['school_email'] ?? '');
    $school_phone = sanitizeInput($_POST['school_phone'] ?? '');
    $school_address = sanitizeInput($_POST['school_address'] ?? '');
    $city = sanitizeInput($_POST['city'] ?? '');
    $state = sanitizeInput($_POST['state'] ?? '');
    $pincode = sanitizeInput($_POST['pincode'] ?? '');
    $website = sanitizeInput($_POST['website'] ?? '');
    
    // Principal Info
    $principal_name = sanitizeInput($_POST['principal_name'] ?? '');
    $principal_email = sanitizeInput($_POST['principal_email'] ?? '');
    $principal_phone = sanitizeInput($_POST['principal_phone'] ?? '');
    
    // School Admin Details
    $admin_name = sanitizeInput($_POST['admin_name'] ?? '');
    $admin_email = sanitizeInput($_POST['admin_email'] ?? '');
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Additional Info
    $board = sanitizeInput($_POST['board'] ?? 'CBSE');
    $medium = sanitizeInput($_POST['medium'] ?? '');
    $established_year = intval($_POST['established_year'] ?? 0);
    $school_type = sanitizeInput($_POST['school_type'] ?? 'Co-Ed');
    
    // Validations
    if (empty($school_name) || empty($school_code) || empty($school_email) || empty($admin_email) || empty($username) || empty($password)) {
        $message = "Please fill in all required fields.";
        $messageType = "danger";
    } elseif (!filter_var($school_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid school email address.";
        $messageType = "danger";
    } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid admin email address.";
        $messageType = "danger";
    } elseif (!empty($principal_email) && !filter_var($principal_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid principal email address.";
        $messageType = "danger";
    } elseif (!preg_match('/^[0-9+\-\s]{7,20}$/', $school_phone)) {
        $message = "School phone number must be between 7 and 20 digits/characters.";
        $messageType = "danger";
    } elseif (!preg_match('/^[0-9+\-\s]{7,20}$/', $principal_phone)) {
        $message = "Principal phone number must be between 7 and 20 digits/characters.";
        $messageType = "danger";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $messageType = "danger";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $messageType = "danger";
    } else {
        $db = getDBConnection();
        
        // Check if unique constraints are violated
        $stmt = $db->prepare("SELECT id FROM schools WHERE school_code = ? OR email = ?");
        $stmt->execute([$school_code, $school_email]);
        $existingSchool = $stmt->fetch();
        
        $stmt2 = $db->prepare("SELECT id FROM school_admins WHERE username = ? OR admin_email = ?");
        $stmt2->execute([$username, $admin_email]);
        $existingAdmin = $stmt2->fetch();
        
        if ($existingSchool) {
            $message = "A school with this code or email already exists.";
            $messageType = "danger";
        } elseif ($existingAdmin) {
            $message = "Admin username or email already exists.";
            $messageType = "danger";
        } else {
            // Handle logo upload
            $logo_name = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['logo']['tmp_name'];
                $fileName = $_FILES['logo']['name'];
                $fileSize = $_FILES['logo']['size'];
                $fileType = $_FILES['logo']['type'];
                
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));
                
                $allowedExtensions = ['jpg', 'jpeg', 'png'];
                
                if (in_array($fileExtension, $allowedExtensions)) {
                    if ($fileSize < 2 * 1024 * 1024) { // 2MB Limit
                        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                        $uploadFileDir = __DIR__ . '/../assets/uploads/logos/';
                        if (!is_dir($uploadFileDir)) {
                            mkdir($uploadFileDir, 0777, true);
                        }
                        $dest_path = $uploadFileDir . $newFileName;
                        
                        if (move_uploaded_file($fileTmpPath, $dest_path)) {
                            $logo_name = $newFileName;
                        }
                    } else {
                        $message = "Logo file size must be less than 2MB.";
                        $messageType = "danger";
                    }
                } else {
                    $message = "Only JPG, JPEG, and PNG images are allowed for school logo.";
                    $messageType = "danger";
                }
            }
            
            if ($messageType !== 'danger') {
                try {
                    $db->beginTransaction();
                    
                    // Insert School Record
                    $stmtSchool = $db->prepare("INSERT INTO schools (school_name, school_code, email, phone, address, city, state, pincode, website, logo, board, medium, established_year, school_type, principal_name, principal_email, principal_phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                    $stmtSchool->execute([
                        $school_name, $school_code, $school_email, $school_phone, $school_address,
                        $city, $state, $pincode, $website, $logo_name, $board, $medium,
                        $established_year, $school_type, $principal_name, $principal_email, $principal_phone
                    ]);
                    
                    $school_id = $db->lastInsertId();
                    
                    // Insert School Admin
                    $password_hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmtAdmin = $db->prepare("INSERT INTO school_admins (school_id, admin_name, admin_email, username, password_hash, status) VALUES (?, ?, ?, ?, ?, 'active')");
                    $stmtAdmin->execute([$school_id, $admin_name, $admin_email, $username, $password_hash]);
                    
                    $db->commit();
                    
                    // Log registration
                    logActivity("School Registration", "School $school_name ($school_code) registered.", $school_id);
                    
                    $message = "Your school registration has been submitted successfully. Your account will be activated after approval by the platform administrator.";
                    $messageType = "success";
                } catch (PDOException $e) {
                    $db->rollBack();
                    $message = "Registration failed: " . $e->getMessage();
                    $messageType = "danger";
                }
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
    <title>Register Your School - SchoolERP</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light py-5">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="text-center mb-4">
                    <a href="../index.php" class="h2 fw-bold text-decoration-none text-indigo"><i class="fa-solid fa-graduation-cap me-2"></i>SchoolERP</a>
                    <h3 class="mt-2 fw-bold">Register Your Institution</h3>
                    <p class="text-secondary">Register your school and begin digitalising your complete operations</p>
                </div>
                
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show shadow-sm" role="alert">
                        <?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($messageType !== 'success'): ?>
                    <form method="POST" enctype="multipart/form-data" class="glass-card p-5 mb-5">
                        <?= getCSRFInput() ?>
                        
                        <!-- Section 1: School Information -->
                        <h5 class="fw-bold mb-4 text-indigo border-bottom pb-2"><i class="fa-solid fa-school me-2"></i>School Information</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label font-semibold">School Name <span class="text-danger">*</span></label>
                                <input type="text" name="school_name" class="form-control rounded-3" required placeholder="e.g. ABC Public School">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold">School Code <span class="text-danger">*</span></label>
                                <input type="text" name="school_code" class="form-control rounded-3" required placeholder="e.g. ABC001">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold">School Email <span class="text-danger">*</span></label>
                                <input type="email" name="school_email" class="form-control rounded-3" required placeholder="school@gmail.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold">School Phone <span class="text-danger">*</span></label>
                                <input type="tel" name="school_phone" class="form-control rounded-3" required pattern="[0-9+\-\s]{7,20}" title="Enter a valid phone number (7-20 characters)" placeholder="e.g. +91 98250 12345" value="<?= htmlspecialchars($school_phone ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label font-semibold">School Address <span class="text-danger">*</span></label>
                                <textarea name="school_address" class="form-control rounded-3" rows="3" required placeholder="Enter complete physical address"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold">City <span class="text-danger">*</span></label>
                                <input type="text" name="city" class="form-control rounded-3" required placeholder="City">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold">State <span class="text-danger">*</span></label>
                                <input type="text" name="state" class="form-control rounded-3" required placeholder="State">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold">Pincode <span class="text-danger">*</span></label>
                                <input type="text" name="pincode" class="form-control rounded-3" required placeholder="Pincode">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold">Website</label>
                                <input type="url" name="website" class="form-control rounded-3" placeholder="https://www.school.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold">School Logo</label>
                                <input type="file" name="logo" class="form-control rounded-3" accept=".png, .jpg, .jpeg">
                                <small class="text-muted">Allowed formats: JPG, JPEG, PNG (max 2MB)</small>
                            </div>
                        </div>

                        <!-- Section 2: Principal Information -->
                        <h5 class="fw-bold mb-4 text-indigo border-bottom pb-2"><i class="fa-solid fa-user-tie me-2"></i>Principal Information</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label font-semibold">Principal Name <span class="text-danger">*</span></label>
                                <input type="text" name="principal_name" class="form-control rounded-3" required placeholder="Principal's Name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold">Principal Email <span class="text-danger">*</span></label>
                                <input type="email" name="principal_email" class="form-control rounded-3" required placeholder="principal@gmail.com">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold">Principal Phone <span class="text-danger">*</span></label>
                                <input type="tel" name="principal_phone" class="form-control rounded-3" required pattern="[0-9+\-\s]{7,20}" title="Enter a valid phone number (7-20 characters)" placeholder="e.g. +91 98250 12345" value="<?= htmlspecialchars($principal_phone ?? '') ?>">
                            </div>
                        </div>

                        <!-- Section 3: School Admin Account Details -->
                        <h5 class="fw-bold mb-4 text-indigo border-bottom pb-2"><i class="fa-solid fa-user-shield me-2"></i>School Admin Account</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label font-semibold">Admin Name <span class="text-danger">*</span></label>
                                <input type="text" name="admin_name" class="form-control rounded-3" required placeholder="Admin Person Name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-semibold">Admin Email <span class="text-danger">*</span></label>
                                <input type="email" name="admin_email" class="form-control rounded-3" required placeholder="admin@gmail.com">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control rounded-3" required placeholder="Username for login">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold">Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control rounded-3" required minlength="6" placeholder="Password">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label font-semibold">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" name="confirm_password" class="form-control rounded-3" required minlength="6" placeholder="Confirm Password">
                            </div>
                        </div>

                        <!-- Section 4: Additional Information -->
                        <h5 class="fw-bold mb-4 text-indigo border-bottom pb-2"><i class="fa-solid fa-circle-info me-2"></i>Additional Information</h5>
                        <div class="row g-3 mb-5">
                            <div class="col-md-3">
                                <label class="form-label font-semibold">School Board <span class="text-danger">*</span></label>
                                <select name="board" class="form-select rounded-3">
                                    <option value="CBSE">CBSE</option>
                                    <option value="ICSE">ICSE</option>
                                    <option value="GSEB">GSEB</option>
                                    <option value="State Board">State Board</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label font-semibold">Medium <span class="text-danger">*</span></label>
                                <input type="text" name="medium" class="form-control rounded-3" required placeholder="e.g. English, Gujarati">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label font-semibold">Established Year <span class="text-danger">*</span></label>
                                <input type="number" name="established_year" class="form-control rounded-3" required min="1800" max="<?= date('Y') ?>" placeholder="e.g. 2005">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label font-semibold">School Type <span class="text-danger">*</span></label>
                                <select name="school_type" class="form-select rounded-3">
                                    <option value="Co-Ed">Co-Ed</option>
                                    <option value="Boys">Boys Only</option>
                                    <option value="Girls">Girls Only</option>
                                </select>
                            </div>
                        </div>

                        <div class="text-end">
                            <a href="../index.php" class="btn btn-light rounded-pill px-4 me-2">Cancel</a>
                            <button type="submit" class="btn btn-indigo rounded-pill px-4">Submit Registration Request</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="text-center mt-5">
                        <a href="../index.php" class="btn btn-indigo rounded-pill px-4"><i class="fa-solid fa-house me-2"></i>Return to Homepage</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
