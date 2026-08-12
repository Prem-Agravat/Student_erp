<?php
// C:\xampp\htdocs\school-erp\school\attendance.php

$activePage = 'attendance';
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
    echo '<p class="mb-3">Please configure and activate an Academic Year before marking attendance.</p>';
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
$sec_id = intval($_GET['section_id'] ?? 0);
$date = sanitizeInput($_GET['date'] ?? date('Y-m-d'));

$students = [];
if ($std_id > 0 && $sec_id > 0) {
    // Fetch students in selected section
    $stmtStu = $db->prepare("
        SELECT s.id, s.first_name, s.last_name, s.roll_number, att.status as att_status, att.remarks as att_remarks 
        FROM students s 
        LEFT JOIN attendance att ON s.id = att.student_id AND att.date = ? 
        WHERE s.school_id = ? AND s.standard_id = ? AND s.section_id = ? AND s.status = 'active' 
        ORDER BY s.roll_number ASC
    ");
    $stmtStu->execute([$date, $school_id, $std_id, $sec_id]);
    $students = $stmtStu->fetchAll();
}

// Handle attendance save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_attendance') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    
    $attendance_data = $_POST['attendance'] ?? []; // key: student_id, val: status
    $remarks_data = $_POST['remarks'] ?? []; // key: student_id, val: remark
    $admin_id = $_SESSION['school_admin_id'];
    
    if (empty($attendance_data)) {
        $message = getAlert('danger', "No attendance records submitted.");
    } else {
        try {
            $db->beginTransaction();
            
            $stmtUpsert = $db->prepare("
                INSERT INTO attendance (school_id, student_id, academic_year_id, date, status, remarks, marked_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks), marked_by = VALUES(marked_by)
            ");
            
            foreach ($attendance_data as $stu_id => $status) {
                $remark = sanitizeInput($remarks_data[$stu_id] ?? '');
                $stmtUpsert->execute([$school_id, intval($stu_id), $activeYear['id'], $date, $status, $remark, $admin_id]);
            }
            
            $db->commit();
            logActivity("Mark Attendance", "Marked attendance for Standard ID: $std_id, Section ID: $sec_id on date $date");
            $message = getAlert('success', "Attendance saved successfully.");
            
            // Reload list
            $stmtStu = $db->prepare("
                SELECT s.id, s.first_name, s.last_name, s.roll_number, att.status as att_status, att.remarks as att_remarks 
                FROM students s 
                LEFT JOIN attendance att ON s.id = att.student_id AND att.date = ? 
                WHERE s.school_id = ? AND s.standard_id = ? AND s.section_id = ? AND s.status = 'active' 
                ORDER BY s.roll_number ASC
            ");
            $stmtStu->execute([$date, $school_id, $std_id, $sec_id]);
            $students = $stmtStu->fetchAll();
            
        } catch (PDOException $e) {
            $db->rollBack();
            $message = getAlert('danger', "Failed to save attendance: " . $e->getMessage());
        }
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">Daily Attendance Tracker</h2>
            <p class="text-secondary">Select standard class divisions and select present/absent student records.</p>
        </div>
    </div>
    
    <?= $message ?>
    
    <!-- Filter Card -->
    <div class="card border-0 shadow-sm p-4 mb-4 glass-card">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label font-semibold">Standard <span class="text-danger">*</span></label>
                <select name="standard_id" class="form-select" required id="stdSelect">
                    <option value="">Select Standard</option>
                    <?php foreach ($standards as $std): ?>
                        <option value="<?= $std['id'] ?>" <?= $std_id === $std['id'] ? 'selected' : '' ?>><?= htmlspecialchars($std['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label font-semibold">Section <span class="text-danger">*</span></label>
                <select name="section_id" class="form-select" required id="secSelect">
                    <option value="">Select Section</option>
                    <?php foreach ($sections as $sec): ?>
                        <option value="<?= $sec['id'] ?>" data-std="<?= $sec['standard_id'] ?>" <?= $sec_id === $sec['id'] ? 'selected' : '' ?>><?= htmlspecialchars($sec['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label font-semibold">Date <span class="text-danger">*</span></label>
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>" required max="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-indigo w-100 rounded-pill"><i class="fa-solid fa-users-viewfinder me-2"></i>Load Students</button>
            </div>
        </form>
    </div>
    
    <?php if ($std_id > 0 && $sec_id > 0): ?>
        <!-- Attendance Form -->
        <form method="POST" class="card border-0 shadow-sm p-4 glass-card">
            <?= getCSRFInput() ?>
            <input type="hidden" name="action" value="save_attendance">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">Attendance Sheet (<?= htmlspecialchars($date) ?>)</h5>
                <button type="button" class="btn btn-outline-indigo btn-sm rounded-pill" id="markAllPresent"><i class="fa-solid fa-clipboard-user me-2"></i>Mark All Present</button>
            </div>
            
            <div class="table-responsive">
                <table class="table align-middle custom-table">
                    <thead>
                        <tr>
                            <th style="width: 100px;">Roll No</th>
                            <th>Student Name</th>
                            <th>Attendance Status</th>
                            <th>Remarks (Optional)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No active students registered in this section yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $stu): ?>
                                <?php $status = $stu['att_status'] ?? 'Present'; ?>
                                <tr>
                                    <td><code>#<?= htmlspecialchars($stu['roll_number']) ?></code></td>
                                    <td class="fw-bold"><?= htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']) ?></td>
                                    <td>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input att-radio-present" type="radio" name="attendance[<?= $stu['id'] ?>]" value="Present" id="pres<?= $stu['id'] ?>" <?= $status === 'Present' ? 'checked' : '' ?>>
                                                <label class="form-check-label text-success font-semibold" for="pres<?= $stu['id'] ?>">Present</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="attendance[<?= $stu['id'] ?>]" value="Absent" id="abs<?= $stu['id'] ?>" <?= $status === 'Absent' ? 'checked' : '' ?>>
                                                <label class="form-check-label text-danger font-semibold" for="abs<?= $stu['id'] ?>">Absent</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="attendance[<?= $stu['id'] ?>]" value="Late" id="late<?= $stu['id'] ?>" <?= $status === 'Late' ? 'checked' : '' ?>>
                                                <label class="form-check-label text-warning font-semibold" for="late<?= $stu['id'] ?>">Late</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="attendance[<?= $stu['id'] ?>]" value="Leave" id="leave<?= $stu['id'] ?>" <?= $status === 'Leave' ? 'checked' : '' ?>>
                                                <label class="form-check-label text-info font-semibold" for="leave<?= $stu['id'] ?>">Leave</label>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="remarks[<?= $stu['id'] ?>]" class="form-control form-control-sm" placeholder="Reason if absent/leave" value="<?= htmlspecialchars($stu['att_remarks'] ?? '') ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (!empty($students)): ?>
                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-indigo rounded-pill px-5">Save Attendance Records</button>
                </div>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Filter sections selection dynamically
    const stdSelect = document.getElementById('stdSelect');
    const secSelect = document.getElementById('secSelect');
    
    if (stdSelect && secSelect) {
        const originalOptions = Array.from(secSelect.options);
        
        const filterSections = () => {
            const selectedStdId = stdSelect.value;
            secSelect.innerHTML = '';
            originalOptions.forEach(option => {
                if (option.value === '' || option.getAttribute('data-std') === selectedStdId) {
                    secSelect.appendChild(option.cloneNode(true));
                }
            });
        };
        
        stdSelect.addEventListener('change', filterSections);
        // Run once on load if standard already selected
        if (stdSelect.value !== '') {
            const currentVal = secSelect.value;
            filterSections();
            secSelect.value = currentVal;
        }
    }
    
    // Mark All Present shortcut button
    const markAllBtn = document.getElementById('markAllPresent');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function() {
            const presentRadios = document.querySelectorAll('.att-radio-present');
            presentRadios.forEach(radio => {
                radio.checked = true;
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
