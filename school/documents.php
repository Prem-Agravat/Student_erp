<?php
// C:\xampp\htdocs\school-erp\school\documents.php

$activePage = 'documents';
require_once __DIR__ . '/../includes/school_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$school_id = $_SESSION['school_id'];
$message = '';

// Fetch active students list for dropdown selection
$stmtStus = $db->prepare("SELECT id, first_name, last_name, student_id FROM students WHERE school_id = ? AND status = 'active' ORDER BY first_name ASC");
$stmtStus->execute([$school_id]);
$students = $stmtStus->fetchAll();

// Handle File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['doc_file'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF validation failed.");
    }
    
    $student_id = intval($_POST['student_id'] ?? 0);
    $doc_name = sanitizeInput($_POST['doc_name'] ?? '');
    
    if ($student_id <= 0 || empty($doc_name)) {
        $message = getAlert('danger', "Student selection and Document Name are required.");
    } else {
        $fileTmpPath = $_FILES['doc_file']['tmp_name'];
        $fileName = $_FILES['doc_file']['name'];
        $fileSize = $_FILES['doc_file']['size'];
        $fileType = $_FILES['doc_file']['type'];
        
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            if ($fileSize < 5 * 1024 * 1024) { // 5MB Limit
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $uploadFileDir = __DIR__ . '/../assets/uploads/documents/';
                
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0777, true);
                }
                
                if (move_uploaded_file($fileTmpPath, $uploadFileDir . $newFileName)) {
                    try {
                        $stmt = $db->prepare("INSERT INTO documents (school_id, student_id, name, file_path, file_type, file_size) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$school_id, $student_id, $doc_name, $newFileName, $fileType, $fileSize]);
                        
                        logActivity("Upload Document", "Uploaded document: $doc_name for student ID: $student_id");
                        $message = getAlert('success', "Document '$doc_name' uploaded successfully.");
                    } catch (PDOException $e) {
                        $message = getAlert('danger', "Failed to save record: " . $e->getMessage());
                    }
                } else {
                    $message = getAlert('danger', "Failed to move file to upload directory.");
                }
            } else {
                $message = getAlert('danger', "File size exceeds 5MB limit.");
            }
        } else {
            $message = getAlert('danger', "File type not allowed. Allowed types: PDF, JPG, JPEG, PNG, DOC, DOCX");
        }
    }
}

// Fetch all uploaded documents
$stmtDocs = $db->prepare("
    SELECT d.*, s.first_name, s.last_name, s.student_id 
    FROM documents d 
    JOIN students s ON d.student_id = s.id 
    WHERE d.school_id = ? 
    ORDER BY d.id DESC
");
$stmtDocs->execute([$school_id]);
$documents = $stmtDocs->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0">Student Documents Manager</h2>
                <p class="text-secondary mb-0">Upload certificate copies, transcripts, ID cards, and record transcripts.</p>
            </div>
            <button class="btn btn-indigo rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#uploadDocModal"><i class="fa-solid fa-cloud-arrow-up me-2"></i>Upload Document</button>
        </div>
    </div>
    
    <?= $message ?>
    
    <div class="card border-0 shadow-sm p-4 glass-card">
        <div class="table-responsive">
            <table class="table align-middle custom-table">
                <thead>
                    <tr>
                        <th>Date Uploaded</th>
                        <th>Student Name</th>
                        <th>Document Title</th>
                        <th>Size / Format</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documents)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No documents uploaded yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td><?= date('Y-m-d H:i', strtotime($doc['uploaded_at'])) ?></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']) ?></div>
                                    <small class="text-muted">ID: <?= htmlspecialchars($doc['student_id']) ?></small>
                                </td>
                                <td><span class="fw-bold text-dark"><?= htmlspecialchars($doc['name']) ?></span></td>
                                <td>
                                    <code><?= round($doc['file_size'] / 1024, 1) ?> KB</code>
                                    <span class="badge bg-secondary ms-1"><?= strtoupper(substr($doc['file_type'], strpos($doc['file_type'], '/') + 1)) ?></span>
                                </td>
                                <td>
                                    <a href="<?= UPLOAD_URL ?>documents/<?= $doc['file_path'] ?>" target="_blank" class="btn btn-light btn-sm rounded-pill px-3 text-indigo"><i class="fa-solid fa-download me-1"></i>Download</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadDocModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data">
            <?= getCSRFInput() ?>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Upload Student Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-semibold">Select Student <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Choose Student</option>
                            <?php foreach ($students as $stu): ?>
                                <option value="<?= $stu['id'] ?>"><?= htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']) ?> (<?= htmlspecialchars($stu['student_id']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Document Title / Name <span class="text-danger">*</span></label>
                        <input type="text" name="doc_name" class="form-control" required placeholder="e.g. Birth Certificate, Transfer Circular">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Choose File <span class="text-danger">*</span></label>
                        <input type="file" name="doc_file" class="form-control" required accept=".pdf, .jpg, .jpeg, .png, .doc, .docx">
                        <small class="text-muted">Allowed types: PDF, JPG, JPEG, PNG, DOC, DOCX (max 5MB)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-indigo">Upload File</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
