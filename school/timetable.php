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

// Handle Actions (Add, Edit, Delete, Clear All)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    
    $action = sanitizeInput($_POST['action'] ?? 'add');
    
    if ($action === 'add') {
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
                $message = getAlert('success', "Timetable period added successfully.");
            } catch (PDOException $e) {
                $message = getAlert('danger', "Failed to add timetable period: " . $e->getMessage());
            }
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $day = sanitizeInput($_POST['day'] ?? '');
        $period = intval($_POST['period_number'] ?? 0);
        $start_time = sanitizeInput($_POST['start_time'] ?? '');
        $end_time = sanitizeInput($_POST['end_time'] ?? '');
        $subject_id = intval($_POST['subject_id'] ?? 0);
        $teacher = sanitizeInput($_POST['teacher_name'] ?? '');
        $room = sanitizeInput($_POST['room_number'] ?? '');
        
        if ($id <= 0 || empty($day) || $period <= 0 || empty($start_time) || empty($end_time) || $subject_id <= 0) {
            $message = getAlert('danger', "All fields are required to update the period.");
        } else {
            try {
                // Delete any overlapping entry at the destination, excluding the current ID
                $stmtDel = $db->prepare("DELETE FROM timetables WHERE school_id = ? AND academic_year_id = ? AND standard_id = ? AND section_id = ? AND day = ? AND period_number = ? AND id != ?");
                $stmtDel->execute([$school_id, $active_year_id, $std_id, $sec_id, $day, $period, $id]);
                
                // Update
                $stmtUpd = $db->prepare("UPDATE timetables SET day = ?, period_number = ?, start_time = ?, end_time = ?, subject_id = ?, teacher_name = ?, room_number = ? WHERE id = ? AND school_id = ?");
                $stmtUpd->execute([$day, $period, $start_time, $end_time, $subject_id, $teacher, $room, $id, $school_id]);
                
                logActivity("Edit Timetable", "Updated period cell ID $id: Day $day, Period $period for Standard: $std_id");
                $message = getAlert('success', "Timetable period updated successfully.");
            } catch (PDOException $e) {
                $message = getAlert('danger', "Failed to update timetable period: " . $e->getMessage());
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $message = getAlert('danger', "Invalid period ID.");
        } else {
            try {
                $stmtDel = $db->prepare("DELETE FROM timetables WHERE id = ? AND school_id = ?");
                $stmtDel->execute([$id, $school_id]);
                
                logActivity("Delete Timetable", "Deleted period cell ID $id");
                $message = getAlert('success', "Timetable period deleted successfully.");
            } catch (PDOException $e) {
                $message = getAlert('danger', "Failed to delete timetable period: " . $e->getMessage());
            }
        }
    } elseif ($action === 'clear_all') {
        try {
            $stmtDel = $db->prepare("DELETE FROM timetables WHERE school_id = ? AND academic_year_id = ? AND standard_id = ? AND section_id = ?");
            $stmtDel->execute([$school_id, $active_year_id, $std_id, $sec_id]);
            
            logActivity("Clear Timetable", "Cleared entire timetable for Standard: $std_id");
            $message = getAlert('success', "Timetable cleared successfully.");
        } catch (PDOException $e) {
            $message = getAlert('danger', "Failed to clear timetable: " . $e->getMessage());
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
            <div class="col-md-6 d-flex gap-2">
                <?php if ($std_id > 0): ?>
                    <button type="button" class="btn btn-indigo flex-grow-1 rounded-pill animate-fade-in" data-bs-toggle="modal" data-bs-target="#addPeriodModal"><i class="fa-solid fa-calendar-plus me-2"></i>Add / Schedule Period</button>
                    <button type="button" class="btn btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#clearTimetableModal" title="Clear Entire Timetable"><i class="fa-solid fa-trash-can"></i> Clear All</button>
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
                                    <td class="<?= isset($timetableGrid[$day][$p]) ? '' : 'empty-slot-cell' ?> align-middle text-center" 
                                        data-day="<?= htmlspecialchars($day) ?>" 
                                        data-period="<?= $p ?>" 
                                        style="<?= isset($timetableGrid[$day][$p]) ? '' : 'cursor: pointer; height: 100px;' ?>">
                                        <?php if (isset($timetableGrid[$day][$p])): ?>
                                            <?php $cell = $timetableGrid[$day][$p]; ?>
                                            <div class="p-2 border rounded bg-indigo bg-opacity-10 text-indigo position-relative timetable-slot-card text-start" style="font-size: 13px;">
                                                <div class="fw-bold text-truncate" title="<?= htmlspecialchars($cell['subject_name']) ?>"><?= htmlspecialchars($cell['subject_name']) ?></div>
                                                <small class="d-block text-muted"><?= date("g:i A", strtotime($cell['start_time'])) ?> - <?= date("g:i A", strtotime($cell['end_time'])) ?></small>
                                                <small class="d-block text-secondary text-truncate" title="Teacher: <?= htmlspecialchars($cell['teacher_name'] ?: 'N/A') ?>">T: <?= htmlspecialchars($cell['teacher_name'] ?: 'N/A') ?></small>
                                                <small class="d-block text-secondary text-truncate" title="Room: <?= htmlspecialchars($cell['room_number'] ?: 'N/A') ?>">R: <?= htmlspecialchars($cell['room_number'] ?: 'N/A') ?></small>
                                                
                                                <div class="slot-actions mt-2 d-flex justify-content-end gap-1">
                                                    <button type="button" class="btn btn-xs btn-outline-indigo py-0 px-2 btn-edit-slot" 
                                                            data-id="<?= $cell['id'] ?>"
                                                            data-day="<?= htmlspecialchars($day) ?>"
                                                            data-period="<?= $p ?>"
                                                            data-subject="<?= $cell['subject_id'] ?>"
                                                            data-start="<?= htmlspecialchars($cell['start_time']) ?>"
                                                            data-end="<?= htmlspecialchars($cell['end_time']) ?>"
                                                            data-teacher="<?= htmlspecialchars($cell['teacher_name'] ?? '') ?>"
                                                            data-room="<?= htmlspecialchars($cell['room_number'] ?? '') ?>"
                                                            title="Edit Slot">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2 btn-delete-slot" 
                                                            data-id="<?= $cell['id'] ?>"
                                                            data-subject="<?= htmlspecialchars($cell['subject_name']) ?>"
                                                            data-day="<?= htmlspecialchars($day) ?>"
                                                            data-period="<?= $p ?>"
                                                            title="Delete Slot">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="empty-slot-placeholder text-muted" style="font-size: 12px;">—</span>
                                            <span class="empty-slot-hover-icon d-none text-indigo"><i class="fa-solid fa-circle-plus fa-lg"></i></span>
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
            <input type="hidden" name="action" value="add">
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

<!-- Edit Period Modal -->
<div class="modal fade" id="editPeriodModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <?= getCSRFInput() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_slot_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Class Period</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-semibold">Select Day <span class="text-danger">*</span></label>
                        <select name="day" id="edit_day" class="form-select" required>
                            <?php foreach ($days as $d): ?>
                                <option value="<?= $d ?>"><?= $d ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Period Number <span class="text-danger">*</span></label>
                            <select name="period_number" id="edit_period_number" class="form-select" required>
                                <?php foreach ($periods as $p): ?>
                                    <option value="<?= $p ?>">Period <?= $p ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Subject <span class="text-danger">*</span></label>
                            <select name="subject_id" id="edit_subject_id" class="form-select" required>
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
                            <input type="time" name="start_time" id="edit_start_time" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" id="edit_end_time" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Teacher Name</label>
                            <input type="text" name="teacher_name" id="edit_teacher_name" class="form-control" placeholder="e.g. Mr. Robert">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Room Number</label>
                            <input type="text" name="room_number" id="edit_room_number" class="form-control" placeholder="e.g. Lab 2, R201">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-indigo">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Period Confirmation Modal -->
<div class="modal fade" id="deletePeriodModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <?= getCSRFInput() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete_slot_id">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Delete Period</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Are you sure you want to delete the scheduled period for <strong id="delete_subject_name" class="text-dark"></strong>?</p>
                    <p class="text-secondary small mb-0">Class Period details: <strong id="delete_slot_details"></strong></p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Period</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Clear Entire Timetable Confirmation Modal -->
<div class="modal fade" id="clearTimetableModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <?= getCSRFInput() ?>
            <input type="hidden" name="action" value="clear_all">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-circle-exclamation me-2"></i>Clear Entire Timetable</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Are you sure you want to delete <strong>ALL</strong> scheduled periods for the selected standard?</p>
                    <p class="text-danger small mb-0"><i class="fa-solid fa-triangle-exclamation me-1"></i> <strong>Warning:</strong> This action is permanent and cannot be undone.</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Clear Timetable</button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.empty-slot-cell {
    transition: all 0.2s ease;
}
.empty-slot-cell:hover {
    background-color: rgba(99, 102, 241, 0.05) !important;
}
.empty-slot-cell:hover .empty-slot-placeholder {
    display: none;
}
.empty-slot-cell:hover .empty-slot-hover-icon {
    display: inline-block !important;
}
.timetable-slot-card {
    transition: all 0.2s ease;
}
.timetable-slot-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(99, 102, 241, 0.15);
}
.btn-xs {
    padding: 1px 6px;
    font-size: 10px;
    border-radius: 4px;
}
.btn-outline-indigo {
    color: #6366f1;
    border-color: #6366f1;
}
.btn-outline-indigo:hover {
    color: white;
    background-color: #6366f1;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit button click handler
    const editButtons = document.querySelectorAll('.btn-edit-slot');
    editButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation(); // prevent td trigger
            const id = this.getAttribute('data-id');
            const day = this.getAttribute('data-day');
            const period = this.getAttribute('data-period');
            const subject = this.getAttribute('data-subject');
            const start = this.getAttribute('data-start');
            const end = this.getAttribute('data-end');
            const teacher = this.getAttribute('data-teacher');
            const room = this.getAttribute('data-room');
            
            document.getElementById('edit_slot_id').value = id;
            document.getElementById('edit_day').value = day;
            document.getElementById('edit_period_number').value = period;
            document.getElementById('edit_subject_id').value = subject;
            document.getElementById('edit_start_time').value = start.substring(0, 5);
            document.getElementById('edit_end_time').value = end.substring(0, 5);
            document.getElementById('edit_teacher_name').value = teacher;
            document.getElementById('edit_room_number').value = room;
            
            const editModal = new bootstrap.Modal(document.getElementById('editPeriodModal'));
            editModal.show();
        });
    });

    // Delete button click handler
    const deleteButtons = document.querySelectorAll('.btn-delete-slot');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation(); // prevent td trigger
            const id = this.getAttribute('data-id');
            const subject = this.getAttribute('data-subject');
            const day = this.getAttribute('data-day');
            const period = this.getAttribute('data-period');
            
            document.getElementById('delete_slot_id').value = id;
            document.getElementById('delete_subject_name').textContent = subject;
            document.getElementById('delete_slot_details').textContent = `${day}, Period ${period}`;
            
            const deleteModal = new bootstrap.Modal(document.getElementById('deletePeriodModal'));
            deleteModal.show();
        });
    });

    // Empty cell click handler to add period quickly
    const emptyCells = document.querySelectorAll('.empty-slot-cell');
    emptyCells.forEach(cell => {
        cell.addEventListener('click', function() {
            const day = this.getAttribute('data-day');
            const period = this.getAttribute('data-period');
            
            const addDaySelect = document.querySelector('#addPeriodModal select[name="day"]');
            const addPeriodSelect = document.querySelector('#addPeriodModal select[name="period_number"]');
            
            if(addDaySelect) addDaySelect.value = day;
            if(addPeriodSelect) addPeriodSelect.value = period;
            
            const addModal = new bootstrap.Modal(document.getElementById('addPeriodModal'));
            addModal.show();
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
