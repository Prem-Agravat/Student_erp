<?php
// C:\xampp\htdocs\school-erp\admin\school-requests.php

$activePage = 'requests';
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
    $reason = sanitizeInput($_POST['rejection_reason'] ?? '');
    
    if ($school_id > 0) {
        $stmt = $db->prepare("SELECT school_name, school_code FROM schools WHERE id = ?");
        $stmt->execute([$school_id]);
        $school = $stmt->fetch();
        
        if ($school) {
            $school_name = $school['school_name'];
            $school_code = $school['school_code'];
            
            if ($action === 'approve') {
                $stmtUpdate = $db->prepare("UPDATE schools SET status = 'approved' WHERE id = ?");
                $stmtUpdate->execute([$school_id]);
                logActivity("Approve School", "Approved school: $school_name ($school_code)");
                $message = getAlert('success', "School '$school_name' approved successfully.");
            } elseif ($action === 'reject') {
                $stmtUpdate = $db->prepare("UPDATE schools SET status = 'rejected', rejection_reason = ? WHERE id = ?");
                $stmtUpdate->execute([$reason, $school_id]);
                logActivity("Reject School", "Rejected school: $school_name ($school_code) - Reason: $reason");
                $message = getAlert('warning', "School '$school_name' registration rejected.");
            }
        }
    }
}

// Fetch pending schools
$stmt = $db->prepare("SELECT * FROM schools WHERE status = 'pending' ORDER BY id DESC");
$stmt->execute();
$requests = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">Pending School Registrations</h2>
            <p class="text-secondary">Review and approve new school registration requests.</p>
        </div>
    </div>
    
    <?= $message ?>
    
    <div class="card border-0 shadow-sm p-4 glass-card">
        <div class="table-responsive">
            <table class="table align-middle custom-table">
                <thead>
                    <tr>
                        <th>School Name</th>
                        <th>Code</th>
                        <th>Admin Name</th>
                        <th>Email / Phone</th>
                        <th>Board</th>
                        <th>Reg Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No pending registration requests found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($req['school_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($req['city']) . ', ' . htmlspecialchars($req['state']) ?></small>
                                </td>
                                <td><code><?= htmlspecialchars($req['school_code']) ?></code></td>
                                <td>
                                    <?php
                                    // Fetch Admin Name
                                    $stmtAdmin = $db->prepare("SELECT admin_name FROM school_admins WHERE school_id = ?");
                                    $stmtAdmin->execute([$req['id']]);
                                    echo htmlspecialchars($stmtAdmin->fetchColumn() ?? 'N/A');
                                    ?>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($req['email']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($req['phone']) ?></small>
                                </td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($req['board']) ?></span></td>
                                <td><?= date('Y-m-d', strtotime($req['created_at'])) ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="school-view.php?id=<?= $req['id'] ?>" class="btn btn-light btn-sm" title="View details"><i class="fa-solid fa-eye text-indigo"></i></a>
                                        
                                        <form method="POST" class="d-inline">
                                            <?= getCSRFInput() ?>
                                            <input type="hidden" name="school_id" value="<?= $req['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to approve this school admin account?')" title="Approve"><i class="fa-solid fa-check"></i></button>
                                        </form>
                                        
                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $req['id'] ?>" title="Reject"><i class="fa-solid fa-xmark"></i></button>
                                    </div>
                                    
                                    <!-- Reject Reason Modal -->
                                    <div class="modal fade" id="rejectModal<?= $req['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <form method="POST">
                                                <?= getCSRFInput() ?>
                                                <input type="hidden" name="school_id" value="<?= $req['id'] ?>">
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
