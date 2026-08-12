<?php
// C:\xampp\htdocs\school-erp\school\subjects.php

$activePage = 'subjects';
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
    echo '<p class="mb-3">You must configure Standards before managing Subjects or mapping them to classes.</p>';
    echo '<a href="standards.php" class="btn btn-indigo rounded-pill px-4">Manage Standards</a>';
    echo '</div>';
    echo '</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF validation failed.");
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_subject') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $stream = sanitizeInput($_POST['stream'] ?? 'General');
        $status = sanitizeInput($_POST['status'] ?? 'active');
        
        if (empty($name)) {
            $message = getAlert('danger', "Subject name is required.");
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO subjects (school_id, name, stream, status) VALUES (?, ?, ?, ?)");
                $stmt->execute([$school_id, $name, $stream, $status]);
                logActivity("Create Subject", "Created subject: $name ($stream)");
                $message = getAlert('success', "Subject '$name' added successfully.");
            } catch (PDOException $e) {
                $message = getAlert('danger', "Failed to add subject: " . $e->getMessage());
            }
        }
    } elseif ($action === 'map_subject') {
        $standard_id = intval($_POST['standard_id'] ?? 0);
        $subject_ids = $_POST['subject_ids'] ?? [];
        
        if ($standard_id <= 0 || empty($subject_ids)) {
            $message = getAlert('danger', "Standard and at least one Subject are required for mapping.");
        } else {
            try {
                $db->beginTransaction();
                
                // Clear existing mapping for standard to prevent duplicates
                $stmtClear = $db->prepare("DELETE FROM standard_subjects WHERE school_id = ? AND standard_id = ?");
                $stmtClear->execute([$school_id, $standard_id]);
                
                // Insert new mappings
                $stmtInsert = $db->prepare("INSERT INTO standard_subjects (school_id, standard_id, subject_id) VALUES (?, ?, ?)");
                foreach ($subject_ids as $sub_id) {
                    $stmtInsert->execute([$school_id, $standard_id, intval($sub_id)]);
                }
                
                $db->commit();
                logActivity("Map Subjects", "Mapped subjects to standard ID: $standard_id");
                $message = getAlert('success', "Subjects mapped to Standard successfully.");
            } catch (PDOException $e) {
                $db->rollBack();
                $message = getAlert('danger', "Failed to map subjects: " . $e->getMessage());
            }
        }
    }
}

// Fetch all subjects
$stmtSubjects = $db->prepare("SELECT * FROM subjects WHERE school_id = ? ORDER BY name ASC");
$stmtSubjects->execute([$school_id]);
$subjects = $stmtSubjects->fetchAll();

// Fetch mapping summary
$stmtMapping = $db->prepare("
    SELECT std.name as standard_name, GROUP_CONCAT(sub.name SEPARATOR ', ') as mapped_subjects 
    FROM standard_subjects ss 
    JOIN standards std ON ss.standard_id = std.id 
    JOIN subjects sub ON ss.subject_id = sub.id 
    WHERE ss.school_id = ? 
    GROUP BY std.id 
    ORDER BY std.display_order ASC
");
$stmtMapping->execute([$school_id]);
$mappings = $stmtMapping->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0">Subjects Setup</h2>
                <p class="text-secondary mb-0">Create subjects and associate them with standard classroom levels.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-indigo rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createSubjectModal"><i class="fa-solid fa-plus me-2"></i>Add Subject</button>
                <button class="btn btn-indigo rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#mapSubjectModal"><i class="fa-solid fa-link me-2"></i>Map to Standard</button>
            </div>
        </div>
    </div>
    
    <?= $message ?>
    
    <div class="row g-4">
        <!-- Subjects List Column -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 glass-card h-100">
                <h5 class="fw-bold mb-4"><i class="fa-solid fa-book-open text-indigo me-2"></i>School Subjects</h5>
                <div class="table-responsive">
                    <table class="table align-middle custom-table">
                        <thead>
                            <tr>
                                <th>Subject Name</th>
                                <th>Stream</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($subjects)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">No subjects created yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($subjects as $sub): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($sub['name']) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($sub['stream']) ?></span></td>
                                        <td>
                                            <span class="badge <?= $sub['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= ucfirst($sub['status']) ?>
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
        
        <!-- Standard Mappings Column -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 glass-card h-100">
                <h5 class="fw-bold mb-4"><i class="fa-solid fa-school-flag text-indigo me-2"></i>Class Mappings</h5>
                <div class="table-responsive">
                    <table class="table align-middle custom-table">
                        <thead>
                            <tr>
                                <th>Standard</th>
                                <th>Mapped Subjects</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mappings)): ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-3">No subjects mapped to standards yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($mappings as $map): ?>
                                    <tr>
                                        <td class="fw-bold text-indigo" style="width: 150px;"><?= htmlspecialchars($map['standard_name']) ?></td>
                                        <td><span class="text-secondary"><?= htmlspecialchars($map['mapped_subjects']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Subject Modal -->
<div class="modal fade" id="createSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <?= getCSRFInput() ?>
            <input type="hidden" name="action" value="create_subject">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-semibold">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Mathematics, Science, Biology">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Academic Stream</label>
                        <select name="stream" class="form-select">
                            <option value="General">General / All</option>
                            <option value="Science">Science Stream</option>
                            <option value="Commerce">Commerce Stream</option>
                            <option value="Arts">Arts Stream</option>
                        </select>
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
                    <button type="submit" class="btn btn-indigo">Save Subject</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Map Subject Modal -->
<div class="modal fade" id="mapSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <?= getCSRFInput() ?>
            <input type="hidden" name="action" value="map_subject">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Map Subjects to Standard</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-semibold">Standard / Class <span class="text-danger">*</span></label>
                        <select name="standard_id" class="form-select" required>
                            <option value="">Select Standard</option>
                            <?php foreach ($standards as $std): ?>
                                <option value="<?= $std['id'] ?>"><?= htmlspecialchars($std['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Select Subjects <span class="text-danger">*</span></label>
                        <div class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                            <?php if (empty($subjects)): ?>
                                <span class="text-muted">No active subjects available.</span>
                            <?php else: ?>
                                <?php foreach ($subjects as $sub): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="subject_ids[]" value="<?= $sub['id'] ?>" id="subcheck<?= $sub['id'] ?>">
                                        <label class="form-check-label" for="subcheck<?= $sub['id'] ?>">
                                            <?= htmlspecialchars($sub['name']) ?> <small class="text-muted">(<?= htmlspecialchars($sub['stream']) ?>)</small>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-indigo">Save Mapping</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
