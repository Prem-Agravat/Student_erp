<?php
// C:\xampp\htdocs\school-erp\school\standards.php

$activePage = 'standards';
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
    echo '<p class="mb-3">You must create and activate an Academic Year before configuring Standards (Classes).</p>';
    echo '<a href="academic_years.php" class="btn btn-indigo rounded-pill px-4">Manage Academic Years</a>';
    echo '</div>';
    echo '</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$active_year_id = $activeYear['id'];

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF validation failed.");
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        $status = sanitizeInput($_POST['status'] ?? 'active');
        
        if (empty($name)) {
            $message = getAlert('danger', "Standard name is required.");
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO standards (school_id, academic_year_id, name, display_order, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$school_id, $active_year_id, $name, $display_order, $status]);
                $std_id = $db->lastInsertId();
                getOrInsertDefaultSectionId($db, $school_id, $std_id);
                logActivity("Create Standard", "Created standard: $name in academic year: " . $activeYear['name']);
                $message = getAlert('success', "Standard '$name' added successfully.");
            } catch (PDOException $e) {
                $message = getAlert('danger', "Failed to add standard: " . $e->getMessage());
            }
        }
    }
}

// Fetch standards for the active academic year
$stmt = $db->prepare("SELECT * FROM standards WHERE school_id = ? AND academic_year_id = ? ORDER BY display_order ASC, id ASC");
$stmt->execute([$school_id, $active_year_id]);
$standards = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Standards (Classes)</h2>
            <p class="text-secondary mb-0">Academic Year: <span class="badge bg-indigo"><?= htmlspecialchars($activeYear['name']) ?></span></p>
        </div>
        <button class="btn btn-indigo rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createStandardModal"><i class="fa-solid fa-plus me-2"></i>Add Standard</button>
    </div>
    
    <?= $message ?>
    
    <div class="card border-0 shadow-sm p-4 glass-card">
        <div class="table-responsive">
            <table class="table align-middle custom-table">
                <thead>
                    <tr>
                        <th>Display Order</th>
                        <th>Standard Name</th>
                        <th>Status</th>
                        <th>Date Configured</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($standards)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No standards configured yet for this academic year. Click "Add Standard" to create one.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($standards as $std): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($std['display_order']) ?></code></td>
                                <td class="fw-bold"><?= htmlspecialchars($std['name']) ?></td>
                                <td>
                                    <span class="badge <?= $std['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= ucfirst($std['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('Y-m-d', strtotime($std['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Standard Modal -->
<div class="modal fade" id="createStandardModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <?= getCSRFInput() ?>
            <input type="hidden" name="action" value="create">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Standard</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-semibold">Standard / Class Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Standard 10 or Grade 5">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Display Order</label>
                        <input type="number" name="display_order" class="form-control" value="0" min="0" placeholder="e.g. 1">
                        <small class="text-muted">Used to sort classes sequentially in drop downs.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-indigo">Save Standard</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
