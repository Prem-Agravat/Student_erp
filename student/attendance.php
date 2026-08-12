<?php
// C:\xampp\htdocs\school-erp\student\attendance.php

$activePage = 'attendance';
require_once __DIR__ . '/../includes/student_auth.php';
require_once __DIR__ . '/../includes/header.php';

$student_id = $_SESSION['student_id'];
$school_id = $_SESSION['school_id'];
$db = getDBConnection();

// Fetch summary metrics
$stmtSum = $db->prepare("
    SELECT 
        COUNT(id) as total,
        SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status='Absent' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN status='Late' THEN 1 ELSE 0 END) as late,
        SUM(CASE WHEN status='Leave' THEN 1 ELSE 0 END) as leave_count
    FROM attendance 
    WHERE student_id = ? AND school_id = ?
");
$stmtSum->execute([$student_id, $school_id]);
$metrics = $stmtSum->fetch();

$total = intval($metrics['total']);
$present = intval($metrics['present']);
$absent = intval($metrics['absent']);
$late = intval($metrics['late']);
$leave = intval($metrics['leave_count']);

$percent = $total > 0 ? round((($present + $late) / $total) * 100) : 100;

// Fetch daily logs
$stmtLogs = $db->prepare("SELECT date, status, remarks FROM attendance WHERE student_id = ? AND school_id = ? ORDER BY date DESC LIMIT 60");
$stmtLogs->execute([$student_id, $school_id]);
$logs = $stmtLogs->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">My Attendance Records</h2>
            <p class="text-secondary">Track daily check-ins, leaves, and overall percentage reports.</p>
        </div>
    </div>
    
    <!-- Stats Row -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm glass-card p-4 text-center">
                <span class="text-muted fw-bold" style="font-size: 13px;">ATTENDANCE PERCENT</span>
                <h1 class="display-4 fw-bold mt-2 mb-0 <?= $percent >= 75 ? 'text-success' : 'text-danger' ?>"><?= $percent ?>%</h1>
                <small class="text-muted mt-2 d-block">Target: Minimum 75%</small>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card border-0 shadow-sm glass-card p-4 h-100 d-flex flex-column justify-content-center">
                <div class="row text-center g-3">
                    <div class="col-md-3 col-6 border-end">
                        <small class="text-muted d-block font-semibold">Total Days Tracked</small>
                        <h3 class="fw-bold mt-1 text-dark mb-0"><?= $total ?> days</h3>
                    </div>
                    <div class="col-md-3 col-6 border-end">
                        <small class="text-muted d-block font-semibold">Days Present</small>
                        <h3 class="fw-bold mt-1 text-success mb-0"><?= $present ?> days</h3>
                    </div>
                    <div class="col-md-2 col-4 border-end">
                        <small class="text-muted d-block font-semibold">Absent</small>
                        <h3 class="fw-bold mt-1 text-danger mb-0"><?= $absent ?> days</h3>
                    </div>
                    <div class="col-md-2 col-4 border-end">
                        <small class="text-muted d-block font-semibold">Late Arrivals</small>
                        <h3 class="fw-bold mt-1 text-warning mb-0"><?= $late ?> days</h3>
                    </div>
                    <div class="col-md-2 col-4">
                        <small class="text-muted d-block font-semibold">Leaves</small>
                        <h3 class="fw-bold mt-1 text-info mb-0"><?= $leave ?> days</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Daily Checkins Card -->
    <div class="card border-0 shadow-sm p-4 glass-card">
        <h5 class="fw-bold mb-4">Daily Attendance History</h5>
        <div class="table-responsive">
            <table class="table align-middle custom-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Status</th>
                        <th>Remarks / Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No attendance checks logged yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($log['date']) ?></td>
                                <td><?= date('l', strtotime($log['date'])) ?></td>
                                <td>
                                    <?php 
                                    $bg = $log['status'] === 'Present' ? 'bg-success' : ($log['status'] === 'Absent' ? 'bg-danger' : ($log['status'] === 'Late' ? 'bg-warning' : 'bg-info'));
                                    ?>
                                    <span class="badge <?= $bg ?> px-3 py-2 font-semibold" style="font-size: 12px;"><?= htmlspecialchars($log['status']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($log['remarks'] ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
