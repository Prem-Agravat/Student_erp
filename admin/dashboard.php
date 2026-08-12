<?php
// C:\xampp\htdocs\school-erp\admin\dashboard.php

$activePage = 'dashboard';
require_once __DIR__ . '/../includes/super_admin_auth.php';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();

// Get counts
$totalSchools = $db->query("SELECT COUNT(*) FROM schools")->fetchColumn();
$pendingSchools = $db->query("SELECT COUNT(*) FROM schools WHERE status = 'pending'")->fetchColumn();
$approvedSchools = $db->query("SELECT COUNT(*) FROM schools WHERE status = 'approved'")->fetchColumn();
$suspendedSchools = $db->query("SELECT COUNT(*) FROM schools WHERE status = 'suspended'")->fetchColumn();
$totalStudents = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();

// Get recent registrations
$stmt = $db->query("SELECT * FROM schools ORDER BY id DESC LIMIT 5");
$recentSchools = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">Platform Overview</h2>
            <p class="text-secondary">Welcome, Super Admin. Monitor and manage registered school systems.</p>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm glass-card p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted fw-bold" style="font-size: 13px;">TOTAL SCHOOLS</span>
                        <h2 class="fw-bold mt-2 mb-0"><?= $totalSchools ?></h2>
                    </div>
                    <div class="bg-indigo bg-opacity-10 text-indigo rounded-3 p-3" style="font-size: 24px;">
                        <i class="fa-solid fa-school"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm glass-card p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-warning fw-bold" style="font-size: 13px;">PENDING APPROVAL</span>
                        <h2 class="fw-bold mt-2 mb-0 text-warning"><?= $pendingSchools ?></h2>
                    </div>
                    <div class="bg-warning bg-opacity-10 text-warning rounded-3 p-3" style="font-size: 24px;">
                        <i class="fa-solid fa-envelope-open-text"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm glass-card p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-success fw-bold" style="font-size: 13px;">ACTIVE SCHOOLS</span>
                        <h2 class="fw-bold mt-2 mb-0 text-success"><?= $approvedSchools ?></h2>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success rounded-3 p-3" style="font-size: 24px;">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm glass-card p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted fw-bold" style="font-size: 13px;">TOTAL STUDENTS</span>
                        <h2 class="fw-bold mt-2 mb-0"><?= $totalStudents ?></h2>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3" style="font-size: 24px;">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Chart Column -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4 h-100 glass-card">
                <h5 class="fw-bold mb-4">School Registration Metrics</h5>
                <div style="height: 300px;">
                    <canvas id="registrationChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Recent Registrations List -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4 h-100 glass-card">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h5 class="fw-bold mb-0">Recent Registrations</h5>
                    <a href="school-requests.php" class="btn btn-indigo btn-sm">View Requests</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle custom-table">
                        <thead>
                            <tr>
                                <th>School Name</th>
                                <th>Code</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentSchools)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No registration records found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentSchools as $school): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($school['school_name']) ?></td>
                                        <td><code><?= htmlspecialchars($school['school_code']) ?></code></td>
                                        <td>
                                            <span class="status-badge status-<?= $school['status'] ?>">
                                                <?= ucfirst($school['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="school-view.php?id=<?= $school['id'] ?>" class="btn btn-light btn-sm rounded-circle"><i class="fa-solid fa-eye"></i></a>
                                        </td>
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

<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('registrationChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Approved', 'Pending', 'Suspended'],
            datasets: [{
                data: [<?= $approvedSchools ?>, <?= $pendingSchools ?>, <?= $suspendedSchools ?>],
                backgroundColor: ['#10b981', '#f59e0b', '#6b7280'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
