<?php
// C:\xampp\htdocs\school-erp\school\results.php

$activePage = 'results';
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
    echo '<p class="mb-3">Please configure and activate an Academic Year before publishing Results.</p>';
    echo '<a href="academic_years.php" class="btn btn-indigo rounded-pill px-4">Manage Academic Years</a>';
    echo '</div>';
    echo '</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Handle Publish/Unpublish toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    
    $exam_id = intval($_POST['exam_id'] ?? 0);
    $action = $_POST['action'];
    
    if ($exam_id > 0) {
        // Verify ownership
        $stmtEx = $db->prepare("SELECT exam_name, status FROM exams WHERE id = ? AND school_id = ?");
        $stmtEx->execute([$exam_id, $school_id]);
        $exam = $stmtEx->fetch();
        
        if ($exam) {
            $exam_name = $exam['exam_name'];
            $newStatus = ($action === 'publish') ? 'published' : 'draft';
            
            $stmtUpdate = $db->prepare("UPDATE exams SET status = ? WHERE id = ?");
            $stmtUpdate->execute([$newStatus, $exam_id]);
            
            logActivity("Toggle Result Status", "Set exam status to $newStatus for: $exam_name");
            $message = getAlert('success', "Exam result status updated to '$newStatus' for '$exam_name'.");
        }
    }
}

// Fetch exams with student mapping count
$stmtExams = $db->prepare("
    SELECT e.*, std.name as standard_name, 
           (SELECT COUNT(DISTINCT student_id) FROM marks WHERE exam_id = e.id) as students_marked 
    FROM exams e 
    JOIN standards std ON e.standard_id = std.id 
    WHERE e.school_id = ? AND e.academic_year_id = ? 
    ORDER BY e.start_date DESC
");
$stmtExams->execute([$school_id, $activeYear['id']]);
$examsList = $stmtExams->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">Publish & Manage Exam Results</h2>
            <p class="text-secondary">Toggle examination status to let students download report cards.</p>
        </div>
    </div>
    
    <?= $message ?>
    
    <div class="card border-0 shadow-sm p-4 glass-card">
        <div class="table-responsive">
            <table class="table align-middle custom-table">
                <thead>
                    <tr>
                        <th>Exam Name</th>
                        <th>Standard</th>
                        <th>Students Evaluated</th>
                        <th>Schedule Dates</th>
                        <th>Result Status</th>
                        <th>Actions</th>
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
                                <td><code><?= htmlspecialchars($ex['students_marked']) ?> students</code></td>
                                <td><?= htmlspecialchars($ex['start_date']) ?> to <?= htmlspecialchars($ex['end_date']) ?></td>
                                <td>
                                    <span class="badge <?= $ex['status'] === 'published' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= ucfirst($ex['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <?php if ($ex['status'] === 'draft'): ?>
                                            <form method="POST" class="d-inline">
                                                <?= getCSRFInput() ?>
                                                <input type="hidden" name="exam_id" value="<?= $ex['id'] ?>">
                                                <input type="hidden" name="action" value="publish">
                                                <button type="submit" class="btn btn-success btn-sm rounded-pill px-3" onclick="return confirm('Publish results? Students will see their marks.')"><i class="fa-solid fa-bullhorn me-1"></i>Publish</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" class="d-inline">
                                                <?= getCSRFInput() ?>
                                                <input type="hidden" name="exam_id" value="<?= $ex['id'] ?>">
                                                <input type="hidden" name="action" value="unpublish">
                                                <button type="submit" class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="return confirm('Unpublish results? This hides marks from students.')"><i class="fa-solid fa-lock me-1"></i>Unpublish</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <a href="report_cards.php?exam_id=<?= $ex['id'] ?>" class="btn btn-light btn-sm rounded-pill px-3"><i class="fa-solid fa-print me-1"></i>Report Cards</a>
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
