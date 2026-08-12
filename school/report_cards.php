<?php
// C:\xampp\htdocs\school-erp\school\report_cards.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Access validation
if (!isset($_SESSION['role']) || $_SESSION['role'] !== ROLE_SCHOOL_ADMIN) {
    die("Unauthorized access.");
}

$school_id = $_SESSION['school_id'];
$db = getDBConnection();

$exam_id = intval($_GET['exam_id'] ?? 0);
$student_id = intval($_GET['student_id'] ?? 0);

// Verify exam belongs to school
$stmtEx = $db->prepare("SELECT e.*, std.name as standard_name FROM exams e JOIN standards std ON e.standard_id = std.id WHERE e.id = ? AND e.school_id = ?");
$stmtEx->execute([$exam_id, $school_id]);
$exam = $stmtEx->fetch();

if (!$exam) {
    die("Exam record not found or unauthorized access.");
}

// -------------------------------------------------------------
// CASE 1: Render specific student report card (print-ready)
// -------------------------------------------------------------
if ($student_id > 0) {
    // Fetch school settings/profile
    $stmtSch = $db->prepare("SELECT * FROM schools WHERE id = ?");
    $stmtSch->execute([$school_id]);
    $school = $stmtSch->fetch();
    
    // Fetch Student profile
    $stmtStu = $db->prepare("SELECT s.*, sec.name as section_name FROM students s JOIN sections sec ON s.section_id = sec.id WHERE s.id = ? AND s.school_id = ?");
    $stmtStu->execute([$student_id, $school_id]);
    $student = $stmtStu->fetch();
    
    if (!$student) {
        die("Student record not found.");
    }
    
    // Fetch marks for this student for this exam
    $stmtMarks = $db->prepare("
        SELECT m.*, sub.name as subject_name, es.passing_marks 
        FROM marks m 
        JOIN subjects sub ON m.subject_id = sub.id 
        JOIN exam_subjects es ON es.exam_id = m.exam_id AND es.subject_id = m.subject_id
        WHERE m.student_id = ? AND m.exam_id = ? AND m.school_id = ?
    ");
    $stmtMarks->execute([$student_id, $exam_id, $school_id]);
    $marks = $stmtMarks->fetchAll();
    
    // Calculate totals
    $totalObtained = 0;
    $totalMax = 0;
    $isFail = false;
    
    foreach ($marks as $m) {
        $totalObtained += floatval($m['marks_obtained']);
        $totalMax += floatval($m['max_marks']);
        if (floatval($m['marks_obtained']) < floatval($m['passing_marks'])) {
            $isFail = true;
        }
    }
    
    $percentage = $totalMax > 0 ? round(($totalObtained / $totalMax) * 100, 2) : 0;
    $finalGrade = calculateGrade($totalObtained, $totalMax);
    $resultStatus = $isFail ? 'Fail' : 'Pass';
    
    // Calculate attendance ratio
    $stmtAtt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) as present FROM attendance WHERE student_id = ? AND school_id = ? AND academic_year_id = ?");
    $stmtAtt->execute([$student_id, $school_id, $exam['academic_year_id']]);
    $att = $stmtAtt->fetch();
    
    $attTotal = intval($att['total']);
    $attPresent = intval($att['present']);
    $attRatio = $attTotal > 0 ? round(($attPresent / $attTotal) * 100) : 100;
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Report Card - <?= htmlspecialchars($student['first_name'] . '_' . $student['last_name']) ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            @media print {
                .no-print { display: none; }
                body { background-color: white; }
                .report-card-container { border: 2px solid #333 !important; }
            }
            body { background-color: #f1f5f9; padding: 20px; }
            .report-card-container {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                border: 3px double #4f46e5;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            }
        </style>
    </head>
    <body>
        <div class="no-print text-center mb-4">
            <button onclick="window.print()" class="btn btn-primary px-4 rounded-pill"><i class="fa-solid fa-print me-2"></i>Print Report Card</button>
            <a href="report_cards.php?exam_id=<?= $exam_id ?>" class="btn btn-secondary px-4 rounded-pill">Back to List</a>
        </div>
        
        <div class="report-card-container">
            <!-- Header layout -->
            <div class="row align-items-center border-bottom pb-4 mb-4">
                <div class="col-3 text-center">
                    <?php if (!empty($school['logo'])): ?>
                        <img src="<?= UPLOAD_URL ?>logos/<?= $school['logo'] ?>" alt="Logo" style="max-height: 80px;">
                    <?php else: ?>
                        <div class="h3 fw-bold text-primary mb-0">LOGO</div>
                    <?php endif; ?>
                </div>
                <div class="col-9 text-center text-md-start">
                    <h3 class="fw-bold mb-1"><?= htmlspecialchars($school['school_name']) ?></h3>
                    <p class="text-secondary mb-1" style="font-size: 13px;"><?= htmlspecialchars($school['address']) ?>, <?= htmlspecialchars($school['city']) ?>, <?= htmlspecialchars($school['state']) ?></p>
                    <small class="text-muted">Board: <?= htmlspecialchars($school['board']) ?> | Medium: <?= htmlspecialchars($school['medium']) ?></small>
                </div>
            </div>
            
            <h4 class="text-center fw-bold mb-4">PROGRESS REPORT CARD</h4>
            
            <!-- Student particulars -->
            <div class="row g-3 mb-4 border p-3 rounded bg-light">
                <div class="col-md-6">
                    <small class="text-muted d-block">Student Name</small>
                    <span class="fw-bold"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></span>
                </div>
                <div class="col-md-6">
                    <small class="text-muted d-block">Student ID / Roll No</small>
                    <span class="fw-bold"><code><?= htmlspecialchars($student['student_id']) ?></code> / #<?= htmlspecialchars($student['roll_number']) ?></span>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Standard / Class</small>
                    <span class="fw-bold"><?= htmlspecialchars($exam['standard_name']) ?></span>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Section / division</small>
                    <span class="fw-bold"><?= htmlspecialchars($student['section_name']) ?></span>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Evaluation Examination</small>
                    <span class="fw-bold text-indigo"><?= htmlspecialchars($exam['exam_name']) ?></span>
                </div>
            </div>
            
            <!-- Evaluation table -->
            <table class="table table-bordered align-middle text-center mb-4">
                <thead class="table-light">
                    <tr>
                        <th>Subject</th>
                        <th>Max Marks</th>
                        <th>Passing Marks</th>
                        <th>Marks Obtained</th>
                        <th>Grade</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($marks)): ?>
                        <tr>
                            <td colspan="6" class="text-muted py-3">No evaluation logs found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($marks as $m): ?>
                            <tr>
                                <td class="text-start fw-bold"><?= htmlspecialchars($m['subject_name']) ?></td>
                                <td><?= htmlspecialchars($m['max_marks']) ?></td>
                                <td><?= htmlspecialchars($m['passing_marks']) ?></td>
                                <td class="fw-bold <?= floatval($m['marks_obtained']) < floatval($m['passing_marks']) ? 'text-danger' : 'text-success' ?>"><?= htmlspecialchars($m['marks_obtained']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($m['grade']) ?></span></td>
                                <td><small class="text-muted"><?= htmlspecialchars($m['remarks'] ?: '—') ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Summary calculations -->
            <div class="row g-3 border-top pt-4 mb-5">
                <div class="col-md-3 text-center border-end">
                    <small class="text-muted d-block">GRAND TOTAL</small>
                    <h4 class="fw-bold"><?= $totalObtained ?> / <?= $totalMax ?></h4>
                </div>
                <div class="col-md-3 text-center border-end">
                    <small class="text-muted d-block">PERCENTAGE</small>
                    <h4 class="fw-bold"><?= $percentage ?>%</h4>
                </div>
                <div class="col-md-3 text-center border-end">
                    <small class="text-muted d-block">FINAL GRADE</small>
                    <h4 class="fw-bold text-indigo"><?= $finalGrade ?></h4>
                </div>
                <div class="col-md-3 text-center">
                    <small class="text-muted d-block">RESULT STATUS</small>
                    <h4 class="fw-bold <?= $resultStatus === 'Pass' ? 'text-success' : 'text-danger' ?>"><?= $resultStatus ?></h4>
                </div>
            </div>
            
            <!-- Attendance and signatures -->
            <div class="row pt-3">
                <div class="col-md-6 mb-4">
                    <small class="text-muted d-block">Attendance Ratio</small>
                    <strong><?= $attPresent ?> present out of <?= $attTotal ?> school days (<?= $attRatio ?>%)</strong>
                </div>
                <div class="col-md-6 text-end mb-4">
                    <small class="text-muted d-block">Evaluation Date</small>
                    <strong><?= date('Y-m-d') ?></strong>
                </div>
                
                <div class="col-4 text-center mt-5">
                    <div style="border-top: 1px solid #999; margin-top: 40px; padding-top: 10px;"><small>Class Teacher Signature</small></div>
                </div>
                <div class="col-4 text-center mt-5">
                    <div style="border-top: 1px solid #999; margin-top: 40px; padding-top: 10px;"><small>Parent/Guardian</small></div>
                </div>
                <div class="col-4 text-center mt-5">
                    <div style="border-top: 1px solid #999; margin-top: 40px; padding-top: 10px;"><small>Principal seal & signature</small></div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// -------------------------------------------------------------
// CASE 2: List students who took this exam
// -------------------------------------------------------------
$activePage = 'results';
require_once __DIR__ . '/../includes/header.php';

// Fetch students in this standard
$stmtStus = $db->prepare("
    SELECT s.id, s.first_name, s.last_name, s.student_id, s.roll_number, sec.name as section_name 
    FROM students s 
    JOIN sections sec ON s.section_id = sec.id 
    WHERE s.school_id = ? AND s.standard_id = ? AND s.status = 'active' 
    ORDER BY sec.name ASC, s.roll_number ASC
");
$stmtStus->execute([$school_id, $exam['standard_id']]);
$studentsList = $stmtStus->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="results.php" class="btn btn-light rounded-circle shadow-sm border"><i class="fa-solid fa-arrow-left text-secondary"></i></a>
        <div>
            <h2 class="fw-bold mb-0">Report Cards Directory — <?= htmlspecialchars($exam['exam_name']) ?></h2>
            <p class="text-secondary mb-0">Standard Level: <?= htmlspecialchars($exam['standard_name']) ?></p>
        </div>
    </div>
    
    <div class="card border-0 shadow-sm p-4 glass-card">
        <div class="table-responsive">
            <table class="table align-middle custom-table">
                <thead>
                    <tr>
                        <th>Roll No</th>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Section</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($studentsList)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No active students enrolled in this standard yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($studentsList as $stu): ?>
                            <tr>
                                <td><code>#<?= htmlspecialchars($stu['roll_number']) ?></code></td>
                                <td><code><?= htmlspecialchars($stu['student_id']) ?></code></td>
                                <td class="fw-bold"><?= htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']) ?></td>
                                <td>Section <?= htmlspecialchars($stu['section_name']) ?></td>
                                <td>
                                    <a href="report_cards.php?exam_id=<?= $exam_id ?>&student_id=<?= $stu['id'] ?>" target="_blank" class="btn btn-indigo btn-sm rounded-pill"><i class="fa-solid fa-print me-1"></i>Generate Report Card</a>
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
