<?php
// C:\xampp\htdocs\school-erp\school\student_credentials.php

$activePage = 'student_credentials';
require_once __DIR__ . '/../includes/school_auth.php';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$school_id = $_SESSION['school_id'];

// Check active academic year
$stmtYear = $db->prepare("SELECT id, name FROM academic_years WHERE school_id = ? AND status = 'active'");
$stmtYear->execute([$school_id]);
$activeYear = $stmtYear->fetch();

if (!$activeYear) {
    echo '<div class="container-fluid">';
    echo '<div class="alert alert-warning py-4 shadow-sm border-0 glass-card">';
    echo '<h5 class="fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Active Academic Year Required</h5>';
    echo '<p class="mb-0">Please configure and activate an Academic Year before viewing credentials.</p>';
    echo '</div>';
    echo '</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$active_year_id = $activeYear['id'];

// Fetch Standards for filtering
$stmtStds = $db->prepare("SELECT id, name FROM standards WHERE school_id = ? AND status = 'active' ORDER BY display_order ASC");
$stmtStds->execute([$school_id]);
$standards = $stmtStds->fetchAll();

// Selected Standard Filter
$selected_standard_id = intval($_GET['standard_id'] ?? 0);

// Build student credentials query
$query = "
    SELECT s.id, s.first_name, s.last_name, s.student_id, s.roll_number, s.username, s.parent_phone, std.name as standard_name 
    FROM students s
    JOIN standards std ON s.standard_id = std.id
    WHERE s.school_id = ? AND s.academic_year_id = ? AND s.status = 'active'
";
$params = [$school_id, $active_year_id];

if ($selected_standard_id > 0) {
    $query .= " AND s.standard_id = ?";
    $params[] = $selected_standard_id;
}

$query .= " ORDER BY std.display_order ASC, s.roll_number ASC";
$stmtStudents = $db->prepare($query);
$stmtStudents->execute($params);
$students = $stmtStudents->fetchAll();
?>

<div class="container-fluid">
    <!-- Print-only header -->
    <div class="d-none d-print-block text-center mb-4">
        <h3 class="fw-bold text-uppercase"><?= htmlspecialchars($_SESSION['school_name'] ?? 'School ERP') ?></h3>
        <h5>Student Login IDs List (Academic Year: <?= htmlspecialchars($activeYear['name']) ?>)</h5>
        <hr>
    </div>

    <div class="row mb-4 d-print-none">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="fw-bold mb-1">Student Credentials Directory</h2>
                <p class="text-secondary mb-0">View or print list of active student login IDs (default password is the student's Father's Phone Number).</p>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-indigo rounded-pill px-4"><i class="fa-solid fa-print me-2"></i>Print Roster</button>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm p-4 mb-4 glass-card d-print-none">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label font-semibold">Filter by Standard</label>
                <select name="standard_id" class="form-select rounded-3">
                    <option value="">All Standards</option>
                    <?php foreach ($standards as $std): ?>
                        <option value="<?= $std['id'] ?>" <?= $selected_standard_id === intval($std['id']) ? 'selected' : '' ?>><?= htmlspecialchars($std['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-indigo w-100 rounded-3"><i class="fa-solid fa-filter me-2"></i>Filter</button>
            </div>
            <?php if ($selected_standard_id > 0): ?>
                <div class="col-md-2">
                    <a href="student_credentials.php" class="btn btn-light w-100 rounded-3">Clear</a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Credentials Table -->
    <div class="card border-0 shadow-sm p-4 glass-card">
        <div class="table-responsive">
            <table class="table align-middle custom-table">
                <thead class="table-light">
                    <tr>
                        <th class="py-3">Roll No</th>
                        <th class="py-3">Student Name</th>
                        <th class="py-3">Standard</th>
                        <th class="py-3">Student ID (Username)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No active student records found matching the criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $stu): ?>
                            <tr>
                                <td><code>#<?= htmlspecialchars($stu['roll_number']) ?></code></td>
                                <td class="fw-bold"><?= htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']) ?></td>
                                <td><?= htmlspecialchars($stu['standard_name']) ?></td>
                                <td><code class="bg-light text-indigo px-2 py-1 rounded"><?= htmlspecialchars($stu['username']) ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    body {
        background-color: white !important;
        color: black !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .glass-card {
        box-shadow: none !important;
        border: none !important;
        background: transparent !important;
        padding: 0 !important;
    }
    table {
        border-collapse: collapse !important;
        width: 100% !important;
    }
    th, td {
        border: 1px solid #dee2e6 !important;
        padding: 8px !important;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
