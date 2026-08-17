<?php
// C:\xampp\htdocs\school-erp\student\profile.php

$activePage = 'profile';
require_once __DIR__ . '/../includes/student_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/header.php';

$student_id = $_SESSION['student_id'];
$school_id = $_SESSION['school_id'];
$db = getDBConnection();
$message = '';

// Fetch Profile
$stmt = $db->prepare("
    SELECT s.*, std.name as standard_name, sec.name as section_name, ay.name as academic_year_name 
    FROM students s 
    JOIN standards std ON s.standard_id = std.id 
    JOIN sections sec ON s.section_id = sec.id 
    JOIN academic_years ay ON s.academic_year_id = ay.id
    WHERE s.id = ? AND s.school_id = ?
");
$stmt->execute([$student_id, $school_id]);
$student = $stmt->fetch();

if (!$student) {
    echo '<div class="alert alert-danger">Access denied. Student record not found.</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $parent_email = sanitizeInput($_POST['parent_email'] ?? '');
        
        if (empty($phone) || empty($email)) {
            $message = getAlert('danger', "My Phone and My Email fields are required.");
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = getAlert('danger', "Invalid student email format.");
        } elseif (!empty($parent_email) && !filter_var($parent_email, FILTER_VALIDATE_EMAIL)) {
            $message = getAlert('danger', "Invalid parent email format.");
        } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
            $message = getAlert('danger', "My Phone number must be exactly 10 digits.");
        } else {
            try {
                $stmtUpdate = $db->prepare("UPDATE students SET phone = ?, email = ?, parent_email = ? WHERE id = ? AND school_id = ?");
                $stmtUpdate->execute([$phone, $email, $parent_email, $student_id, $school_id]);
                logActivity("Update Profile", "Student updated contact information.");
                $student['phone'] = $phone;
                $student['email'] = $email;
                $student['parent_email'] = $parent_email;
                $message = getAlert('success', "Contact information updated successfully.");
            } catch (PDOException $e) {
                $message = getAlert('danger', "Failed to update profile: " . $e->getMessage());
            }
        }
    } elseif ($action === 'change_password') {
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $message = getAlert('danger', "Please fill in all password fields.");
        } elseif (strlen($new_password) < 6) {
            $message = getAlert('danger', "New password must be at least 6 characters long.");
        } elseif ($new_password !== $confirm_password) {
            $message = getAlert('danger', "New passwords do not match.");
        } else {
            // Verify old password
            if (password_verify($old_password, $student['password_hash'])) {
                try {
                    $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
                    $stmtPass = $db->prepare("UPDATE students SET password_hash = ? WHERE id = ? AND school_id = ?");
                    $stmtPass->execute([$new_hash, $student_id, $school_id]);
                    logActivity("Change Password", "Student changed portal account password.");
                    $message = getAlert('success', "Password changed successfully.");
                } catch (PDOException $e) {
                    $message = getAlert('danger', "Failed to change password: " . $e->getMessage());
                }
            } else {
                $message = getAlert('danger', "Incorrect current password.");
            }
        }
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">My Profile Details</h2>
            <p class="text-secondary">Manage contact parameters and login credentials.</p>
        </div>
    </div>
    
    <?= $message ?>
    
    <div class="row g-4">
        <!-- View profile tabs -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 glass-card mb-4">
                <ul class="nav nav-tabs mb-4" id="profileTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active fw-bold" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal">Personal Info</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold" id="parent-tab" data-bs-toggle="tab" data-bs-target="#parent">Parent details</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold" id="academic-tab" data-bs-toggle="tab" data-bs-target="#academic">Academic Info</button>
                    </li>
                </ul>
                
                <div class="tab-content" id="profileTabContent">
                    <!-- Personal info -->
                    <div class="tab-pane fade show active" id="personal">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <small class="text-muted d-block">First Name</small>
                                <span class="fw-bold"><?= htmlspecialchars($student['first_name']) ?></span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Last Name</small>
                                <span class="fw-bold"><?= htmlspecialchars($student['last_name']) ?></span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Gender</small>
                                <span class="fw-bold"><?= htmlspecialchars($student['gender']) ?></span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Date of Birth</small>
                                <span class="fw-bold"><?= htmlspecialchars($student['dob']) ?></span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Blood Group</small>
                                <span class="fw-bold"><?= htmlspecialchars($student['blood_group'] ?: 'N/A') ?></span>
                            </div>
                            <div class="col-12 mt-4 border-top pt-3">
                                <h6 class="fw-bold text-indigo mb-3">Residential Address</h6>
                                <p class="mb-0"><?= htmlspecialchars($student['address']) ?>, <?= htmlspecialchars($student['city']) ?>, <?= htmlspecialchars($student['state']) ?> - <?= htmlspecialchars($student['pincode']) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Parents -->
                    <div class="tab-pane fade" id="parent">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Father's Name</small>
                                <span class="fw-bold"><?= htmlspecialchars($student['father_name']) ?></span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Mother's Name</small>
                                <span class="fw-bold"><?= htmlspecialchars($student['mother_name']) ?></span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Guardian Name</small>
                                <span class="fw-bold"><?= htmlspecialchars($student['guardian_name'] ?: 'N/A') ?></span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Parent Phone</small>
                                <span class="fw-bold"><?= htmlspecialchars($student['parent_phone']) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Academics -->
                    <div class="tab-pane fade" id="academic">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Student ID</small>
                                <span class="fw-bold"><code><?= htmlspecialchars($student['student_id']) ?></code></span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Admission Number</small>
                                <span class="fw-bold"><code><?= htmlspecialchars($student['admission_number']) ?></code></span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Class / Standard</small>
                                <span class="fw-bold"><?= htmlspecialchars($student['standard_name']) ?></span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Roll Number</small>
                                <span class="fw-bold">#<?= htmlspecialchars($student['roll_number']) ?></span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Admission Date</small>
                                <span class="fw-bold"><?= htmlspecialchars($student['admission_date']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Update contacts form -->
            <div class="card border-0 shadow-sm p-4 glass-card">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-address-book text-indigo me-2"></i>Update Contact details</h5>
                <form method="POST">
                    <?= getCSRFInput() ?>
                    <input type="hidden" name="action" value="update_profile">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label font-semibold">My Phone</label>
                            <input type="tel" name="phone" class="form-control" required pattern="[0-9]{10}" title="Please enter a valid 10-digit phone number" value="<?= htmlspecialchars($student['phone']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">My Email</label>
                            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($student['email']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Parent Email</label>
                            <input type="email" name="parent_email" class="form-control" value="<?= htmlspecialchars($student['parent_email']) ?>">
                        </div>
                        <div class="col-12 text-end mt-4">
                            <button type="submit" class="btn btn-indigo rounded-pill px-4">Update Contacts</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Account configuration -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 glass-card text-center mb-4">
                <div class="mb-3">
                    <?php if (!empty($student['photo'])): ?>
                        <img src="<?= UPLOAD_URL ?>students/<?= $student['photo'] ?>" alt="Profile" class="rounded-circle border border-indigo p-1" style="width: 120px; height: 120px; object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-indigo bg-opacity-10 text-indigo rounded-circle d-flex align-items-center justify-content-center fw-bold mx-auto" style="width: 120px; height: 120px; font-size: 42px;">
                            <?= strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <h5 class="fw-bold mb-1"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></h5>
                <code class="d-block mb-3">Portal Username: <?= htmlspecialchars($student['username']) ?></code>
            </div>
            
            <div class="card border-0 shadow-sm p-4 glass-card" style="background: linear-gradient(135deg, #f5f7ff 0%, #eef2ff 100%); border-left: 4px solid #6366f1;">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-key text-indigo me-2"></i>Change Password</h5>
                <form method="POST">
                    <?= getCSRFInput() ?>
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="old_password" class="form-control" required placeholder="••••••••">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6" placeholder="••••••••">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6" placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn btn-indigo w-100 rounded-pill mt-2">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
