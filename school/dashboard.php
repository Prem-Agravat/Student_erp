<?php
// C:\xampp\htdocs\school-erp\school\dashboard.php

$activePage = 'dashboard';
require_once __DIR__ . '/../includes/school_auth.php';
require_once __DIR__ . '/../includes/header.php';

$school_id = $_SESSION['school_id'];
$db = getDBConnection();

// Get counts filtered by school_id
$totalStudents = $db->query("SELECT COUNT(*) FROM students WHERE school_id = $school_id")->fetchColumn();
$totalStandards = $db->query("SELECT COUNT(*) FROM standards WHERE school_id = $school_id")->fetchColumn();
$totalSections = $db->query("SELECT COUNT(*) FROM sections WHERE school_id = $school_id")->fetchColumn();
$totalSubjects = $db->query("SELECT COUNT(*) FROM subjects WHERE school_id = $school_id")->fetchColumn();

// Fetch today's attendance summary
$today = date('Y-m-d');
$stmtAtt = $db->prepare("SELECT status, COUNT(*) as count FROM attendance WHERE school_id = ? AND date = ? GROUP BY status");
$stmtAtt->execute([$school_id, $today]);
$attSummary = $stmtAtt->fetchAll();

$presentCount = 0;
$absentCount = 0;
foreach ($attSummary as $att) {
    if ($att['status'] === 'Present') $presentCount = $att['count'];
    if ($att['status'] === 'Absent') $absentCount = $att['count'];
}

// Fetch upcoming exams
$stmtExams = $db->prepare("SELECT * FROM exams WHERE school_id = ? AND start_date >= ? ORDER BY start_date ASC LIMIT 3");
$stmtExams->execute([$school_id, $today]);
$upcomingExams = $stmtExams->fetchAll();

// Fetch recent notices
$stmtNotices = $db->prepare("SELECT * FROM notices WHERE school_id = ? AND (expiry_date IS NULL OR expiry_date >= ?) ORDER BY id DESC LIMIT 3");
$stmtNotices->execute([$school_id, $today]);
$recentNotices = $stmtNotices->fetchAll();

// Fetch Student distribution by standard for chart
$stmtDist = $db->prepare("SELECT std.name as standard, COUNT(s.id) as count FROM standards std LEFT JOIN students s ON s.standard_id = std.id WHERE std.school_id = ? GROUP BY std.id");
$stmtDist->execute([$school_id]);
$stdDist = $stmtDist->fetchAll();

