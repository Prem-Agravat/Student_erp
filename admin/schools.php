<?php
// C:\xampp\htdocs\school-erp\admin\schools.php

$activePage = 'schools';
require_once __DIR__ . '/../includes/super_admin_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$message = '';

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF validation failed.");
    }
    
    $school_id = intval($_POST['school_id'] ?? 0);
    $action = $_POST['action'];
    
    if ($school_id > 0) {
        $stmt = $db->prepare("SELECT school_name, school_code FROM schools WHERE id = ?");
        $stmt->execute([$school_id]);
        $school = $stmt->fetch();
        
        if ($school) {
            $school_name = $school['school_name'];
            $school_code = $school['school_code'];
            
            if ($action === 'suspend') {
                $stmtUpdate = $db->prepare("UPDATE schools SET status = 'suspended' WHERE id = ?");
                $stmtUpdate->execute([$school_id]);
                logActivity("Suspend School", "Suspended school: $school_name ($school_code)");
                $message = getAlert('warning', "School '$school_name' suspended successfully.");
            } elseif ($action === 'activate') {
                $stmtUpdate = $db->prepare("UPDATE schools SET status = 'approved' WHERE id = ?");
                $stmtUpdate->execute([$school_id]);
                logActivity("Activate School", "Re-activated school: $school_name ($school_code)");
                $message = getAlert('success', "School '$school_name' activated successfully.");
            }
        }
    }
}

// Search and Filtering Parameters
$search = sanitizeInput($_GET['search'] ?? '');
$statusFilter = sanitizeInput($_GET['status'] ?? '');

$query = "SELECT * FROM schools WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (school_name LIKE ? OR school_code LIKE ? OR email LIKE ? OR city LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($statusFilter)) {
    $query .= " AND status = ?";
    $params[] = $statusFilter;
}

$query .= " ORDER BY id DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$schools = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">All Schools</h2>
            <p class="text-secondary">Search, audit, activate, and suspend school tenants.</p>
        </div>
    </div>
    
    <?= $message ?>
    
    <!-- Search & Filter Card -->
    <div class="card border-0 shadow-sm p-4 mb-4 glass-card">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label font-semibold">Search Schools</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-start-0 rounded-end-3" placeholder="Search by name, code, email, city..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label font-semibold">Filter by Status</label>
                <select name="status" class="form-select bg-light">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved / Active</option>
                    <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-indigo w-100 rounded-pill"><i class="fa-solid fa-filter me-2"></i>Filter</button>
                <a href="schools.php" class="btn btn-light border w-100 rounded-pill"><i class="fa-solid fa-arrow-rotate-left me-2"></i>Reset</a>
            </div>
        </form>
    </div>
    
    <!-- Schools List Card -->
    <div class="card border-0 shadow-sm p-4 glass-card">
        <div class="table-responsive">
            <table class="table align-middle custom-table">
                <thead>
                    <tr>
                        <th>School Name</th>
                        <th>Code</th>
                        <th>Board / Medium</th>
                        <th>Contact Email</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($schools)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No schools match the filter criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($schools as $school): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (!empty($school['logo'])): ?>
                                            <img src="<?= UPLOAD_URL ?>logos/<?= $school['logo'] ?>" alt="Logo" class="rounded-circle border" style="width: 40px; height: 40px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-indigo bg-opacity-10 text-indigo rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                                                <?= strtoupper(substr($school['school_name'], 0, 2)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($school['school_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($school['city']) . ', ' . htmlspecialchars($school['state']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><code><?= htmlspecialchars($school['school_code']) ?></code></td>
                                <td>
                                    <div><?= htmlspecialchars($school['board']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($school['medium']) ?> Medium</small>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($school['email']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($school['phone']) ?></small>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $school['status'] ?>">
                                        <?= ucfirst($school['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="school-view.php?id=<?= $school['id'] ?>" class="btn btn-light btn-sm" title="View details"><i class="fa-solid fa-eye text-indigo"></i></a>
                                        
                                        <?php if ($school['status'] === 'approved'): ?>
                                            <form method="POST" class="d-inline">
                                                <?= getCSRFInput() ?>
                                                <input type="hidden" name="school_id" value="<?= $school['id'] ?>">
                                                <input type="hidden" name="action" value="suspend">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to suspend this school? Users will not be able to log in.')" title="Suspend"><i class="fa-solid fa-ban"></i></button>
                                            </form>
                                        <?php elseif ($school['status'] === 'suspended'): ?>
                                            <form method="POST" class="d-inline">
                                                <?= getCSRFInput() ?>
                                                <input type="hidden" name="school_id" value="<?= $school['id'] ?>">
                                                <input type="hidden" name="action" value="activate">
                                                <button type="submit" class="btn btn-outline-success btn-sm" onclick="return confirm('Re-activate this school account?')" title="Activate"><i class="fa-solid fa-check"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
