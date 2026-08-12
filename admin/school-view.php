<?php
// C:\xampp\htdocs\school-erp\admin\school-view.php

$activePage = 'schools';
require_once __DIR__ . '/../includes/super_admin_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$school_id = intval($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM schools WHERE id = ?");
$stmt->execute([$school_id]);
$school = $stmt->fetch();

if (!$school) {
    echo '<div class="alert alert-danger">School record not found.</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Fetch admin account details
$stmtAdmin = $db->prepare("SELECT * FROM school_admins WHERE school_id = ?");
$stmtAdmin->execute([$school_id]);
$admin = $stmtAdmin->fetch();

$message = '';
// Handle Actions inside detailed view
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF validation failed.");
    }
    
    $action = $_POST['action'];
    $reason = sanitizeInput($_POST['rejection_reason'] ?? '');
    
    if ($action === 'approve') {
        $db->prepare("UPDATE schools SET status = 'approved' WHERE id = ?")->execute([$school_id]);
        logActivity("Approve School", "Approved school: " . $school['school_name']);
        $school['status'] = 'approved';
        $message = getAlert('success', "School approved successfully.");
    } elseif ($action === 'suspend') {
        $db->prepare("UPDATE schools SET status = 'suspended' WHERE id = ?")->execute([$school_id]);
        logActivity("Suspend School", "Suspended school: " . $school['school_name']);
        $school['status'] = 'suspended';
        $message = getAlert('warning', "School account has been suspended.");
    } elseif ($action === 'reject') {
        $db->prepare("UPDATE schools SET status = 'rejected', rejection_reason = ? WHERE id = ?")->execute([$reason, $school_id]);
        logActivity("Reject School", "Rejected school: " . $school['school_name'] . " - Reason: " . $reason);
        $school['status'] = 'rejected';
        $school['rejection_reason'] = $reason;
        $message = getAlert('warning', "School registration rejected.");
    } elseif ($action === 'activate') {
        $db->prepare("UPDATE schools SET status = 'approved' WHERE id = ?")->execute([$school_id]);
        logActivity("Activate School", "Re-activated school: " . $school['school_name']);
        $school['status'] = 'approved';
        $message = getAlert('success', "School re-activated successfully.");
    }
}
?>

