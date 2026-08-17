<?php
// C:\xampp\htdocs\school-erp\school\id_cards.php

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

$student_id = intval($_GET['student_id'] ?? 0);

// -------------------------------------------------------------
// CASE 1: Render specific student ID card (print-friendly layout)
// -------------------------------------------------------------
if ($student_id > 0) {
    // Fetch School Settings
    $stmtSch = $db->prepare("SELECT * FROM schools WHERE id = ?");
    $stmtSch->execute([$school_id]);
    $school = $stmtSch->fetch();
    
    // Fetch Student profile
    $stmtStu = $db->prepare("
        SELECT s.*, std.name as standard_name, sec.name as section_name, ay.name as academic_year_name 
        FROM students s 
        JOIN standards std ON s.standard_id = std.id 
        JOIN sections sec ON s.section_id = sec.id 
        JOIN academic_years ay ON s.academic_year_id = ay.id
        WHERE s.id = ? AND s.school_id = ?
    ");
    $stmtStu->execute([$student_id, $school_id]);
    $student = $stmtStu->fetch();
    
    if (!$student || !$school) {
        die("Record mismatch or missing profile.");
    }
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>ID Card - <?= htmlspecialchars($student['first_name'] . '_' . $student['last_name']) ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            @media print {
                .no-print { display: none; }
                body { background-color: white; }
            }
            body { background-color: #f1f5f9; padding: 40px; }
            .id-card {
                width: 320px;
                height: 480px;
                margin: 0 auto;
                background: white;
                border: 2px solid #6366f1;
                border-radius: 16px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                overflow: hidden;
                position: relative;
                font-family: 'Plus Jakarta Sans', sans-serif;
            }
            .id-card-header {
                background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
                color: white;
                padding: 16px;
                text-align: center;
            }
            .id-card-body {
                padding: 24px;
                text-align: center;
            }
            .id-photo {
                width: 110px;
                height: 110px;
                border-radius: 50%;
                object-fit: cover;
                border: 3px solid #e2e8f0;
                margin-bottom: 16px;
            }
            .id-card-footer {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                background: #f8fafc;
                border-top: 1px solid #e2e8f0;
                padding: 12px;
                text-align: center;
                font-size: 11px;
                color: #64748b;
            }
        </style>
    </head>
    <body>
        <div class="no-print text-center mb-4">
            <button onclick="window.print()" class="btn btn-primary px-4 rounded-pill"><i class="fa-solid fa-print me-2"></i>Print ID Card</button>
            <a href="id_cards.php" class="btn btn-secondary px-4 rounded-pill">Back to List</a>
        </div>
        
        <div class="id-card">
            <div class="id-card-header">
                <h6 class="fw-bold mb-0 text-uppercase tracking-wider"><?= htmlspecialchars($school['school_name']) ?></h6>
                <small style="font-size: 9px;">Code: <?= htmlspecialchars($school['school_code']) ?></small>
            </div>
            
            <div class="id-card-body">
                <?php if (!empty($student['photo'])): ?>
                    <img src="<?= UPLOAD_URL ?>students/<?= $student['photo'] ?>" class="id-photo" alt="Photo">
                <?php else: ?>
                    <div class="id-photo bg-light d-flex align-items-center justify-content-center fw-bold text-indigo mx-auto" style="font-size: 32px; width: 110px; height: 110px;">
                        <?= strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
                
                <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></h5>
                <code class="d-block mb-3 text-indigo" style="font-size: 13px;">ID: <?= htmlspecialchars($student['student_id']) ?></code>
                
                <table class="table table-sm table-borderless text-start mx-auto" style="max-width: 240px; font-size: 12px;">
                    <tr>
                        <td class="text-muted">Standard:</td>
                        <td class="fw-bold"><?= htmlspecialchars($student['standard_name']) ?></td>
                    </tr>

                    <tr>
                        <td class="text-muted">Roll No:</td>
                        <td class="fw-bold">#<?= htmlspecialchars($student['roll_number']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Academic:</td>
                        <td class="fw-bold"><?= htmlspecialchars($student['academic_year_name']) ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="id-card-footer">
                <div>Principal Signature</div>
                <div class="fw-bold mt-1 text-uppercase text-indigo" style="font-size: 9px;"><?= htmlspecialchars($school['city']) . ', ' . htmlspecialchars($school['state']) ?></div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// -------------------------------------------------------------
// CASE 2: List students to print ID cards
// -------------------------------------------------------------
$activePage = 'documents';
require_once __DIR__ . '/../includes/header.php';

// Fetch enrolled students
$stmtStus = $db->prepare("
    SELECT s.id, s.first_name, s.last_name, s.student_id, s.roll_number, std.name as standard_name, sec.name as section_name 
    FROM students s 
    JOIN standards std ON s.standard_id = std.id 
    JOIN sections sec ON s.section_id = sec.id 
    WHERE s.school_id = ? AND s.status = 'active' 
    ORDER BY std.display_order ASC, sec.name ASC, s.roll_number ASC
");
$stmtStus->execute([$school_id]);
$studentsList = $stmtStus->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">Student ID Card Generator</h2>
            <p class="text-secondary">Extract print-ready graphical identification badges for enrolled students.</p>
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
                        <th>Standard</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($studentsList)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No active students enrolled to print ID cards yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($studentsList as $stu): ?>
                            <tr>
                                <td><code>#<?= htmlspecialchars($stu['roll_number']) ?></code></td>
                                <td><code><?= htmlspecialchars($stu['student_id']) ?></code></td>
                                <td class="fw-bold"><?= htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']) ?></td>
                                <td><?= htmlspecialchars($stu['standard_name']) ?></td>
                                <td>
                                    <a href="id_cards.php?student_id=<?= $stu['id'] ?>" target="_blank" class="btn btn-indigo btn-sm rounded-pill"><i class="fa-solid fa-id-card me-1"></i>Generate Badge</a>
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
