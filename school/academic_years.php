<?php
// C:\xampp\htdocs\school-erp\school\academic_years.php

$activePage = 'academic_years';
require_once __DIR__ . '/../includes/school_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$school_id = $_SESSION['school_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $start_date = sanitizeInput($_POST['start_date'] ?? '');
        $end_date = sanitizeInput($_POST['end_date'] ?? '');
        $status = sanitizeInput($_POST['status'] ?? 'inactive');
        
        if (empty($name) || empty($start_date) || empty($end_date)) {
            $message = getAlert('danger', "All fields are required.");
        } else {
            try {
                $db->beginTransaction();
                
                // If status is active, deactivate others first
                if ($status === 'active') {
                    $stmtDeact = $db->prepare("UPDATE academic_years SET status = 'inactive' WHERE school_id = ?");
                    $stmtDeact->execute([$school_id]);
                }
                
                $stmt = $db->prepare("INSERT INTO academic_years (school_id, name, start_date, end_date, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$school_id, $name, $start_date, $end_date, $status]);
                
                $db->commit();
                logActivity("Create Academic Year", "Created academic year: $name");
                $message = getAlert('success', "Academic Year '$name' created successfully.");
            } catch (PDOException $e) {
                $db->rollBack();
                $message = getAlert('danger', "Failed to create academic year: " . $e->getMessage());
            }
        }
    } elseif ($action === 'activate') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $db->beginTransaction();
                
                // Deactivate all first
                $stmtDeact = $db->prepare("UPDATE academic_years SET status = 'inactive' WHERE school_id = ?");
                $stmtDeact->execute([$school_id]);
                
                // Activate selected
                $stmtAct = $db->prepare("UPDATE academic_years SET status = 'active' WHERE id = ? AND school_id = ?");
                $stmtAct->execute([$id, $school_id]);
                
                $db->commit();
                logActivity("Activate Academic Year", "Activated academic year ID: $id");
                $message = getAlert('success', "Academic Year activated successfully.");
            } catch (PDOException $e) {
                $db->rollBack();
                $message = getAlert('danger', "Failed to activate academic year: " . $e->getMessage());
            }
        }
    }
}

// Fetch academic years
$stmt = $db->prepare("SELECT * FROM academic_years WHERE school_id = ? ORDER BY name DESC");
$stmt->execute([$school_id]);
$years = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Academic Years</h2>
            <p class="text-secondary mb-0">Define academic cycles for student enrollment and reports.</p>
        </div>
        <button class="btn btn-indigo rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createYearModal"><i class="fa-solid fa-plus me-2"></i>Add Academic Year</button>
    </div>
    
    <?= $message ?>
    
    <div class="card border-0 shadow-sm p-4 glass-card">
        <div class="table-responsive">
            <table class="table align-middle custom-table">
                <thead>
                    <tr>
                        <th>Academic Year</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($years)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No academic years found. Click "Add Academic Year" to begin.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($years as $year): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($year['name']) ?></td>
                                <td><?= htmlspecialchars($year['start_date']) ?></td>
                                <td><?= htmlspecialchars($year['end_date']) ?></td>
                                <td>
                                    <span class="badge <?= $year['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= ucfirst($year['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($year['status'] !== 'active'): ?>
                                        <form method="POST" class="d-inline">
                                            <?= getCSRFInput() ?>
                                            <input type="hidden" name="action" value="activate">
                                            <input type="hidden" name="id" value="<?= $year['id'] ?>">
                                            <button type="submit" class="btn btn-light btn-sm text-indigo" title="Mark as Active"><i class="fa-solid fa-circle-check me-1"></i>Activate</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted"><i class="fa-solid fa-lock me-1"></i>Active</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Year Modal -->
<div class="modal fade" id="createYearModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <?= getCSRFInput() ?>
            <input type="hidden" name="action" value="create">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Academic Year</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-semibold">Academic Year Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. 2025-26">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="inactive">Inactive</option>
                            <option value="active">Active</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-indigo">Save Academic Year</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
