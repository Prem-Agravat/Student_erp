<?php
// C:\xampp\htdocs\school-erp\student\timetable.php

$activePage = 'timetable';
require_once __DIR__ . '/../includes/student_auth.php';
require_once __DIR__ . '/../includes/header.php';

$student_id = $_SESSION['student_id'];
$school_id = $_SESSION['school_id'];
$db = getDBConnection();

// Fetch student profile to get standard and section
$stmtStu = $db->prepare("SELECT standard_id, section_id, academic_year_id FROM students WHERE id = ? AND school_id = ?");
$stmtStu->execute([$student_id, $school_id]);
$stu = $stmtStu->fetch();

$timetableGrid = [];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$periods = [1, 2, 3, 4, 5, 6, 7, 8];

if ($stu) {
    // Fetch timetable grid
    $stmtGrid = $db->prepare("
        SELECT t.*, sub.name as subject_name 
        FROM timetables t 
        JOIN subjects sub ON t.subject_id = sub.id 
        WHERE t.school_id = ? AND t.academic_year_id = ? AND t.standard_id = ? AND t.section_id = ?
    ");
    $stmtGrid->execute([$school_id, $stu['academic_year_id'], $stu['standard_id'], $stu['section_id']]);
    $gridRows = $stmtGrid->fetchAll();
    
    foreach ($gridRows as $row) {
        $timetableGrid[$row['day']][$row['period_number']] = $row;
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">My Class Timetable</h2>
            <p class="text-secondary">Weekly period assignments and teacher details.</p>
        </div>
    </div>
    
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
                                            <small class="d-block text-secondary">Room: <?= htmlspecialchars($cell['room_number'] ?: 'N/A') ?></small>
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
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
