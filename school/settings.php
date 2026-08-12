<?php
// C:\xampp\htdocs\school-erp\school\settings.php

$activePage = 'settings';
require_once __DIR__ . '/../includes/school_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$school_id = $_SESSION['school_id'];
$message = '';

// Fetch current School details
$stmt = $db->prepare("SELECT * FROM schools WHERE id = ?");
$stmt->execute([$school_id]);
$school = $stmt->fetch();

if (!$school) {
    echo '<div class="alert alert-danger">School profile not found.</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    
    $school_name = sanitizeInput($_POST['school_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $city = sanitizeInput($_POST['city'] ?? '');
    $state = sanitizeInput($_POST['state'] ?? '');
    $pincode = sanitizeInput($_POST['pincode'] ?? '');
    $website = sanitizeInput($_POST['website'] ?? '');
    
    $principal_name = sanitizeInput($_POST['principal_name'] ?? '');
    $principal_email = sanitizeInput($_POST['principal_email'] ?? '');
    $principal_phone = sanitizeInput($_POST['principal_phone'] ?? '');
    
    $board = sanitizeInput($_POST['board'] ?? '');
    $medium = sanitizeInput($_POST['medium'] ?? '');
    $established_year = intval($_POST['established_year'] ?? 0);
    $school_type = sanitizeInput($_POST['school_type'] ?? '');
    
    if (empty($school_name) || empty($email) || empty($phone) || empty($address)) {
        $message = getAlert('danger', "All fields marked * are required.");
    } else {
        // Logo Upload Handle
        $logo_name = $school['logo'];
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['logo']['tmp_name'];
            $fileName = $_FILES['logo']['name'];
            $fileSize = $_FILES['logo']['size'];
            
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            
            $allowedExtensions = ['jpg', 'jpeg', 'png'];
            if (in_array($fileExtension, $allowedExtensions) && $fileSize < 2 * 1024 * 1024) {
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $uploadFileDir = __DIR__ . '/../assets/uploads/logos/';
                
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0777, true);
                }
                
                if (move_uploaded_file($fileTmpPath, $uploadFileDir . $newFileName)) {
                    $logo_name = $newFileName;
                }
            }
        }
        
        try {
            $stmtUpdate = $db->prepare("
                UPDATE schools 
                SET school_name = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, pincode = ?, 
                    website = ?, logo = ?, board = ?, medium = ?, established_year = ?, school_type = ?, 
                    principal_name = ?, principal_email = ?, principal_phone = ? 
                WHERE id = ?
            ");
            $stmtUpdate->execute([
                $school_name, $email, $phone, $address, $city, $state, $pincode, 
                $website, $logo_name, $board, $medium, $established_year, $school_type,
                $principal_name, $principal_email, $principal_phone, $school_id
            ]);
            
            logActivity("Update School Profile", "Updated school settings profile parameters.");
            $message = getAlert('success', "School settings updated successfully.");
            
            // Reload school details
            $school['school_name'] = $school_name;
            $school['email'] = $email;
            $school['phone'] = $phone;
            $school['address'] = $address;
            $school['city'] = $city;
            $school['state'] = $state;
            $school['pincode'] = $pincode;
            $school['website'] = $website;
            $school['logo'] = $logo_name;
            $school['board'] = $board;
            $school['medium'] = $medium;
            $school['established_year'] = $established_year;
            $school['school_type'] = $school_type;
            $school['principal_name'] = $principal_name;
            $school['principal_email'] = $principal_email;
            $school['principal_phone'] = $principal_phone;
            
        } catch (PDOException $e) {
            $message = getAlert('danger', "Failed to update school settings: " . $e->getMessage());
        }
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">School Settings Profile</h2>
            <p class="text-secondary">Configure branding parameters, logos, Principal details, and academic medium settings.</p>
        </div>
    </div>
    
    <?= $message ?>
    
    <form method="POST" enctype="multipart/form-data" class="glass-card p-5 mb-5">
        <?= getCSRFInput() ?>
        
        <!-- School Metadata -->
        <h5 class="fw-bold mb-4 text-indigo border-bottom pb-2"><i class="fa-solid fa-school me-2"></i>School Branding</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label font-semibold">School Name <span class="text-danger">*</span></label>
                <input type="text" name="school_name" class="form-control" value="<?= htmlspecialchars($school['school_name']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label font-semibold">School Code (Immutable)</label>
                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($school['school_code']) ?>" disabled>
            </div>
            <div class="col-md-6">
                <label class="form-label font-semibold">School Contact Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($school['email']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label font-semibold">School Phone <span class="text-danger">*</span></label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($school['phone']) ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label font-semibold">Physical Address <span class="text-danger">*</span></label>
                <textarea name="address" class="form-control" rows="3" required><?= htmlspecialchars($school['address']) ?></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label font-semibold">City <span class="text-danger">*</span></label>
                <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($school['city']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label font-semibold">State <span class="text-danger">*</span></label>
                <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($school['state']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label font-semibold">Pincode <span class="text-danger">*</span></label>
                <input type="text" name="pincode" class="form-control" value="<?= htmlspecialchars($school['pincode']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label font-semibold">Website URL</label>
                <input type="url" name="website" class="form-control" value="<?= htmlspecialchars($school['website']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label font-semibold">School Logo</label>
                <input type="file" name="logo" class="form-control" accept=".jpg, .jpeg, .png">
                <?php if (!empty($school['logo'])): ?>
                    <small class="text-muted mt-2 d-block">Current logo: <a href="<?= UPLOAD_URL ?>logos/<?= $school['logo'] ?>" target="_blank"><?= $school['logo'] ?></a></small>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Principal Info -->
        <h5 class="fw-bold mb-4 text-indigo border-bottom pb-2"><i class="fa-solid fa-user-tie me-2"></i>Principal Details</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label font-semibold">Principal Name <span class="text-danger">*</span></label>
                <input type="text" name="principal_name" class="form-control" value="<?= htmlspecialchars($school['principal_name']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label font-semibold">Principal Email <span class="text-danger">*</span></label>
                <input type="email" name="principal_email" class="form-control" value="<?= htmlspecialchars($school['principal_email']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label font-semibold">Principal Phone <span class="text-danger">*</span></label>
                <input type="text" name="principal_phone" class="form-control" value="<?= htmlspecialchars($school['principal_phone']) ?>" required>
            </div>
        </div>
        
        <!-- Additional parameters -->
        <h5 class="fw-bold mb-4 text-indigo border-bottom pb-2"><i class="fa-solid fa-circle-info me-2"></i>Additional Info</h5>
        <div class="row g-3 mb-5">
            <div class="col-md-3">
                <label class="form-label font-semibold">Board Board</label>
                <select name="board" class="form-select">
                    <option value="CBSE" <?= $school['board'] === 'CBSE' ? 'selected' : '' ?>>CBSE</option>
                    <option value="ICSE" <?= $school['board'] === 'ICSE' ? 'selected' : '' ?>>ICSE</option>
                    <option value="GSEB" <?= $school['board'] === 'GSEB' ? 'selected' : '' ?>>GSEB</option>
                    <option value="State Board" <?= $school['board'] === 'State Board' ? 'selected' : '' ?>>State Board</option>
                    <option value="Other" <?= $school['board'] === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label font-semibold">Medium <span class="text-danger">*</span></label>
                <input type="text" name="medium" class="form-control" value="<?= htmlspecialchars($school['medium']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label font-semibold">Established Year <span class="text-danger">*</span></label>
                <input type="number" name="established_year" class="form-control" value="<?= htmlspecialchars($school['established_year']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label font-semibold">School Type</label>
                <select name="school_type" class="form-select">
                    <option value="Co-Ed" <?= $school['school_type'] === 'Co-Ed' ? 'selected' : '' ?>>Co-Ed</option>
                    <option value="Boys" <?= $school['school_type'] === 'Boys' ? 'selected' : '' ?>>Boys</option>
                    <option value="Girls" <?= $school['school_type'] === 'Girls' ? 'selected' : '' ?>>Girls</option>
                </select>
            </div>
        </div>
        
        <div class="text-end">
            <button type="submit" class="btn btn-indigo rounded-pill px-5">Save Settings Changes</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
