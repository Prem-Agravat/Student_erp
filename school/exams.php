<?php
// C:\xampp\htdocs\school-erp\school\exams.php

$activePage = 'exams';
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
    echo '<p class="mb-3">Please configure and activate an Academic Year before setting up Exams.</p>';
    echo '<a href="academic_years.php" class="btn btn-indigo rounded-pill px-4">Manage Academic Years</a>';
    echo '</div>';
    echo '</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$active_year_id = $activeYear['id'];

// Fetch Standards for exam creation
$stmtStds = $db->prepare("SELECT id, name FROM standards WHERE school_id = ? AND status = 'active' ORDER BY display_order ASC");
$stmtStds->execute([$school_id]);
$standards = $stmtStds->fetchAll();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF validation failed.");
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_exam') {
        $exam_name = sanitizeInput($_POST['exam_name'] ?? '');
        $standard_id = intval($_POST['standard_id'] ?? 0);
        $start_date = sanitizeInput($_POST['start_date'] ?? '');
        $end_date = sanitizeInput($_POST['end_date'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        
        if (empty($exam_name) || $standard_id <= 0 || empty($start_date) || empty($end_date)) {
            $message = getAlert('danger', "All fields marked * are required.");
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO exams (school_id, academic_year_id, standard_id, exam_name, start_date, end_date, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'draft')");
                $stmt->execute([$school_id, $active_year_id, $standard_id, $exam_name, $start_date, $end_date, $description]);
                logActivity("Create Exam", "Created exam: $exam_name for standard ID: $standard_id");
                $message = getAlert('success', "Exam '$exam_name' created successfully as draft.");
            } catch (PDOException $e) {
                $message = getAlert('danger', "Failed to create exam: " . $e->getMessage());
            }
        }
    } elseif ($action === 'configure_subject') {
        $exam_id = intval($_POST['exam_id'] ?? 0);
        $subject_id = intval($_POST['subject_id'] ?? 0);
        $max_marks = floatval($_POST['max_marks'] ?? 100);
        $passing_marks = floatval($_POST['passing_marks'] ?? 40);
        $exam_date = sanitizeInput($_POST['exam_date'] ?? null);
        
        if ($exam_id <= 0 || $subject_id <= 0) {
            $message = getAlert('danger', "Exam and Subject are required.");
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO exam_subjects (school_id, exam_id, subject_id, max_marks, passing_marks, exam_date) 
                    VALUES (?, ?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE max_marks = VALUES(max_marks), passing_marks = VALUES(passing_marks), exam_date = VALUES(exam_date)
                ");
                $stmt->execute([$school_id, $exam_id, $subject_id, $max_marks, $passing_marks, $exam_date ?: null]);
                logActivity("Configure Exam Subject", "Mapped subject to exam ID: $exam_id");
                $message = getAlert('success', "Exam subject configured successfully.");
            } catch (PDOException $e) {
                $message = getAlert('danger', "Failed to configure subject: " . $e->getMessage());
            }
        }
    }
}

// Fetch all exams with standard names
$stmtEx = $db->prepare("SELECT e.*, std.name as standard_name FROM exams e JOIN standards std ON e.standard_id = std.id WHERE e.school_id = ? AND e.academic_year_id = ? ORDER BY e.start_date DESC");
$stmtEx->execute([$school_id, $active_year_id]);
$examsList = $stmtEx->fetchAll();

// Helper to fetch subjects for standard for configuring exam subjects
$subjectsByStd = [];
foreach ($standards as $std) {
    $stmtSub = $db->prepare("SELECT sub.id, sub.name FROM standard_subjects ss JOIN subjects sub ON ss.subject_id = sub.id WHERE ss.school_id = ? AND ss.standard_id = ? AND sub.status = 'active'");
    $stmtSub->execute([$school_id, $std['id']]);
    $subjectsByStd[$std['id']] = $stmtSub->fetchAll();
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Examination Scheduler</h2>
            <p class="text-secondary mb-0">Academic Year: <span class="badge bg-indigo"><?= htmlspecialchars($activeYear['name']) ?></span></p>
        </div>
        <button class="btn btn-indigo rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createExamModal"><i class="fa-solid fa-plus me-2"></i>Schedule Exam</button>
    </div>
    
    <?= $message ?>
    
    <div class="row g-4">
        <!-- Exams List -->
        <div class="col-12">
            <div class="card border-0 shadow-sm p-4 glass-card">
                <div class="table-responsive">
                    <table class="table align-middle custom-table">
                        <thead>
                            <tr>
                                <th>Exam Name</th>
                                <th>Standard</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Configure Subjects</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($examsList)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No exams scheduled yet for this academic year.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($examsList as $ex): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($ex['exam_name']) ?></td>
                                        <td class="text-indigo fw-bold"><?= htmlspecialchars($ex['standard_name']) ?></td>
                                        <td><?= htmlspecialchars($ex['start_date']) ?></td>
                                        <td><?= htmlspecialchars($ex['end_date']) ?></td>
                                        <td>
                                            <span class="badge <?= $ex['status'] === 'published' ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= ucfirst($ex['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-outline-indigo btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#configModal<?= $ex['id'] ?>"><i class="fa-solid fa-gear me-1"></i>Configure Subjects</button>
                                            
                                            <!-- Configure Subjects Modal -->
                                            <div class="modal fade" id="configModal<?= $ex['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <form method="POST">
                                                        <?= getCSRFInput() ?>
                                                        <input type="hidden" name="action" value="configure_subject">
                                                        <input type="hidden" name="exam_id" value="<?= $ex['id'] ?>">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title fw-bold">Map Subjects to <?= htmlspecialchars($ex['exam_name']) ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body text-start">
                                                                <div class="mb-3">
                                                                    <label class="form-label font-semibold">Select Subject <span class="text-danger">*</span></label>
                                                                    <select name="subject_id" class="form-select" required>
                                                                        <option value="">Choose Subject</option>
                                                                        <?php 
                                                                        $subs = $subjectsByStd[$ex['standard_id']] ?? [];
                                                                        foreach ($subs as $sub): 
                                                                        ?>
                                                                            <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['name']) ?></option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="row mb-3">
                                                                    <div class="col-md-6">
                                                                        <label class="form-label font-semibold">Max Marks <span class="text-danger">*</span></label>
                                                                        <input type="number" name="max_marks" class="form-control" value="100" min="1" step="0.5" required>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label font-semibold">Passing Marks <span class="text-danger">*</span></label>
                                                                        <input type="number" name="passing_marks" class="form-control" value="40" min="1" step="0.5" required>
                                                                    </div>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label font-semibold">Exam Date</label>
                                                                    <input type="date" name="exam_date" class="form-control">
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-indigo">Save Config</button>
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
    </div>
</div>

<!-- Create Exam Modal -->
<div class="modal fade" id="createExamModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <?= getCSRFInput() ?>
            <input type="hidden" name="action" value="create_exam">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Schedule Examination</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-semibold">Exam Name <span class="text-danger">*</span></label>
                        <input type="text" name="exam_name" class="form-control" required placeholder="e.g. Unit Test 1, Semester 1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Standard / Class <span class="text-danger">*</span></label>
                        <select name="standard_id" class="form-select" required>
                            <option value="">Select Standard</option>
                            <?php foreach ($standards as $std): ?>
                                <option value="<?= $std['id'] ?>"><?= htmlspecialchars($std['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="e.g. Syllabus coverage instructions..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-indigo">Create Exam Schedule</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
