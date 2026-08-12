<?php
// C:\xampp\htdocs\school-erp\school\sections.php

$activePage = 'sections';
require_once __DIR__ . '/../includes/school_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$school_id = $_SESSION['school_id'];
$message = '';

// Check if any standards exist
$stmtStd = $db->prepare("SELECT id, name FROM standards WHERE school_id = ? AND status = 'active' ORDER BY display_order ASC");
$stmtStd->execute([$school_id]);
$standards = $stmtStd->fetchAll();

if (empty($standards)) {
    echo '<div class="container-fluid">';
    echo '<div class="alert alert-warning py-4 shadow-sm border-0 glass-card">';
    echo '<h5 class="fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Standards Required</h5>';
    echo '<p class="mb-3">You must configure at least one active Standard before setting up Sections (divisions).</p>';
    echo '<a href="standards.php" class="btn btn-indigo rounded-pill px-4">Manage Standards</a>';
    echo '</div>';
    echo '</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF validation failed.");
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $standard_id = intval($_POST['standard_id'] ?? 0);
        $name = sanitizeInput($_POST['name'] ?? '');
        $class_teacher = sanitizeInput($_POST['class_teacher'] ?? '');
        $capacity = intval($_POST['capacity'] ?? 40);
        $status = sanitizeInput($_POST['status'] ?? 'active');
        
        if ($standard_id <= 0 || empty($name)) {
            $message = getAlert('danger', "Standard and Section Name are required.");
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO sections (school_id, standard_id, name, class_teacher, capacity, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$school_id, $standard_id, $name, $class_teacher, $capacity, $status]);
                logActivity("Create Section", "Created section: $name for standard ID: $standard_id");
                $message = getAlert('success', "Section '$name' added successfully.");
            } catch (PDOException $e) {
                $message = getAlert('danger', "Failed to add section: " . $e->getMessage());
            }
        }
    }
}

// Fetch sections with standard names
$stmt = $db->prepare("SELECT sec.*, std.name as standard_name FROM sections sec JOIN standards std ON sec.standard_id = std.id WHERE sec.school_id = ? ORDER BY std.display_order ASC, sec.name ASC");
$stmt->execute([$school_id]);
$sections = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Sections Management</h2>
            <p class="text-secondary mb-0">Configure divisions/sections for standards with capacity and teachers.</p>
        </div>
        <button class="btn btn-indigo rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createSectionModal"><i class="fa-solid fa-plus me-2"></i>Add Section</button>
    </div>
    
    <?= $message ?>
    
    <div class="card border-0 shadow-sm p-4 glass-card">
        <div class="table-responsive">
            <table class="table align-middle custom-table">
                <thead>
                    <tr>
                        <th>Standard</th>
                        <th>Section Name</th>
                        <th>Class Teacher</th>
                        <th>Capacity</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sections)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No sections configured yet. Click "Add Section" to create one.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sections as $sec): ?>
                            <tr>
                                <td class="fw-bold text-indigo"><?= htmlspecialchars($sec['standard_name']) ?></td>
                                <td class="fw-bold">Section <?= htmlspecialchars($sec['name']) ?></td>
                                <td><?= htmlspecialchars($sec['class_teacher'] ?: 'Not Assigned') ?></td>
                                <td><code><?= htmlspecialchars($sec['capacity']) ?> students</code></td>
                                <td>
                                    <span class="badge <?= $sec['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= ucfirst($sec['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Section Modal -->
<div class="modal fade" id="createSectionModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <?= getCSRFInput() ?>
            <input type="hidden" name="action" value="create">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-semibold">Standard <span class="text-danger">*</span></label>
                        <select name="standard_id" class="form-select" required>
                            <option value="">Select Standard</option>
                            <?php foreach ($standards as $std): ?>
                                <option value="<?= $std['id'] ?>"><?= htmlspecialchars($std['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Section Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. A, B, or Alpha">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Class Teacher</label>
                        <input type="text" name="class_teacher" class="form-control" placeholder="e.g. Mr. David Miller">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Capacity</label>
                        <input type="number" name="capacity" class="form-control" value="40" min="1" max="200">
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
                    <button type="submit" class="btn btn-indigo">Save Section</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
