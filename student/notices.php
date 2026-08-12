<?php
// C:\xampp\htdocs\school-erp\student\notices.php

$activePage = 'notices';
require_once __DIR__ . '/../includes/student_auth.php';
require_once __DIR__ . '/../includes/header.php';

$student_id = $_SESSION['student_id'];
$school_id = $_SESSION['school_id'];
$db = getDBConnection();

// Fetch student profile to know standard and section
$stmtStu = $db->prepare("SELECT standard_id, section_id FROM students WHERE id = ? AND school_id = ?");
$stmtStu->execute([$student_id, $school_id]);
$stu = $stmtStu->fetch();

$notices = [];
if ($stu) {
    $today = date('Y-m-d');
    $stmtNot = $db->prepare("
        SELECT * FROM notices 
        WHERE school_id = ? 
          AND (publish_date <= ? AND (expiry_date IS NULL OR expiry_date >= ?))
          AND (
            target_audience = 'All Students' 
            OR (target_audience = 'Specific Standard' AND target_id = ?)
            OR (target_audience = 'Specific Section' AND target_id = ?)
          )
        ORDER BY id DESC
    ");
    $stmtNot->execute([$school_id, $today, $today, $stu['standard_id'], $stu['section_id']]);
    $notices = $stmtNot->fetchAll();
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">My Notices & Announcements</h2>
            <p class="text-secondary">Official school circulars and notifications posted for you.</p>
        </div>
    </div>
    
    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm p-4 glass-card">
                <div class="list-group list-group-flush">
                    <?php if (empty($notices)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fa-solid fa-bell-slash fa-3x mb-3 text-secondary"></i>
                            <h5 class="fw-bold text-dark">No Notices Active</h5>
                            <p class="mb-0">You do not have any new announcements at this time.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notices as $note): ?>
                            <div class="list-group-item bg-transparent border-bottom px-0 py-4">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h5 class="fw-bold text-indigo mb-1" style="color: #4f46e5;"><?= htmlspecialchars($note['title']) ?></h5>
                                        <small class="text-muted"><i class="fa-solid fa-calendar me-1"></i>Date Posted: <?= date('Y-m-d', strtotime($note['publish_date'])) ?></small>
                                    </div>
                                    <span class="badge bg-secondary px-3 py-2 font-semibold"><?= htmlspecialchars($note['category']) ?></span>
                                </div>
                                <p class="text-secondary mb-0 mt-2" style="font-size: 15px; line-height: 1.6;"><?= nl2br(htmlspecialchars($note['description'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
