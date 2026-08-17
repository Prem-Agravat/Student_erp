<?php
// C:\xampp\htdocs\school-erp\school\notices.php

$activePage = 'notices';
require_once __DIR__ . '/../includes/school_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$school_id = $_SESSION['school_id'];
$message = '';

// Fetch Standards and Sections for Target Audience selectors
$standards = $db->query("SELECT id, name FROM standards WHERE school_id = $school_id AND status = 'active' ORDER BY display_order ASC")->fetchAll();
$sections = $db->query("SELECT s.id, s.name, std.name as standard_name FROM sections s JOIN standards std ON s.standard_id = std.id WHERE s.school_id = $school_id AND s.status = 'active' ORDER BY std.display_order ASC, s.name ASC")->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF validation failed.");
    }
    
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? 'General Notice');
    $publish_date = sanitizeInput($_POST['publish_date'] ?? date('Y-m-d'));
    $expiry_date = sanitizeInput($_POST['expiry_date'] ?? null);
    $target_audience = sanitizeInput($_POST['target_audience'] ?? 'All Students');
    
    $target_id = null;
    if ($target_audience === 'Specific Standard') {
        $target_id = intval($_POST['target_standard_id'] ?? 0);
    } elseif ($target_audience === 'Specific Section') {
        $target_id = intval($_POST['target_section_id'] ?? 0);
    }
    
    if (empty($title) || empty($description)) {
        $message = getAlert('danger', "Title and Description are required.");
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO notices (school_id, title, description, category, publish_date, expiry_date, target_audience, target_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$school_id, $title, $description, $category, $publish_date, $expiry_date ?: null, $target_audience, $target_id]);
            
            logActivity("Create Notice", "Created notice bulletin: $title ($category)");
            $message = getAlert('success', "Notice '$title' published successfully.");
        } catch (PDOException $e) {
            $message = getAlert('danger', "Failed to publish notice: " . $e->getMessage());
        }
    }
}

// Fetch all notices (last 50)
$stmtNotices = $db->prepare("SELECT * FROM notices WHERE school_id = ? ORDER BY id DESC LIMIT 50");
$stmtNotices->execute([$school_id]);
$noticesList = $stmtNotices->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Noticeboard Bulletin</h2>
            <p class="text-secondary mb-0">Publish circulars, holiday warnings, exams circulars, or fees alerts.</p>
        </div>
        <button class="btn btn-indigo rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createNoticeModal"><i class="fa-solid fa-plus me-2"></i>Publish Notice</button>
    </div>
    
    <?= $message ?>
    
    <div class="card border-0 shadow-sm p-4 glass-card">
        <div class="table-responsive">
            <table class="table align-middle custom-table">
                <thead>
                    <tr>
                        <th>Date Posted</th>
                        <th>Notice Title</th>
                        <th>Category</th>
                        <th>Target Audience</th>
                        <th>Expiry Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($noticesList)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No notices published yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($noticesList as $note): ?>
                            <tr>
                                <td><?= date('Y-m-d', strtotime($note['publish_date'])) ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($note['title']) ?></div>
                                    <small class="text-secondary d-block"><?= htmlspecialchars(substr($note['description'], 0, 80)) ?>...</small>
                                </td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($note['category']) ?></span></td>
                                <td>
                                    <span class="badge bg-indigo bg-opacity-10 text-indigo">
                                        <?= htmlspecialchars($note['target_audience']) ?>
                                    </span>
                                </td>
                                <td><?= $note['expiry_date'] ? htmlspecialchars($note['expiry_date']) : 'Never' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Notice Modal -->
<div class="modal fade" id="createNoticeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST">
            <?= getCSRFInput() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Publish Bulletin Notice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-semibold">Notice Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g. Mid Term Exam Schedule Out">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Notice Category</label>
                            <select name="category" class="form-select">
                                <option value="General Notice">General Notice</option>
                                <option value="Exam Notice">Exam Notice</option>
                                <option value="Holiday Notice">Holiday Notice</option>
                                <option value="Result Notice">Result Notice</option>
                                <option value="Attendance Notice">Attendance Notice</option>
                                <option value="Fee Notice">Fee Notice</option>
                                <option value="Important Announcement">Important Announcement</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Publish Date <span class="text-danger">*</span></label>
                            <input type="date" name="publish_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label font-semibold">Detailed Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="4" required placeholder="Enter circular content details..."></textarea>
                    </div>
                    
                    <div class="row mb-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Target Audience</label>
                            <select name="target_audience" class="form-select" id="audienceSelect">
                                <option value="All Students">All Students</option>
                                <option value="Specific Standard">Specific Standard</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4" id="targetStdGroup" style="display: none;">
                            <label class="form-label font-semibold">Select Standard</label>
                            <select name="target_standard_id" class="form-select">
                                <?php foreach ($standards as $std): ?>
                                    <option value="<?= $std['id'] ?>"><?= htmlspecialchars($std['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Expiry Date (Optional)</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-indigo">Publish Circular</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const audienceSelect = document.getElementById('audienceSelect');
    const targetStdGroup = document.getElementById('targetStdGroup');
    
    if (audienceSelect) {
        audienceSelect.addEventListener('change', function() {
            const val = audienceSelect.value;
            if (val === 'All Students') {
                targetStdGroup.style.display = 'none';
            } else if (val === 'Specific Standard') {
                targetStdGroup.style.display = 'block';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
