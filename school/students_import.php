<?php
// C:\xampp\htdocs\school-erp\school\students_import.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle Sample CSV Download
if (isset($_GET['download_sample']) && $_GET['download_sample'] == '1') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=student_import_sample.csv');
    $output = fopen('php://output', 'w');
    
    // Header Row
    fputcsv($output, [
        'first_name', 'middle_name', 'last_name', 'gender', 'dob_yyyy_mm_dd', 
        'blood_group', 'email', 'phone', 'address', 'city', 'state', 'pincode', 
        'standard_id', 'section_id', 'roll_number', 'admission_number', 
        'father_name', 'mother_name', 'parent_phone', 'parent_email'
    ]);
    
    // Sample Data Row
    fputcsv($output, [
        'Prem', 'Ramesh', 'Agravat', 'Male', '2015-08-20', 
        'B+', 'prem.agravat@example.com', '9876543210', '123 Ring Road', 'Rajkot', 'Gujarat', '360001', 
        '1', '1', '1', 'ADM2025-001', 
        'Ramesh Agravat', 'Geeta Agravat', '9876501234', 'ramesh.agravat@example.com'
    ]);
    
    fclose($output);
    exit;
}

$activePage = 'import_students';
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
    echo '<p class="mb-3">Please configure and activate an Academic Year before importing students.</p>';
    echo '<a href="academic_years.php" class="btn btn-indigo rounded-pill px-4">Manage Academic Years</a>';
    echo '</div>';
    echo '</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Handle CSV Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF validation failed.");
    }
    
    $fileTmpPath = $_FILES['csv_file']['tmp_name'];
    $fileName = $_FILES['csv_file']['name'];
    $fileSize = $_FILES['csv_file']['size'];
    
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));
    
    if ($fileExtension !== 'csv') {
        $message = getAlert('danger', "Please upload a valid CSV file.");
    } elseif ($fileSize <= 0) {
        $message = getAlert('danger', "Uploaded file is empty.");
    } else {
        if (($handle = fopen($fileTmpPath, "r")) !== FALSE) {
            // Read Header
            $header = fgetcsv($handle, 1000, ",");
            
            // Expected headers count
            if (count($header) < 15) {
                $message = getAlert('danger', "Invalid CSV format. Please download and match the sample CSV layout.");
                fclose($handle);
            } else {
                // Fetch School Code
                $stmtSch = $db->prepare("SELECT school_code FROM schools WHERE id = ?");
                $stmtSch->execute([$school_id]);
                $school_code = $stmtSch->fetchColumn();
                
                // Get student count for generating IDs
                $stmtCount = $db->prepare("SELECT COUNT(*) FROM students WHERE school_id = ?");
                $stmtCount->execute([$school_id]);
                $studentCount = intval($stmtCount->fetchColumn());
                
                $successCount = 0;
                $errorCount = 0;
                
                try {
                    $db->beginTransaction();
                    
                    $stmtInsert = $db->prepare("
                        INSERT INTO students (
                            school_id, student_id, first_name, middle_name, last_name, gender, dob, blood_group, 
                            email, phone, address, city, state, pincode, academic_year_id, standard_id, section_id, 
                            roll_number, admission_number, admission_date, father_name, mother_name, parent_phone, parent_email, 
                            username, password_hash, status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
                    ");
                    
                    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if (count($row) < 15) continue;
                        
                        $first_name = sanitizeInput($row[0] ?? '');
                        $middle_name = sanitizeInput($row[1] ?? '');
                        $last_name = sanitizeInput($row[2] ?? '');
                        $gender = sanitizeInput($row[3] ?? 'Male');
                        $dob = sanitizeInput($row[4] ?? '');
                        $blood_group = sanitizeInput($row[5] ?? '');
                        $email = sanitizeInput($row[6] ?? '');
                        $phone = sanitizeInput($row[7] ?? '');
                        $address = sanitizeInput($row[8] ?? '');
                        $city = sanitizeInput($row[9] ?? '');
                        $state = sanitizeInput($row[10] ?? '');
                        $pincode = sanitizeInput($row[11] ?? '');
                        $standard_id = intval($row[12] ?? 0);
                        $section_id = getOrInsertDefaultSectionId($db, $school_id, $standard_id);
                        $roll_number = intval($row[14] ?? 0);
                        $admission_number = sanitizeInput($row[15] ?? '');
                        $father_name = sanitizeInput($row[16] ?? '');
                        $mother_name = sanitizeInput($row[17] ?? '');
                        $parent_phone = sanitizeInput($row[18] ?? '');
                        $parent_email = sanitizeInput($row[19] ?? '');
                        
                        if (empty($first_name) || empty($last_name) || empty($dob) || $standard_id <= 0 || empty($parent_phone)) {
                            $errorCount++;
                            continue;
                        }
                        
                        // Check roll number uniqueness
                        $stmtCheckRoll = $db->prepare("SELECT id FROM students WHERE school_id = ? AND academic_year_id = ? AND standard_id = ? AND section_id = ? AND roll_number = ?");
                        $stmtCheckRoll->execute([$school_id, $activeYear['id'], $standard_id, $section_id, $roll_number]);
                        if ($stmtCheckRoll->fetch()) {
                            $errorCount++;
                            continue;
                        }
                        
                        // Generate ID and credentials
                        $studentCount++;
                        $student_id = generateStudentID($school_code, $activeYear['name'], $studentCount - 1);
                        $username = strtolower($school_code) . str_pad($studentCount, 4, '0', STR_PAD_LEFT);
                        $password_hash = password_hash('student123', PASSWORD_BCRYPT);
                        
                        $stmtInsert->execute([
                            $school_id, $student_id, $first_name, $middle_name, $last_name, $gender, $dob, $blood_group,
                            $email, $phone, $address, $city, $state, $pincode, $activeYear['id'],
                            $standard_id, $section_id, $roll_number, $admission_number, date('Y-m-d'),
                            $father_name, $mother_name, $parent_phone, $parent_email,
                            $username, $password_hash
                        ]);
                        $successCount++;
                    }
                    
                    $db->commit();
                    logActivity("Import Students", "Bulk imported students: $successCount successes, $errorCount skips");
                    $message = getAlert('success', "Import complete! $successCount students enrolled successfully. $errorCount rows skipped due to duplicate rolls or missing data.");
                } catch (PDOException $e) {
                    $db->rollBack();
                    $message = getAlert('danger', "Import failed: " . $e->getMessage());
                }
                
                fclose($handle);
            }
        }
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">Bulk Import Students</h2>
            <p class="text-secondary">Quickly upload hundreds of student records via CSV sheets.</p>
        </div>
    </div>
    
    <?= $message ?>
    
    <div class="row g-4">
        <!-- Import card -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 glass-card">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-file-csv text-indigo me-2"></i>Upload CSV File</h5>
                <form method="POST" enctype="multipart/form-data" class="mb-3">
                    <?= getCSRFInput() ?>
                    <div class="mb-4">
                        <label class="form-label font-semibold">Select CSV File</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        <small class="text-muted">Ensure your CSV format uses UTF-8 and fits the sample configuration exactly.</small>
                    </div>
                    <button type="submit" class="btn btn-indigo rounded-pill px-4"><i class="fa-solid fa-upload me-2"></i>Start Student Import</button>
                </form>
            </div>
        </div>
        
        <!-- Guidelines card -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 glass-card">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-circle-info text-indigo me-2"></i>Import Instructions</h5>
                <ol class="text-secondary ps-3 mb-4">
                    <li class="mb-2">Download the official sample CSV file below.</li>
                    <li class="mb-2">Insert data matching the headers carefully.</li>
                    <li class="mb-2"><strong>`standard_id`</strong> must match the standard ID in your dashboard tables! Section is automatically assigned.</li>
                    <li class="mb-2">Rows with missing required fields or duplicate roll numbers are automatically skipped to avoid corrupting records.</li>
                    <li class="mb-2">Temporary passwords will automatically default to <strong>`student123`</strong>.</li>
                </ol>
                <a href="?download_sample=1" class="btn btn-outline-indigo w-100 rounded-pill"><i class="fa-solid fa-download me-2"></i>Download Sample CSV Layout</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
