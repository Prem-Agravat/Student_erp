<?php
// C:\xampp\htdocs\school-erp\school\marks.php

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
    echo '<p class="mb-3">Please configure and activate an Academic Year before entering Marks.</p>';
    echo '<a href="academic_years.php" class="btn btn-indigo rounded-pill px-4">Manage Academic Years</a>';
    echo '</div>';
    echo '</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Fetch Exams list
$stmtExams = $db->prepare("SELECT id, exam_name, standard_id FROM exams WHERE school_id = ? AND academic_year_id = ?");
$stmtExams->execute([$school_id, $activeYear['id']]);
$exams = $stmtExams->fetchAll();

// Fetch Sections
$stmtSecs = $db->prepare("SELECT id, name, standard_id FROM sections WHERE school_id = ? AND status = 'active' ORDER BY name ASC");
$stmtSecs->execute([$school_id]);
$sections = $stmtSecs->fetchAll();

// Filter values
$exam_id = intval($_GET['exam_id'] ?? 0);
$sec_id = intval($_GET['section_id'] ?? 0);
$subject_id = intval($_GET['subject_id'] ?? 0);

$students = [];
$exam_subject = null;

if ($exam_id > 0 && $sec_id > 0 && $subject_id > 0) {
    // Verify subject is configured in this exam
    $stmtExSub = $db->prepare("SELECT es.*, sub.name as subject_name FROM exam_subjects es JOIN subjects sub ON es.subject_id = sub.id WHERE es.exam_id = ? AND es.subject_id = ? AND es.school_id = ?");
    $stmtExSub->execute([$exam_id, $subject_id, $school_id]);
    $exam_subject = $stmtExSub->fetch();
    
    if (!$exam_subject) {
        $message = getAlert('danger', "The selected subject has not been mapped/configured for this exam. Please map it first.");
    } else {
        // Fetch students and existing marks if any
        $stmtStu = $db->prepare("
            SELECT s.id as student_id, s.first_name, s.last_name, s.roll_number,
                   m.marks_obtained, m.remarks, m.grade 
            FROM students s 
            LEFT JOIN marks m ON s.id = m.student_id AND m.exam_id = ? AND m.subject_id = ? 
            WHERE s.school_id = ? AND s.section_id = ? AND s.status = 'active' 
            ORDER BY s.roll_number ASC
        ");
        $stmtStu->execute([$exam_id, $subject_id, $school_id, $sec_id]);
        $students = $stmtStu->fetchAll();
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">Student Marks Entry Panel</h2>
            <p class="text-secondary">Enter evaluation marks for classroom exams with instant saving.</p>
        </div>
    </div>
    
    <?= $message ?>
    
    <!-- Filter Card -->
    <div class="card border-0 shadow-sm p-4 mb-4 glass-card">
        <form method="GET" class="row g-3 align-items-end" id="filterForm">
            <div class="col-md-3">
                <label class="form-label font-semibold">Select Exam <span class="text-danger">*</span></label>
                <select name="exam_id" class="form-select" required id="examSelect">
                    <option value="">Choose Exam</option>
                    <?php foreach ($exams as $ex): ?>
                        <option value="<?= $ex['id'] ?>" data-std="<?= $ex['standard_id'] ?>" <?= $exam_id === $ex['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ex['exam_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label font-semibold">Select Section <span class="text-danger">*</span></label>
                <select name="section_id" class="form-select" required id="secSelect">
                    <option value="">Choose Section</option>
                    <?php foreach ($sections as $sec): ?>
                        <option value="<?= $sec['id'] ?>" data-std="<?= $sec['standard_id'] ?>" <?= $sec_id === $sec['id'] ? 'selected' : '' ?>><?= htmlspecialchars($sec['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label font-semibold">Select Subject <span class="text-danger">*</span></label>
                <select name="subject_id" class="form-select" required id="subSelect">
                    <option value="">Choose Subject</option>
                    <!-- Dynamically populated via script -->
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-indigo w-100 rounded-pill"><i class="fa-solid fa-clipboard-list me-2"></i>Load Marks Sheet</button>
            </div>
        </form>
    </div>
    
    <?php if ($exam_subject && !empty($students)): ?>
        <div class="card border-0 shadow-sm p-4 glass-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">Marks Entry Sheet — <?= htmlspecialchars($exam_subject['subject_name']) ?> <small class="text-muted">(Max Marks: <?= htmlspecialchars($exam_subject['max_marks']) ?>)</small></h5>
                <span class="badge bg-success" id="saveIndicator" style="display: none;"><i class="fa-solid fa-cloud-arrow-up me-1"></i>Saved</span>
            </div>
            
            <div class="table-responsive">
                <table class="table align-middle custom-table">
                    <thead>
                        <tr>
                            <th style="width: 100px;">Roll No</th>
                            <th>Student Name</th>
                            <th style="width: 200px;">Marks Obtained</th>
                            <th>Grade</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $stu): ?>
                            <tr class="student-mark-row" data-student-id="<?= $stu['student_id'] ?>">
                                <td><code>#<?= htmlspecialchars($stu['roll_number']) ?></code></td>
                                <td class="fw-bold"><?= htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']) ?></td>
                                <td>
                                    <input type="number" 
                                           class="form-control form-control-sm marks-input" 
                                           value="<?= htmlspecialchars($stu['marks_obtained'] ?? '') ?>" 
                                           min="0" 
                                           max="<?= htmlspecialchars($exam_subject['max_marks']) ?>" 
                                           step="0.5" 
                                           placeholder="Max: <?= htmlspecialchars($exam_subject['max_marks']) ?>">
                                </td>
                                <td>
                                    <span class="badge bg-secondary grade-badge"><?= htmlspecialchars($stu['grade'] ?: '—') ?></span>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm remarks-input" value="<?= htmlspecialchars($stu['remarks'] ?? '') ?>" placeholder="e.g. Good performance">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const examSelect = document.getElementById('examSelect');
    const secSelect = document.getElementById('secSelect');
    const subSelect = document.getElementById('subSelect');
    
    // Fetch subjects dynamically based on selected exam's standard
    const loadSubjects = () => {
        const selectedExamOpt = examSelect.options[examSelect.selectedIndex];
        if (!selectedExamOpt || !selectedExamOpt.value) {
            subSelect.innerHTML = '<option value="">Choose Subject</option>';
            return;
        }
        
        const stdId = selectedExamOpt.getAttribute('data-std');
        
        // Fetch via API or load standard subjects
        fetch('<?= BASE_URL ?>api/students.php?action=get_standard_subjects&standard_id=' + stdId)
            .then(res => res.json())
            .then(data => {
                subSelect.innerHTML = '<option value="">Choose Subject</option>';
                data.forEach(sub => {
                    const opt = document.createElement('option');
                    opt.value = sub.id;
                    opt.textContent = sub.name;
                    if (sub.id == '<?= $subject_id ?>') {
                        opt.selected = true;
                    }
                    subSelect.appendChild(opt);
                });
            });
    };

    // Filter section select options by selected exam standard
    const filterSections = () => {
        const opt = examSelect.options[examSelect.selectedIndex];
        if (opt && opt.value) {
            const stdId = opt.getAttribute('data-std');
            Array.from(secSelect.options).forEach(secOpt => {
                if (secOpt.value === '' || secOpt.getAttribute('data-std') === stdId) {
                    secOpt.style.display = 'block';
                } else {
                    secOpt.style.display = 'none';
                }
            });
        }
    };
    
    examSelect.addEventListener('change', () => {
        loadSubjects();
        filterSections();
        secSelect.value = '';
    });
    
    if (examSelect.value !== '') {
        loadSubjects();
        filterSections();
    }
    
    // AJAX saving for Marks
    const rows = document.querySelectorAll('.student-mark-row');
    rows.forEach(row => {
        const stuId = row.getAttribute('data-student-id');
        const marksInput = row.querySelector('.marks-input');
        const remarksInput = row.querySelector('.remarks-input');
        const gradeBadge = row.querySelector('.grade-badge');
        
        const saveMark = () => {
            const marks = parseFloat(marksInput.value);
            const remarks = remarksInput.value;
            
            if (isNaN(marks) || marks < 0 || marks > <?= $exam_subject['max_marks'] ?? 100 ?>) {
                return; // skip saving invalid
            }
            
            const formData = new FormData();
            formData.value = ""; // dummy to clear lints
            formData.append('exam_id', '<?= $exam_id ?>');
            formData.append('student_id', stuId);
            formData.append('subject_id', '<?= $subject_id ?>');
            formData.append('marks_obtained', marks);
            formData.append('max_marks', '<?= $exam_subject['max_marks'] ?? 100 ?>');
            formData.append('remarks', remarks);
            formData.append('csrf_token', '<?= generateCSRFToken() ?>');
            
            const saveIndicator = document.getElementById('saveIndicator');
            
            fetch('<?= BASE_URL ?>api/marks.php?action=save', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    // Update Grade badge
                    gradeBadge.textContent = res.grade;
                    gradeBadge.className = 'badge bg-indigo grade-badge';
                    
                    // Show indicator
                    saveIndicator.style.display = 'inline-block';
                    setTimeout(() => {
                        saveIndicator.style.display = 'none';
                    }, 1000);
                } else {
                    alert('Save failed: ' + res.error);
                }
            });
        };
        
        marksInput.addEventListener('change', saveMark);
        remarksInput.addEventListener('change', saveMark);
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
