<?php
// C:\xampp\htdocs\school-erp\student\documents.php

$activePage = 'documents';
require_once __DIR__ . '/../includes/student_auth.php';
require_once __DIR__ . '/../includes/header.php';

$student_id = $_SESSION['student_id'];
$school_id = $_SESSION['school_id'];
$db = getDBConnection();

// Fetch student documents
$stmtDocs = $db->prepare("SELECT * FROM documents WHERE student_id = ? AND school_id = ? ORDER BY id DESC");
$stmtDocs->execute([$student_id, $school_id]);
$documents = $stmtDocs->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">My Personal Documents</h2>
            <p class="text-secondary">View and download your official certificates uploaded by school administrators.</p>
        </div>
    </div>
    
    <div class="card border-0 shadow-sm p-4 glass-card">
        <div class="table-responsive">
            <table class="table align-middle custom-table">
                <thead>
                    <tr>
                        <th>Date Uploaded</th>
                        <th>Document Title</th>
                        <th>File Format</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documents)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No documents uploaded for your profile yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td><?= date('Y-m-d H:i', strtotime($doc['uploaded_at'])) ?></td>
                                <td><span class="fw-bold text-dark"><?= htmlspecialchars($doc['name']) ?></span></td>
                                <td><span class="badge bg-secondary"><?= strtoupper(substr($doc['file_type'], strpos($doc['file_type'], '/') + 1)) ?></span></td>
                                <td>
                                    <a href="<?= UPLOAD_URL ?>documents/<?= $doc['file_path'] ?>" target="_blank" class="btn btn-indigo btn-sm rounded-pill px-3"><i class="fa-solid fa-download me-1"></i>Download</a>
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
