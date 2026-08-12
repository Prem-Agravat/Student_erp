<?php
// C:\xampp\htdocs\school-erp\school\holidays.php

$activePage = 'holidays';
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
    echo '<p class="mb-3">Please configure and activate an Academic Year before setting up Holidays.</p>';
    echo '<a href="academic_years.php" class="btn btn-indigo rounded-pill px-4">Manage Academic Years</a>';
    echo '</div>';
    echo '</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$active_year_id = $activeYear['id'];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    
    $name = sanitizeInput($_POST['name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $start_date = sanitizeInput($_POST['start_date'] ?? '');
    $end_date = sanitizeInput($_POST['end_date'] ?? '');
    
    if (empty($name) || empty($start_date) || empty($end_date)) {
        $message = getAlert('danger', "Holiday Name, Start Date, and End Date are required.");
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO holidays (school_id, academic_year_id, name, description, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$school_id, $active_year_id, $name, $description, $start_date, $end_date]);
            
            logActivity("Create Holiday", "Created holiday: $name from $start_date to $end_date");
            $message = getAlert('success', "Holiday '$name' added successfully.");
        } catch (PDOException $e) {
            $message = getAlert('danger', "Failed to add holiday: " . $e->getMessage());
        }
    }
}

// Fetch holidays for the active year
$stmt = $db->prepare("SELECT * FROM holidays WHERE school_id = ? AND academic_year_id = ? ORDER BY start_date ASC");
$stmt->execute([$school_id, $active_year_id]);
$holidays = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Holidays Calendar</h2>
            <p class="text-secondary mb-0">Manage official school holidays for: <span class="badge bg-indigo"><?= htmlspecialchars($activeYear['name']) ?></span></p>
        </div>
        <button class="btn btn-indigo rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createHolidayModal"><i class="fa-solid fa-plus me-2"></i>Add Holiday</button>
    </div>
    
    <?= $message ?>
    
    <div class="card border-0 shadow-sm p-4 glass-card">
        <div class="table-responsive">
            <table class="table align-middle custom-table">
                <thead>
                    <tr>
                        <th>Holiday Name</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Days</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($holidays)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No holidays scheduled yet for this academic year.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($holidays as $hol): ?>
                            <?php
                            $start = new DateTime($hol['start_date']);
                            $end = new DateTime($hol['end_date']);
                            $diff = $start->diff($end)->days + 1;
                            ?>
                            <tr>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($hol['name']) ?></td>
                                <td><?= htmlspecialchars($hol['start_date']) ?></td>
                                <td><?= htmlspecialchars($hol['end_date']) ?></td>
                                <td><code><?= $diff ?> days</code></td>
                                <td><?= htmlspecialchars($hol['description'] ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Holiday Modal -->
<div class="modal fade" id="createHolidayModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <?= getCSRFInput() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add School Holiday</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-semibold">Holiday Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Summer Vacation, Christmas Break">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="e.g. School remains closed for all classes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-indigo">Save Holiday</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
