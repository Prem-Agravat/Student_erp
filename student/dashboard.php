<?php
// C:\xampp\htdocs\school-erp\student\dashboard.php

$activePage = 'dashboard';
require_once __DIR__ . '/../includes/student_auth.php';
require_once __DIR__ . '/../includes/header.php';

$student_id = $_SESSION['student_id'];
$school_id = $_SESSION['school_id'];
$db = getDBConnection();

// Fetch student full profile details
$stmt = $db->prepare("
    SELECT s.*, std.name as standard_name, sec.name as section_name, ay.name as academic_year_name 
    FROM students s 
    JOIN standards std ON s.standard_id = std.id 
    JOIN sections sec ON s.section_id = sec.id 
    JOIN academic_years ay ON s.academic_year_id = ay.id
    WHERE s.id = ? AND s.school_id = ?
");
$stmt->execute([$student_id, $school_id]);
$student = $stmt->fetch();

if (!$student) {
    echo '<div class="alert alert-danger">Access denied. Student record not found.</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Calculate attendance percentage
$stmtAtt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) as present FROM attendance WHERE student_id = ? AND school_id = ?");
$stmtAtt->execute([$student_id, $school_id]);
$attCounts = $stmtAtt->fetch();
$totalDays = intval($attCounts['total']);
$presentDays = intval($attCounts['present']);
$attendancePercent = $totalDays > 0 ? round(($presentDays / $totalDays) * 100) : 100;

// Fetch latest published exam result
$stmtResult = $db->prepare("
    SELECT e.exam_name, COUNT(m.id) as subjects_count 
    FROM marks m 
    JOIN exams e ON m.exam_id = e.id 
    WHERE m.student_id = ? AND m.school_id = ? AND e.status = 'published' 
    GROUP BY e.id 
    ORDER BY e.end_date DESC 
    LIMIT 1
");
$stmtResult->execute([$student_id, $school_id]);
$latestResult = $stmtResult->fetch();

// Fetch upcoming exams
$today = date('Y-m-d');
$stmtExams = $db->prepare("SELECT * FROM exams WHERE school_id = ? AND standard_id = ? AND start_date >= ? ORDER BY start_date ASC LIMIT 3");
$stmtExams->execute([$school_id, $student['standard_id'], $today]);
$exams = $stmtExams->fetchAll();

// Fetch target notices (all, standard, or section specific notices)
$stmtNotices = $db->prepare("
    SELECT * FROM notices 
    WHERE school_id = ? 
      AND (publish_date <= ? AND (expiry_date IS NULL OR expiry_date >= ?))
      AND (
        target_audience = 'All Students' 
        OR (target_audience = 'Specific Standard' AND target_id = ?)
        OR (target_audience = 'Specific Section' AND target_id = ?)
      )
    ORDER BY id DESC LIMIT 5
");
$stmtNotices->execute([$school_id, $today, $today, $student['standard_id'], $student['section_id']]);
$notices = $stmtNotices->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">Welcome Back, <?= htmlspecialchars($student['first_name']) ?>!</h2>
            <p class="text-secondary">Track your attendance, exam schedules, reports card entries, and school notices.</p>
        </div>
    </div>
    
    <!-- Profile Mini Card -->
    <div class="card border-0 shadow-sm p-4 mb-4 glass-card">
        <div class="row align-items-center">
            <div class="col-md-2 text-center text-md-start mb-3 mb-md-0">
                <?php if (!empty($student['photo'])): ?>
                    <img src="<?= UPLOAD_URL ?>students/<?= $student['photo'] ?>" alt="Profile" class="rounded-4 border" style="width: 100px; height: 100px; object-fit: cover;">
                <?php else: ?>
                    <div class="bg-indigo bg-opacity-10 text-indigo rounded-4 d-flex align-items-center justify-content-center fw-bold mx-auto mx-md-0" style="width: 100px; height: 100px; font-size: 36px;">
                        <?= strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6 text-center text-md-start">
                <h4 class="fw-bold mb-1"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></h4>
                <div class="text-muted mb-2">Student ID: <code><?= htmlspecialchars($student['student_id']) ?></code></div>
                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                    <span class="badge bg-indigo"><?= htmlspecialchars($student['standard_name']) ?> — <?= htmlspecialchars($student['section_name']) ?></span>
                    <span class="badge bg-secondary">Roll Number: #<?= htmlspecialchars($student['roll_number']) ?></span>
                    <span class="badge bg-light text-dark">Academic Year: <?= htmlspecialchars($student['academic_year_name']) ?></span>
                </div>
            </div>
            <div class="col-md-4 text-center text-md-end mt-3 mt-md-0">
                <a href="profile.php" class="btn btn-indigo rounded-pill px-4"><i class="fa-solid fa-user me-2"></i>My Profile Details</a>
            </div>
        </div>
    </div>
    
    <div class="row g-4 mb-4">
        <!-- Attendance Widget -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 h-100 glass-card text-center">
                <h6 class="text-muted fw-bold mb-3">MY ATTENDANCE</h6>
                <div class="position-relative d-inline-block mx-auto my-3" style="width: 120px; height: 120px;">
                    <h2 class="position-absolute top-50 start-50 translate-middle fw-bold text-indigo mb-0"><?= $attendancePercent ?>%</h2>
                    <svg viewBox="0 0 36 36" class="w-100 h-100">
                        <path class="text-light" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="text-indigo" stroke-dasharray="<?= $attendancePercent ?>, 100" stroke-width="3" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                </div>
                <div class="text-muted mt-2" style="font-size: 13px;">Days Present: <?= $presentDays ?> / <?= $totalDays ?> Total</div>
            </div>
        </div>
        
        <!-- Results Widget -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 h-100 glass-card text-center d-flex flex-column justify-content-center">
                <h6 class="text-muted fw-bold mb-3">LATEST RESULT</h6>
                <?php if ($latestResult): ?>
                    <h3 class="fw-bold mb-1"><?= htmlspecialchars($latestResult['exam_name']) ?></h3>
                    <div class="text-success fw-bold my-2" style="font-size: 18px;"><i class="fa-solid fa-circle-check me-1"></i>Result Published</div>
                    <small class="text-muted"><?= $latestResult['subjects_count'] ?> subjects evaluated.</small>
                    <a href="results.php" class="btn btn-indigo rounded-pill mt-3 btn-sm mx-auto px-4">View Report Card</a>
                <?php else: ?>
                    <div class="text-muted py-4"><i class="fa-solid fa-lock fa-2xl mb-3 d-block text-secondary"></i>No published results available yet.</div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Upcoming Exam Schedule Widget -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 h-100 glass-card">
                <h6 class="text-muted fw-bold mb-3"><i class="fa-solid fa-calendar-check text-indigo me-2"></i>EXAM SCHEDULE</h6>
                <div class="list-group list-group-flush">
                    <?php if (empty($exams)): ?>
                        <div class="text-muted py-3 text-center">No exams scheduled currently.</div>
                    <?php else: ?>
                        <?php foreach ($exams as $ex): ?>
                            <div class="list-group-item bg-transparent px-0 py-2">
                                <div class="fw-bold" style="font-size: 14px;"><?= htmlspecialchars($ex['exam_name']) ?></div>
                                <small class="text-muted">Start Date: <?= date('Y-m-d', strtotime($ex['start_date'])) ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Notice Board -->
        <div class="col-12">
            <div class="card border-0 shadow-sm p-4 glass-card">
                <h5 class="fw-bold mb-4"><i class="fa-solid fa-bullhorn text-indigo me-2"></i>Notice Board / Announcements</h5>
                <div class="list-group list-group-flush">
                    <?php if (empty($notices)): ?>
                        <div class="text-muted py-4 text-center">No notices available. Check back later.</div>
                    <?php else: ?>
                        <?php foreach ($notices as $note): ?>
                            <div class="list-group-item bg-transparent px-0 py-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($note['title']) ?></h6>
                                    <span class="badge bg-indigo"><?= htmlspecialchars($note['category']) ?></span>
                                </div>
                                <p class="text-secondary mb-1" style="font-size: 13px;"><?= nl2br(htmlspecialchars($note['description'])) ?></p>
                                <small class="text-muted">Date Posted: <?= date('Y-m-d', strtotime($note['publish_date'])) ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
