<?php
// C:\xampp\htdocs\school-erp\school\logs.php

$activePage = 'logs';
require_once __DIR__ . '/../includes/school_auth.php';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$school_id = $_SESSION['school_id'];

// Fetch activity logs related to this school only
$stmtLogs = $db->prepare("SELECT * FROM activity_logs WHERE school_id = ? ORDER BY id DESC LIMIT 100");
$stmtLogs->execute([$school_id]);
$logs = $stmtLogs->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">School Activity Logs</h2>
            <p class="text-secondary">Track internal audits, student enrollments, exam publish modifications, and attendance checks.</p>
        </div>
    </div>
    
    <div class="card border-0 shadow-sm p-4 glass-card">
        <div class="table-responsive">
            <table class="table align-middle custom-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User Role</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No activity logs found for your school yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></td>
                                <td>
                                    <span class="badge <?= $log['role'] === ROLE_SCHOOL_ADMIN ? 'bg-indigo' : 'bg-primary' ?>">
                                        <?= htmlspecialchars($log['role']) ?>
                                    </span>
                                </td>
                                <td><strong class="text-dark"><?= htmlspecialchars($log['action']) ?></strong></td>
                                <td><?= htmlspecialchars($log['description']) ?></td>
                                <td><code><?= htmlspecialchars($log['ip_address']) ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
