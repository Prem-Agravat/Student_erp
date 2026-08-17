<?php
// C:\xampp\htdocs\school-erp\student\exams.php

$activePage = 'exams';
require_once __DIR__ . '/../includes/student_auth.php';
require_once __DIR__ . '/../includes/header.php';

$student_id = $_SESSION['student_id'];
$school_id = $_SESSION['school_id'];
$db = getDBConnection();

// Fetch student details
$stmt = $db->prepare("
    SELECT s.*, std.name as standard_name, ay.name as academic_year_name 
    FROM students s 
    JOIN standards std ON s.standard_id = std.id 
    JOIN academic_years ay ON s.academic_year_id = ay.id
    WHERE s.id = ? AND s.school_id = ?
");
$stmt->execute([$student_id, $school_id]);
$student = $stmt->fetch();

if (!$student) {
    echo '<div class="alert alert-danger m-4">Access denied. Student record not found.</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Fetch published exams for this student's standard
$stmtExams = $db->prepare("
    SELECT * FROM exams 
    WHERE school_id = ? AND standard_id = ? AND academic_year_id = ? AND status = 'published'
    ORDER BY start_date ASC
");
$stmtExams->execute([$school_id, $student['standard_id'], $student['academic_year_id']]);
$examsList = $stmtExams->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">My Exam Schedules</h2>
            <p class="text-secondary">View upcoming examination rosters and subject-wise guidelines for <?= htmlspecialchars($student['standard_name']) ?>.</p>
        </div>
    </div>

    <?php if (empty($examsList)): ?>
        <div class="alert alert-info py-4 border-0 shadow-sm glass-card text-center">
            <div class="mb-3 text-indigo" style="font-size: 40px;"><i class="fa-solid fa-calendar-xmark"></i></div>
            <h5 class="fw-bold">No Published Exams</h5>
            <p class="text-secondary mb-0">There are no upcoming exams scheduled or published for your class standard at this moment.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($examsList as $exam): ?>
                <?php
                // Fetch scheduled subjects for this exam
                $stmtSubjects = $db->prepare("
                    SELECT es.*, sub.name as subject_name 
                    FROM exam_subjects es
                    JOIN subjects sub ON es.subject_id = sub.id
                    WHERE es.school_id = ? AND es.exam_id = ?
                    ORDER BY es.exam_date ASC
                ");
                $stmtSubjects->execute([$school_id, $exam['id']]);
                $examSubjects = $stmtSubjects->fetchAll();
                ?>
                <div class="col-12 col-xl-6">
                    <div class="card border-0 shadow-sm p-4 glass-card h-100" style="background: linear-gradient(135deg, #ffffff 0%, #fcfcff 100%);">
                        <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-3">
                            <div>
                                <h4 class="fw-bold text-indigo mb-1"><?= htmlspecialchars($exam['exam_name']) ?></h4>
                                <span class="badge bg-light text-dark border"><i class="fa-solid fa-calendar-days text-indigo me-1"></i><?= date('d M Y', strtotime($exam['start_date'])) ?> to <?= date('d M Y', strtotime($exam['end_date'])) ?></span>
                            </div>
                            <span class="badge bg-success rounded-pill px-3 py-2">Published</span>
                        </div>

                        <?php if (!empty($exam['description'])): ?>
                            <p class="text-secondary small mb-4 bg-light p-3 rounded-3 border-start border-indigo border-3"><?= nl2br(htmlspecialchars($exam['description'])) ?></p>
                        <?php endif; ?>

                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-list-check text-indigo me-2"></i>Subject Roster & Passing Marks</h6>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle table-borderless">
                                <thead class="table-light">
                                    <tr style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <th class="py-2 px-3">Subject</th>
                                        <th class="py-2">Exam Date</th>
                                        <th class="py-2 text-center">Max Marks</th>
                                        <th class="py-2 text-center">Passing Marks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($examSubjects)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">Subject timetable details not configured yet.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($examSubjects as $sub): ?>
                                            <tr class="border-bottom border-light">
                                                <td class="fw-bold text-indigo py-3 px-3"><?= htmlspecialchars($sub['subject_name']) ?></td>
                                                <td>
                                                    <?php if ($sub['exam_date']): ?>
                                                        <code class="text-dark"><?= date('d-m-Y', strtotime($sub['exam_date'])) ?></code>
                                                        <small class="d-block text-secondary text-uppercase" style="font-size: 9px;"><?= date('l', strtotime($sub['exam_date'])) ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not Set</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center fw-semibold"><?= htmlspecialchars($sub['max_marks']) ?></td>
                                                <td class="text-center text-danger fw-semibold"><?= htmlspecialchars($sub['passing_marks']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