$chartLabels = [];
$chartData = [];
foreach ($stdDist as $dist) {
    $chartLabels[] = $dist['standard'];
    $chartData[] = $dist['count'];
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">School Admin Dashboard</h2>
            <p class="text-secondary">Manage classrooms, student enrollments, exam records, fees and notices.</p>
        </div>
    </div>
    
    <!-- Quick Actions Card -->
    <div class="card border-0 shadow-sm p-4 mb-4 glass-card bg-indigo text-white" style="background: var(--primary-gradient);">
        <h5 class="fw-bold mb-3"><i class="fa-solid fa-bolt me-2"></i>Quick Actions</h5>
        <div class="d-flex flex-wrap gap-3">
            <a href="students.php" class="btn btn-light rounded-pill px-4"><i class="fa-solid fa-user-plus text-indigo me-2"></i>Add Student</a>
            <a href="attendance.php" class="btn btn-light rounded-pill px-4"><i class="fa-solid fa-calendar-check text-indigo me-2"></i>Mark Attendance</a>
            <a href="exams.php" class="btn btn-light rounded-pill px-4"><i class="fa-solid fa-file-signature text-indigo me-2"></i>Configure Exams</a>
            <a href="notices.php" class="btn btn-light rounded-pill px-4"><i class="fa-solid fa-bullhorn text-indigo me-2"></i>Publish Notice</a>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm glass-card p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted fw-bold" style="font-size: 13px;">ENROLLED STUDENTS</span>
                        <h2 class="fw-bold mt-2 mb-0"><?= $totalStudents ?></h2>
                    </div>
                    <div class="bg-indigo bg-opacity-10 text-indigo rounded-3 p-3" style="font-size: 24px;">
                        <i class="fa-solid fa-user-graduate"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm glass-card p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted fw-bold" style="font-size: 13px;">STANDARDS</span>
                        <h2 class="fw-bold mt-2 mb-0"><?= $totalStandards ?></h2>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3" style="font-size: 24px;">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm glass-card p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted fw-bold" style="font-size: 13px;">SECTIONS</span>
                        <h2 class="fw-bold mt-2 mb-0"><?= $totalSections ?></h2>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success rounded-3 p-3" style="font-size: 24px;">
                        <i class="fa-solid fa-cubes"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm glass-card p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted fw-bold" style="font-size: 13px;">TOTAL SUBJECTS</span>
                        <h2 class="fw-bold mt-2 mb-0"><?= $totalSubjects ?></h2>
                    </div>
                    <div class="bg-danger bg-opacity-10 text-danger rounded-3 p-3" style="font-size: 24px;">
                        <i class="fa-solid fa-book"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4 mb-4">
        <!-- Chart Column -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4 h-100 glass-card">
                <h5 class="fw-bold mb-4">Student Density by Standard</h5>
                <div style="height: 300px;">
                    <?php if (empty($chartLabels)): ?>
                        <div class="h-100 d-flex align-items-center justify-content-center text-muted">No standards configured yet.</div>
                    <?php else: ?>
                        <canvas id="densityChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Today's Attendance Summary -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4 h-100 glass-card">
                <h5 class="fw-bold mb-4">Today's Attendance Status (<?= date('Y-m-d') ?>)</h5>
                <div class="row align-items-center h-100">
                    <div class="col-md-6 text-center">
                        <h1 class="display-3 fw-bold text-indigo mb-0"><?= ($presentCount + $absentCount) > 0 ? round(($presentCount / ($presentCount + $absentCount)) * 100) : 0 ?>%</h1>
                        <small class="text-muted">Present Percentage</small>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                <span>Present Students</span>
                                <span class="badge bg-success font-semibold"><?= $presentCount ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                <span>Absent Students</span>
                                <span class="badge bg-danger font-semibold"><?= $absentCount ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Not Marked</span>
                                <span class="badge bg-secondary font-semibold"><?= max(0, $totalStudents - ($presentCount + $absentCount)) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Upcoming Exams -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 h-100 glass-card">
                <h5 class="fw-bold mb-4"><i class="fa-solid fa-file-signature text-indigo me-2"></i>Upcoming Examinations</h5>
                <div class="list-group list-group-flush">
                    <?php if (empty($upcomingExams)): ?>
                        <div class="text-muted py-3 text-center">No upcoming exams scheduled.</div>
                    <?php else: ?>
                        <?php foreach ($upcomingExams as $exam): ?>
                            <div class="list-group-item bg-transparent border-0 px-0 py-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($exam['exam_name']) ?></div>
                                    <small class="text-muted">Start Date: <?= date('Y-m-d', strtotime($exam['start_date'])) ?></small>
                                </div>
                                <span class="badge bg-indigo"><?= htmlspecialchars($exam['status']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Notices -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 h-100 glass-card">
                <h5 class="fw-bold mb-4"><i class="fa-solid fa-bullhorn text-indigo me-2"></i>Recent Notices</h5>
                <div class="list-group list-group-flush">
                    <?php if (empty($recentNotices)): ?>
                        <div class="text-muted py-3 text-center">No notices published recently.</div>
                    <?php else: ?>
                        <?php foreach ($recentNotices as $notice): ?>
                            <div class="list-group-item bg-transparent border-0 px-0 py-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="fw-bold"><?= htmlspecialchars($notice['title']) ?></div>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($notice['category']) ?></span>
                                </div>
                                <p class="text-secondary mb-0" style="font-size: 13px;"><?= htmlspecialchars(substr($notice['description'], 0, 100)) ?>...</p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($chartLabels)): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('densityChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'No. of Students',
                data: <?= json_encode($chartData) ?>,
                backgroundColor: '#6366f1',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
