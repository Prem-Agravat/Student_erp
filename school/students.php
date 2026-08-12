<?php
// C:\xampp\htdocs\school-erp\school\students.php

$activePage = 'students';
require_once __DIR__ . '/../includes/school_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$school_id = $_SESSION['school_id'];
$message = '';

// Check active academic year
$stmtYear = $db->prepare("SELECT id, name FROM academic_years WHERE school_id = ? AND status = 'active'");
$stmtYear->execute([$school_id]);
$activeYear = $stmtYear->fetch();

if (!$activeYear) {
    echo '<div class="container-fluid">';
    echo '<div class="alert alert-warning py-4 shadow-sm border-0 glass-card">';
    echo '<h5 class="fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Active Academic Year Required</h5>';
    echo '<p class="mb-3">Please configure and activate an Academic Year before managing students.</p>';
    echo '<a href="academic_years.php" class="btn btn-indigo rounded-pill px-4">Manage Academic Years</a>';
    echo '</div>';
    echo '</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Fetch standards & sections for filters and add forms
$stmtStds = $db->prepare("SELECT id, name FROM standards WHERE school_id = ? AND status = 'active' ORDER BY display_order ASC");
$stmtStds->execute([$school_id]);
$standards = $stmtStds->fetchAll();

$stmtSecs = $db->prepare("SELECT id, name, standard_id FROM sections WHERE school_id = ? AND status = 'active' ORDER BY name ASC");
$stmtSecs->execute([$school_id]);
$sections = $stmtSecs->fetchAll();

// Handle Add Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    
    // Inputs
    $first_name = sanitizeInput($_POST['first_name'] ?? '');
    $middle_name = sanitizeInput($_POST['middle_name'] ?? '');
    $last_name = sanitizeInput($_POST['last_name'] ?? '');
    $gender = sanitizeInput($_POST['gender'] ?? 'Male');
    $dob = sanitizeInput($_POST['dob'] ?? '');
    $blood_group = sanitizeInput($_POST['blood_group'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $city = sanitizeInput($_POST['city'] ?? '');
    $state = sanitizeInput($_POST['state'] ?? '');
    $pincode = sanitizeInput($_POST['pincode'] ?? '');
    $standard_id = intval($_POST['standard_id'] ?? 0);
    $section_id = intval($_POST['section_id'] ?? 0);
    $roll_number = intval($_POST['roll_number'] ?? 0);
    $admission_number = sanitizeInput($_POST['admission_number'] ?? '');
    $admission_date = sanitizeInput($_POST['admission_date'] ?? date('Y-m-d'));
    
    $father_name = sanitizeInput($_POST['father_name'] ?? '');
    $mother_name = sanitizeInput($_POST['mother_name'] ?? '');
    $guardian_name = sanitizeInput($_POST['guardian_name'] ?? '');
    $parent_phone = sanitizeInput($_POST['parent_phone'] ?? '');
    $parent_email = sanitizeInput($_POST['parent_email'] ?? '');
    
    $password = $_POST['password'] ?? 'student123'; // Default temp password
    
    if (empty($first_name) || empty($last_name) || empty($dob) || $standard_id <= 0 || $section_id <= 0 || empty($parent_phone)) {
        $message = getAlert('danger', "Please fill in all required fields.");
    } else {
        // Validate roll number duplicates
        $stmtCheckRoll = $db->prepare("SELECT id FROM students WHERE school_id = ? AND academic_year_id = ? AND standard_id = ? AND section_id = ? AND roll_number = ?");
        $stmtCheckRoll->execute([$school_id, $activeYear['id'], $standard_id, $section_id, $roll_number]);
        if ($stmtCheckRoll->fetch()) {
            $message = getAlert('danger', "Roll number $roll_number already exists in this section.");
        } else {
            // Retrieve School Code
            $stmtSch = $db->prepare("SELECT school_code FROM schools WHERE id = ?");
            $stmtSch->execute([$school_id]);
            $school_code = $stmtSch->fetchColumn();
            
            // Get student count for generating IDs
            $stmtCount = $db->prepare("SELECT COUNT(*) FROM students WHERE school_id = ?");
            $stmtCount->execute([$school_id]);
            $studentCount = intval($stmtCount->fetchColumn());
            
            // Generate Student ID & Username
            $student_id = generateStudentID($school_code, $activeYear['name'], $studentCount);
            $username = strtolower($school_code) . str_pad($studentCount + 1, 4, '0', STR_PAD_LEFT);
            
            // Handle Photo upload
            $photo_name = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['photo']['tmp_name'];
                $fileName = $_FILES['photo']['name'];
                $fileSize = $_FILES['photo']['size'];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));
                
                $allowedExtensions = ['jpg', 'jpeg', 'png'];
                if (in_array($fileExtension, $allowedExtensions) && $fileSize < 2 * 1024 * 1024) {
                    $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                    $uploadFileDir = __DIR__ . '/../assets/uploads/students/';
                    if (!is_dir($uploadFileDir)) {
                        mkdir($uploadFileDir, 0777, true);
                    }
                    if (move_uploaded_file($fileTmpPath, $uploadFileDir . $newFileName)) {
                        $photo_name = $newFileName;
                    }
                }
            }
            
            try {
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("
                    INSERT INTO students (school_id, student_id, first_name, middle_name, last_name, gender, dob, blood_group, photo, email, phone, address, city, state, pincode, academic_year_id, standard_id, section_id, roll_number, admission_number, admission_date, father_name, mother_name, guardian_name, parent_phone, parent_email, username, password_hash, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
                ");
                
                $stmt->execute([
                    $school_id, $student_id, $first_name, $middle_name, $last_name, $gender, $dob, $blood_group,
                    $photo_name, $email, $phone, $address, $city, $state, $pincode, $activeYear['id'],
                    $standard_id, $section_id, $roll_number, $admission_number, $admission_date,
                    $father_name, $mother_name, $guardian_name, $parent_phone, $parent_email,
                    $username, $password_hash
                ]);
                
                logActivity("Create Student", "Created student profile: $first_name $last_name ($student_id)");
                $message = getAlert('success', "Student registered successfully! Student ID: $student_id, Username: $username");
            } catch (PDOException $e) {
                $message = getAlert('danger', "Database error: " . $e->getMessage());
            }
        }
    }
}

// Filtering
$search = sanitizeInput($_GET['search'] ?? '');
$std_filter = intval($_GET['standard_id'] ?? 0);
$sec_filter = intval($_GET['section_id'] ?? 0);

$query = "SELECT s.*, std.name as standard_name, sec.name as section_name FROM students s JOIN standards std ON s.standard_id = std.id JOIN sections sec ON s.section_id = sec.id WHERE s.school_id = ?";
$params = [$school_id];

if (!empty($search)) {
    $query .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ? OR s.username LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}
if ($std_filter > 0) {
    $query .= " AND s.standard_id = ?";
    $params[] = $std_filter;
}
if ($sec_filter > 0) {
    $query .= " AND s.section_id = ?";
    $params[] = $sec_filter;
}

$query .= " ORDER BY std.display_order ASC, sec.name ASC, s.roll_number ASC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Student Enrollment Directory</h2>
            <p class="text-secondary mb-0">Academic Year: <span class="badge bg-indigo"><?= htmlspecialchars($activeYear['name']) ?></span></p>
        </div>
        <button class="btn btn-indigo rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addStudentModal"><i class="fa-solid fa-plus me-2"></i>Add Student</button>
    </div>
    
    <?= $message ?>
    
    <!-- Search and filter -->
    <div class="card border-0 shadow-sm p-4 mb-4 glass-card">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label font-semibold">Search Student</label>
                <input type="text" name="search" class="form-control" placeholder="Search by name, ID, username..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label font-semibold">Standard</label>
                <select name="standard_id" class="form-select">
                    <option value="">All Standards</option>
                    <?php foreach ($standards as $std): ?>
                        <option value="<?= $std['id'] ?>" <?= $std_filter === $std['id'] ? 'selected' : '' ?>><?= htmlspecialchars($std['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label font-semibold">Section</label>
                <select name="section_id" class="form-select">
                    <option value="">All Sections</option>
                    <?php foreach ($sections as $sec): ?>
                        <option value="<?= $sec['id'] ?>" <?= $sec_filter === $sec['id'] ? 'selected' : '' ?>><?= htmlspecialchars($sec['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-indigo w-100 rounded-pill"><i class="fa-solid fa-filter me-1"></i>Filter</button>
                <a href="students.php" class="btn btn-light border w-100 rounded-pill"><i class="fa-solid fa-rotate-left"></i></a>
            </div>
        </form>
    </div>
    
    <!-- Directory Table -->
    <div class="card border-0 shadow-sm p-4 glass-card">
        <div class="table-responsive">
            <table class="table align-middle custom-table">
                <thead>
                    <tr>
                        <th>Roll No</th>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Class Division</th>
                        <th>Parent Contact</th>
                        <th>Portal Username</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No student records found. Click "Add Student" to create.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $stu): ?>
                            <tr>
                                <td><code>#<?= htmlspecialchars($stu['roll_number']) ?></code></td>
                                <td><code><?= htmlspecialchars($stu['student_id']) ?></code></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($stu['gender']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($stu['standard_name']) ?> — <?= htmlspecialchars($stu['section_name']) ?></td>
                                <td>
                                    <div><?= htmlspecialchars($stu['father_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($stu['parent_phone']) ?></small>
                                </td>
                                <td><code><?= htmlspecialchars($stu['username']) ?></code></td>
                                <td><span class="status-badge status-approved"><?= ucfirst($stu['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" enctype="multipart/form-data">
            <?= getCSRFInput() ?>
            <input type="hidden" name="action" value="create">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Enroll New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Step 1: Personal -->
                    <h6 class="fw-bold text-indigo mb-3 border-bottom pb-2">1. Personal Information</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label font-semibold">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" required placeholder="First Name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control" placeholder="Middle Name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" required placeholder="Last Name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" name="dob" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Blood Group</label>
                            <input type="text" name="blood_group" class="form-control" placeholder="e.g. O+, A-">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Student Photo</label>
                            <input type="file" name="photo" class="form-control" accept=".jpg, .jpeg, .png">
                        </div>
                    </div>
                    
                    <!-- Step 2: Contact -->
                    <h6 class="fw-bold text-indigo mb-3 border-bottom pb-2">2. Contact details</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Student Email</label>
                            <input type="email" name="email" class="form-control" placeholder="student@example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Student Phone</label>
                            <input type="text" name="phone" class="form-control" placeholder="Student phone number">
                        </div>
                        <div class="col-12">
                            <label class="form-label font-semibold">Address <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control" rows="2" required placeholder="Address"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">City <span class="text-danger">*</span></label>
                            <input type="text" name="city" class="form-control" required placeholder="City">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">State <span class="text-danger">*</span></label>
                            <input type="text" name="state" class="form-control" required placeholder="State">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Pincode <span class="text-danger">*</span></label>
                            <input type="text" name="pincode" class="form-control" required placeholder="Pincode">
                        </div>
                    </div>
                    
                    <!-- Step 3: Academic details -->
                    <h6 class="fw-bold text-indigo mb-3 border-bottom pb-2">3. Academic Information</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Standard <span class="text-danger">*</span></label>
                            <select name="standard_id" class="form-select" required>
                                <option value="">Select Standard</option>
                                <?php foreach ($standards as $std): ?>
                                    <option value="<?= $std['id'] ?>"><?= htmlspecialchars($std['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Section <span class="text-danger">*</span></label>
                            <select name="section_id" class="form-select" required>
                                <option value="">Select Section</option>
                                <?php foreach ($sections as $sec): ?>
                                    <option value="<?= $sec['id'] ?>" data-std="<?= $sec['standard_id'] ?>"><?= htmlspecialchars($sec['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Roll Number <span class="text-danger">*</span></label>
                            <input type="number" name="roll_number" class="form-control" required min="1" placeholder="e.g. 15">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Admission Number <span class="text-danger">*</span></label>
                            <input type="text" name="admission_number" class="form-control" required placeholder="e.g. ADM2026001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Admission Date</label>
                            <input type="date" name="admission_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    
                    <!-- Step 4: Parent details -->
                    <h6 class="fw-bold text-indigo mb-3 border-bottom pb-2">4. Parent Information</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Father's Name <span class="text-danger">*</span></label>
                            <input type="text" name="father_name" class="form-control" required placeholder="Father's full name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Mother's Name <span class="text-danger">*</span></label>
                            <input type="text" name="mother_name" class="form-control" required placeholder="Mother's full name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Guardian Name</label>
                            <input type="text" name="guardian_name" class="form-control" placeholder="Guardian's name (optional)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Parent Phone <span class="text-danger">*</span></label>
                            <input type="text" name="parent_phone" class="form-control" required placeholder="Primary contact number">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Parent Email</label>
                            <input type="email" name="parent_email" class="form-control" placeholder="parent@example.com">
                        </div>
                    </div>
                    
                    <!-- Step 5: Password configuration -->
                    <h6 class="fw-bold text-indigo mb-3 border-bottom pb-2">5. Account Password</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Temporary Portal Password</label>
                            <input type="password" name="password" class="form-control" value="student123" placeholder="Default: student123">
                            <small class="text-muted">Students can reset their password on their dashboard profile later.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-indigo">Enroll Student</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Filter sections based on selected standard in the modal add-form
document.addEventListener("DOMContentLoaded", function() {
    const stdSelect = document.querySelector('select[name="standard_id"]');
    const secSelect = document.querySelector('select[name="section_id"]');
    
    if (stdSelect && secSelect) {
        const originalOptions = Array.from(secSelect.options);
        
        stdSelect.addEventListener('change', function() {
            const selectedStdId = stdSelect.value;
            
            // Clear current options
            secSelect.innerHTML = '';
            
            // Re-add matching options
            originalOptions.forEach(option => {
                if (option.value === '' || option.getAttribute('data-std') === selectedStdId) {
                    secSelect.appendChild(option.cloneNode(true));
                }
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