<div class="container-fluid">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="schools.php" class="btn btn-light rounded-circle shadow-sm border"><i class="fa-solid fa-arrow-left text-secondary"></i></a>
        <div>
            <h2 class="fw-bold mb-0"><?= htmlspecialchars($school['school_name']) ?></h2>
            <p class="text-secondary mb-0">Detailed tenant review & management.</p>
        </div>
    </div>
    
    <?= $message ?>
    
    <div class="row g-4">
        <!-- Main details -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 mb-4 glass-card">
                <div class="d-flex align-items-center gap-4 border-bottom pb-4 mb-4">
                    <?php if (!empty($school['logo'])): ?>
                        <img src="<?= UPLOAD_URL ?>logos/<?= $school['logo'] ?>" alt="Logo" class="rounded-4 border shadow-sm" style="width: 90px; height: 90px; object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-indigo bg-opacity-10 text-indigo rounded-4 d-flex align-items-center justify-content-center fw-bold" style="width: 90px; height: 90px; font-size: 32px;">
                            <?= strtoupper(substr($school['school_name'], 0, 2)) ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h4 class="fw-bold mb-1"><?= htmlspecialchars($school['school_name']) ?></h4>
                        <div class="d-flex align-items-center gap-2">
                            <code>Code: <?= htmlspecialchars($school['school_code']) ?></code>
                            <span class="status-badge status-<?= $school['status'] ?>"><?= ucfirst($school['status']) ?></span>
                        </div>
                    </div>
                </div>
                
                <h5 class="fw-bold mb-3 text-indigo border-bottom pb-2">School Demographics</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Board</small>
                        <span class="fw-bold"><?= htmlspecialchars($school['board']) ?></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Medium</small>
                        <span class="fw-bold"><?= htmlspecialchars($school['medium']) ?></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Established Year</small>
                        <span class="fw-bold"><?= htmlspecialchars($school['established_year']) ?></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">School Type</small>
                        <span class="fw-bold"><?= htmlspecialchars($school['school_type']) ?></span>
                    </div>
                    <div class="col-md-8">
                        <small class="text-muted d-block">Website</small>
                        <span class="fw-bold"><?= $school['website'] ? '<a href="'.htmlspecialchars($school['website']).'" target="_blank">'.htmlspecialchars($school['website']).'</a>' : 'N/A' ?></span>
                    </div>
                </div>
                
                <h5 class="fw-bold mb-3 text-indigo border-bottom pb-2">Principal Details</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Principal Name</small>
                        <span class="fw-bold"><?= htmlspecialchars($school['principal_name']) ?></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Principal Email</small>
                        <span class="fw-bold"><?= htmlspecialchars($school['principal_email']) ?></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Principal Phone</small>
                        <span class="fw-bold"><?= htmlspecialchars($school['principal_phone']) ?></span>
                    </div>
                </div>
                
                <h5 class="fw-bold mb-3 text-indigo border-bottom pb-2">Address & Location</h5>
                <div class="row g-3">
                    <div class="col-12">
                        <small class="text-muted d-block">Address</small>
                        <span class="fw-bold"><?= htmlspecialchars($school['address']) ?></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">City</small>
                        <span class="fw-bold"><?= htmlspecialchars($school['city']) ?></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">State</small>
                        <span class="fw-bold"><?= htmlspecialchars($school['state']) ?></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Pincode</small>
                        <span class="fw-bold"><?= htmlspecialchars($school['pincode']) ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar controls & Admin Account details -->
        <div class="col-lg-4">
            <!-- School Admin Account Card -->
            <div class="card border-0 shadow-sm p-4 mb-4 glass-card">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-user-shield text-indigo me-2"></i>Admin Account</h5>
                <?php if ($admin): ?>
                    <div class="mb-3">
                        <small class="text-muted d-block">Admin Username</small>
                        <span class="fw-bold"><code><?= htmlspecialchars($admin['username']) ?></code></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Admin Email</small>
                        <span class="fw-bold"><?= htmlspecialchars($admin['admin_email']) ?></span>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">Admin Account Name</small>
                        <span class="fw-bold"><?= htmlspecialchars($admin['admin_name']) ?></span>
                    </div>
                <?php else: ?>
                    <div class="text-muted">No associated admin account found.</div>
                <?php endif; ?>
            </div>
            
            <!-- Actions Card -->
            <div class="card border-0 shadow-sm p-4 glass-card">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-sliders text-indigo me-2"></i>Actions</h5>
                <div class="d-flex flex-column gap-3">
                    <?php if ($school['status'] === 'pending'): ?>
                        <form method="POST">
                            <?= getCSRFInput() ?>
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-success w-100 rounded-pill"><i class="fa-solid fa-check me-2"></i>Approve School</button>
                        </form>
                        
                        <button class="btn btn-danger w-100 rounded-pill" data-bs-toggle="modal" data-bs-target="#rejectModal"><i class="fa-solid fa-xmark me-2"></i>Reject Registration</button>
                    <?php elseif ($school['status'] === 'approved'): ?>
                        <form method="POST">
                            <?= getCSRFInput() ?>
                            <input type="hidden" name="action" value="suspend">
                            <button type="submit" class="btn btn-warning w-100 rounded-pill text-white" onclick="return confirm('Suspend this school?')"><i class="fa-solid fa-ban me-2"></i>Suspend School</button>
                        </form>
                    <?php elseif ($school['status'] === 'suspended'): ?>
                        <form method="POST">
                            <?= getCSRFInput() ?>
                            <input type="hidden" name="action" value="activate">
                            <button type="submit" class="btn btn-success w-100 rounded-pill" onclick="return confirm('Re-activate school account?')"><i class="fa-solid fa-check me-2"></i>Re-activate School</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <?= getCSRFInput() ?>
            <input type="hidden" name="action" value="reject">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Reject School Registration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason</label>
                        <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Enter why this registration is rejected..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Reject</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
