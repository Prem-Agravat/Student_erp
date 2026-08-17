<?php
// C:\xampp\htdocs\school-erp\school\timetable.php

$activePage = 'timetable';
require_once __DIR__ . '/../includes/school_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$school_id = $_SESSION['school_id'];
$message = '';

// Check active academic year
$stmtYear = $db->prepare("SELECT id, name FROM academic_years WHERE school_id = ? AND status = 'active'");
$stmtYear->execute([$school_id]);
$activeYear = $stmtYear->fetch();

if (!$activeYear) {
    echo '<div class="container-fluid">';
    echo '<div class="alert alert-warning py-4 shadow-sm border-0 glass-card">';
    echo '<h5 class="fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Active Academic Year Required</h5>';
    echo '<p class="mb-3">Please configure and activate an Academic Year before setting up Timetables.</p>';
    echo '<a href="academic_years.php" class="btn btn-indigo rounded-pill px-4">Manage Academic Years</a>';
    echo '</div>';
    echo '</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$active_year_id = $activeYear['id'];

// Fetch Standards & Sections for filters
$standards = $db->query("SELECT id, name FROM standards WHERE school_id = $school_id AND status = 'active' ORDER BY display_order ASC")->fetchAll();
$sections = $db->query("SELECT id, name, standard_id FROM sections WHERE school_id = $school_id AND status = 'active' ORDER BY name ASC")->fetchAll();

$std_id = intval($_GET['standard_id'] ?? 0);
$sec_id = 0;
if ($std_id > 0) {
    $sec_id = getOrInsertDefaultSectionId($db, $school_id, $std_id);
}

// Fetch subjects for select list
$subjects = [];
if ($std_id > 0) {
    $stmtSubs = $db->prepare("SELECT sub.id, sub.name FROM standard_subjects ss JOIN subjects sub ON ss.subject_id = sub.id WHERE ss.standard_id = ? AND ss.school_id = ?");
    $stmtSubs->execute([$std_id, $school_id]);
    $subjects = $stmtSubs->fetchAll();
}

// Handle Add/Edit Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    
    $day = sanitizeInput($_POST['day'] ?? '');
    $period = intval($_POST['period_number'] ?? 0);
    $start_time = sanitizeInput($_POST['start_time'] ?? '');
    $end_time = sanitizeInput($_POST['end_time'] ?? '');
    $subject_id = intval($_POST['subject_id'] ?? 0);
    $teacher = sanitizeInput($_POST['teacher_name'] ?? '');
    $room = sanitizeInput($_POST['room_number'] ?? '');
    
    if (empty($day) || $period <= 0 || empty($start_time) || empty($end_time) || $subject_id <= 0) {
        $message = getAlert('danger', "Day, Period, Times, and Subject are required.");
    } else {
        try {
            // Delete existing cell to prevent overlaps
            $stmtDel = $db->prepare("DELETE FROM timetables WHERE school_id = ? AND academic_year_id = ? AND standard_id = ? AND section_id = ? AND day = ? AND period_number = ?");
            $stmtDel->execute([$school_id, $active_year_id, $std_id, $sec_id, $day, $period]);
            
            // Insert
            $stmtIns = $db->prepare("INSERT INTO timetables (school_id, academic_year_id, standard_id, section_id, day, period_number, start_time, end_time, subject_id, teacher_name, room_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtIns->execute([$school_id, $active_year_id, $std_id, $sec_id, $day, $period, $start_time, $end_time, $subject_id, $teacher, $room]);
            
            logActivity("Save Timetable", "Saved period cell: Day $day, Period $period for Standard: $std_id, Section: $sec_id");
            $message = getAlert('success', "Timetable cell saved successfully.");
        } catch (PDOException $e) {
            $message = getAlert('danger', "Failed to save timetable period: " . $e->getMessage());
        }
    }
}

// Fetch current timetable grid data
$timetableGrid = [];
if ($std_id > 0) {
    $stmtGrid = $db->prepare("
        SELECT t.*, sub.name as subject_name 
        FROM timetables t 
        JOIN subjects sub ON t.subject_id = sub.id 
        WHERE t.school_id = ? AND t.academic_year_id = ? AND t.standard_id = ? AND t.section_id = ?
    ");
    $stmtGrid->execute([$school_id, $active_year_id, $std_id, $sec_id]);
    $gridRows = $stmtGrid->fetchAll();
    
    // Structure: timetableGrid[day][period_number] = row
    foreach ($gridRows as $row) {
        $timetableGrid[$row['day']][$row['period_number']] = $row;
    }
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$periods = [1, 2, 3, 4, 5, 6, 7, 8];
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">Class Timetable Scheduler</h2>
            <p class="text-secondary">Configure daily period allocations for class divisions.</p>
        </div>
    </div>
    
    <?= $message ?>
    
    <!-- Class Selector -->
    <div class="card border-0 shadow-sm p-4 mb-4 glass-card">
        <form method="GET" class="row g-3 align-items-end" id="classSelectForm">
            <div class="col-md-6">
                <label class="form-label font-semibold">Standard <span class="text-danger">*</span></label>
                <select name="standard_id" class="form-select" required id="stdSelect" onchange="document.getElementById('classSelectForm').submit();">
                    <option value="">Select Standard</option>
                    <?php foreach ($standards as $std): ?>
                        <option value="<?= $std['id'] ?>" <?= $std_id === $std['id'] ? 'selected' : '' ?>><?= htmlspecialchars($std['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <?php if ($std_id > 0): ?>
                    <button type="button" class="btn btn-indigo w-100 rounded-pill" data-bs-toggle="modal" data-bs-target="#addPeriodModal"><i class="fa-solid fa-calendar-plus me-2"></i>Add / Schedule Period</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <?php if ($std_id > 0 && $sec_id > 0): ?>
        <div class="card border-0 shadow-sm p-4 glass-card">
            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center custom-table" style="min-width: 800px;">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 120px;">Day</th>
                            <?php foreach ($periods as $p): ?>
                                <th>Period <?= $p ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($days as $day): ?>
                            <tr>
                                <td class="fw-bold bg-light"><?= $day ?></td>
                                <?php foreach ($periods as $p): ?>
                                    <td>
                                        <?php if (isset($timetableGrid[$day][$p])): ?>
                                            <?php $cell = $timetableGrid[$day][$p]; ?>
                                            <div class="p-2 border rounded bg-indigo bg-opacity-10 text-indigo" style="font-size: 13px;">
                                                <div class="fw-bold"><?= htmlspecialchars($cell['subject_name']) ?></div>
                                                <small class="d-block text-muted"><?= htmlspecialchars($cell['start_time']) ?>-<?= htmlspecialchars($cell['end_time']) ?></small>
                                                <small class="d-block text-secondary">T: <?= htmlspecialchars($cell['teacher_name'] ?: 'N/A') ?></small>
                                                <small class="d-block text-secondary">R: <?= htmlspecialchars($cell['room_number'] ?: 'N/A') ?></small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 12px;">—</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Period Modal -->
<div class="modal fade" id="addPeriodModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <?= getCSRFInput() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Schedule Class Period</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-semibold">Select Day <span class="text-danger">*</span></label>
                        <select name="day" class="form-select" required>
                            <?php foreach ($days as $d): ?>
                                <option value="<?= $d ?>"><?= $d ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Period Number <span class="text-danger">*</span></label>
                            <select name="period_number" class="form-select" required>
                                <?php foreach ($periods as $p): ?>
                                    <option value="<?= $p ?>">Period <?= $p ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Subject <span class="text-danger">*</span></label>
                            <select name="subject_id" class="form-select" required>
                                <option value="">Choose Subject</option>
                                <?php foreach ($subjects as $sub): ?>
                                    <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Teacher Name</label>
                            <input type="text" name="teacher_name" class="form-control" placeholder="e.g. Mr. Robert">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Room Number</label>
                            <input type="text" name="room_number" class="form-control" placeholder="e.g. Lab 2, R201">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-indigo">Schedule Period</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript sections filter logic removed -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
