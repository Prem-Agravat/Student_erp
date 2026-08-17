<?php
// C:\xampp\htdocs\school-erp\school\attendance_report.php

$activePage = 'attendance_report';
require_once __DIR__ . '/../includes/school_auth.php';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$school_id = $_SESSION['school_id'];

// Check active academic year
$stmtYear = $db->prepare("SELECT id, name FROM academic_years WHERE school_id = ? AND status = 'active'");
$stmtYear->execute([$school_id]);
$activeYear = $stmtYear->fetch();

if (!$activeYear) {
    echo '<div class="container-fluid">';
    echo '<div class="alert alert-warning py-4 shadow-sm border-0 glass-card">';
    echo '<h5 class="fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Active Academic Year Required</h5>';
    echo '<p class="mb-3">Please configure and activate an Academic Year before viewing attendance reports.</p>';
    echo '<a href="academic_years.php" class="btn btn-indigo rounded-pill px-4">Manage Academic Years</a>';
    echo '</div>';
    echo '</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Fetch Standards and Sections for class filtering
$stmtStds = $db->prepare("SELECT id, name FROM standards WHERE school_id = ? AND status = 'active' ORDER BY display_order ASC");
$stmtStds->execute([$school_id]);
$standards = $stmtStds->fetchAll();

$stmtSecs = $db->prepare("SELECT id, name, standard_id FROM sections WHERE school_id = ? AND status = 'active' ORDER BY name ASC");
$stmtSecs->execute([$school_id]);
$sections = $stmtSecs->fetchAll();

// Handle filter inputs
$std_id = intval($_GET['standard_id'] ?? 0);
$sec_id = 0;
if ($std_id > 0) {
    $sec_id = getOrInsertDefaultSectionId($db, $school_id, $std_id);
}

$report = [];
if ($std_id > 0) {
    // Fetch student-wise attendance summary
    $stmtRep = $db->prepare("
        SELECT s.first_name, s.last_name, s.roll_number, s.student_id,
               COUNT(att.id) as total, 
               SUM(CASE WHEN att.status='Present' THEN 1 ELSE 0 END) as present,
               SUM(CASE WHEN att.status='Absent' THEN 1 ELSE 0 END) as absent,
               SUM(CASE WHEN att.status='Leave' THEN 1 ELSE 0 END) as leave_count,
               SUM(CASE WHEN att.status='Late' THEN 1 ELSE 0 END) as late
        FROM students s 
        LEFT JOIN attendance att ON s.id = att.student_id AND att.academic_year_id = ?
        WHERE s.school_id = ? AND s.standard_id = ? AND s.section_id = ? AND s.status = 'active'
        GROUP BY s.id 
        ORDER BY s.roll_number ASC
    ");
    $stmtRep->execute([$activeYear['id'], $school_id, $std_id, $sec_id]);
    $report = $stmtRep->fetchAll();
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">Attendance Statistics Report</h2>
            <p class="text-secondary">Extract student-wise and class-wise attendance ratios.</p>
        </div>
    </div>
    
    <!-- Filter Card -->
    <div class="card border-0 shadow-sm p-4 mb-4 glass-card">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label font-semibold">Standard <span class="text-danger">*</span></label>
                <select name="standard_id" class="form-select" required id="stdSelect">
                    <option value="">Select Standard</option>
                    <?php foreach ($standards as $std): ?>
                        <option value="<?= $std['id'] ?>" <?= $std_id === $std['id'] ? 'selected' : '' ?>><?= htmlspecialchars($std['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <button type="submit" class="btn btn-indigo w-100 rounded-pill"><i class="fa-solid fa-chart-column me-2"></i>Generate Report</button>
            </div>
        </form>
    </div>
    
    <?php if ($std_id > 0): ?>
        <div class="card border-0 shadow-sm p-4 glass-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">Class Attendance Summary</h5>
                <button onclick="window.print()" class="btn btn-light border btn-sm"><i class="fa-solid fa-print me-1"></i>Print Report</button>
            </div>
            <div class="table-responsive">
                <table class="table align-middle custom-table">
                    <thead>
                        <tr>
                            <th style="width: 100px;">Roll No</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Total Days</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Ratio (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($report)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No student records found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($report as $row): ?>
                                <?php 
                                $total = intval($row['total']);
                                $present = intval($row['present']);
                                $percent = $total > 0 ? round(($present / $total) * 100) : 100;
                                ?>
                                <tr>
                                    <td><code>#<?= htmlspecialchars($row['roll_number']) ?></code></td>
                                    <td><code><?= htmlspecialchars($row['student_id']) ?></code></td>
                                    <td class="fw-bold"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                    <td><?= $total ?> days</td>
                                    <td class="text-success"><?= intval($row['present']) ?></td>
                                    <td class="text-danger"><?= intval($row['absent']) ?></td>
                                    <td>
                                        <span class="badge <?= $percent >= 75 ? 'bg-success' : 'bg-danger' ?> font-semibold">
                                            <?= $percent ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- JavaScript sections filter logic removed -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
