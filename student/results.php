<?php
// C:\xampp\htdocs\school-erp\student\results.php

$activePage = 'results';
require_once __DIR__ . '/../includes/student_auth.php';
require_once __DIR__ . '/../includes/header.php';

$student_id = $_SESSION['student_id'];
$school_id = $_SESSION['school_id'];
$db = getDBConnection();

// Fetch published exams that the student took
$stmtEx = $db->prepare("
    SELECT e.id, e.exam_name, e.end_date 
    FROM marks m 
    JOIN exams e ON m.exam_id = e.id 
    WHERE m.student_id = ? AND m.school_id = ? AND e.status = 'published' 
    GROUP BY e.id 
    ORDER BY e.end_date DESC
");
$stmtEx->execute([$student_id, $school_id]);
$publishedExams = $stmtEx->fetchAll();

$selected_exam_id = intval($_GET['exam_id'] ?? ($publishedExams[0]['id'] ?? 0));

$marks = [];
$exam_details = null;
$totalObtained = 0;
$totalMax = 0;
$isFail = false;

if ($selected_exam_id > 0) {
    // Verify exam is published
    $stmtVerify = $db->prepare("SELECT id, exam_name FROM exams WHERE id = ? AND school_id = ? AND status = 'published'");
    $stmtVerify->execute([$selected_exam_id, $school_id]);
    $exam_details = $stmtVerify->fetch();
    
    if ($exam_details) {
        // Fetch marks
        $stmtMarks = $db->prepare("
            SELECT m.*, sub.name as subject_name, es.passing_marks 
            FROM marks m 
            JOIN subjects sub ON m.subject_id = sub.id 
            JOIN exam_subjects es ON es.exam_id = m.exam_id AND es.subject_id = m.subject_id
            WHERE m.student_id = ? AND m.exam_id = ? AND m.school_id = ?
        ");
        $stmtMarks->execute([$student_id, $selected_exam_id, $school_id]);
        $marks = $stmtMarks->fetchAll();
        
        foreach ($marks as $m) {
            $totalObtained += floatval($m['marks_obtained']);
            $totalMax += floatval($m['max_marks']);
            if (floatval($m['marks_obtained']) < floatval($m['passing_marks'])) {
                $isFail = true;
            }
        }
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">My Academic Results</h2>
            <p class="text-secondary">View score sheets and print official report cards for published examinations.</p>
        </div>
    </div>
    
    <?php if (empty($publishedExams)): ?>
        <div class="card border-0 shadow-sm p-5 glass-card text-center text-muted">
            <i class="fa-solid fa-lock fa-3x mb-3 text-secondary"></i>
            <h5 class="fw-bold text-dark">No Results Published</h5>
            <p class="mb-0">Your exam results are currently draft or have not been published by your school administrator yet.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Sidebar list of exams -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-4 glass-card h-100">
                    <h5 class="fw-bold mb-4"><i class="fa-solid fa-file-invoice text-indigo me-2"></i>Examinations</h5>
                    <div class="list-group list-group-flush">
                        <?php foreach ($publishedExams as $ex): ?>
                            <a href="?exam_id=<?= $ex['id'] ?>" class="list-group-item list-group-item-action border-0 rounded-3 mb-2 py-3 px-4 <?= $selected_exam_id === $ex['id'] ? 'active bg-indigo text-white' : '' ?>">
                                <div class="fw-bold"><?= htmlspecialchars($ex['exam_name']) ?></div>
                                <small class="<?= $selected_exam_id === $ex['id'] ? 'text-white-50' : 'text-muted' ?>">End Date: <?= htmlspecialchars($ex['end_date']) ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Result details -->
            <div class="col-lg-8">
                <?php if ($exam_details): ?>
                    <!-- Summary metrics card -->
                    <div class="card border-0 shadow-sm p-4 glass-card mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="fw-bold text-dark mb-0"><?= htmlspecialchars($exam_details['exam_name']) ?> Score Sheet</h4>
                            <span class="badge bg-success px-3 py-2 font-semibold"><i class="fa-solid fa-circle-check me-1"></i>Official</span>
                        </div>
                        
                        <div class="row g-3 text-center border-top pt-4">
                            <div class="col-md-3 col-6 border-end">
                                <small class="text-muted d-block font-semibold">Total Obtained</small>
                                <h3 class="fw-bold mt-1 text-dark mb-0"><?= $totalObtained ?> / <?= $totalMax ?></h3>
                            </div>
                            <div class="col-md-3 col-6 border-end">
                                <small class="text-muted d-block font-semibold">Percentage</small>
                                <h3 class="fw-bold mt-1 text-dark mb-0"><?= $totalMax > 0 ? round(($totalObtained / $totalMax)*100, 2) : 0 ?>%</h3>
                            </div>
                            <div class="col-md-3 col-6 border-end">
                                <small class="text-muted d-block font-semibold">Final Grade</small>
                                <h3 class="fw-bold mt-1 text-indigo mb-0"><?= calculateGrade($totalObtained, $totalMax) ?></h3>
                            </div>
                            <div class="col-md-3 col-6">
                                <small class="text-muted d-block font-semibold">Result Status</small>
                                <h3 class="fw-bold mt-1 mb-0 <?= $isFail ? 'text-danger' : 'text-success' ?>"><?= $isFail ? 'Fail' : 'Pass' ?></h3>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Subject details -->
                    <div class="card border-0 shadow-sm p-4 glass-card">
                        <div class="table-responsive">
                            <table class="table align-middle custom-table">
                                <thead>
                                    <tr>
                                        <th>Subject Name</th>
                                        <th>Max Marks</th>
                                        <th>Passing Marks</th>
                                        <th>Marks Obtained</th>
                                        <th>Grade</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($marks as $m): ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($m['subject_name']) ?></td>
                                            <td><?= htmlspecialchars($m['max_marks']) ?></td>
                                            <td><?= htmlspecialchars($m['passing_marks']) ?></td>
                                            <td class="fw-bold <?= floatval($m['marks_obtained']) < floatval($m['passing_marks']) ? 'text-danger' : 'text-success' ?>"><?= htmlspecialchars($m['marks_obtained']) ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($m['grade']) ?></span></td>
                                            <td>
                                                <?php if (floatval($m['marks_obtained']) < floatval($m['passing_marks'])): ?>
                                                    <span class="status-badge status-rejected">Fail</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-approved">Pass</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
