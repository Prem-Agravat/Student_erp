<?php
// C:\xampp\htdocs\school-erp\school\fees.php

$activePage = 'fees';
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
    echo '<p class="mb-3">Please configure and activate an Academic Year before managing Fees.</p>';
    echo '<a href="academic_years.php" class="btn btn-indigo rounded-pill px-4">Manage Academic Years</a>';
    echo '</div>';
    echo '</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$active_year_id = $activeYear['id'];

// Fetch Standards for bulk fees assign
$standards = $db->query("SELECT id, name FROM standards WHERE school_id = $school_id AND status = 'active' ORDER BY display_order ASC")->fetchAll();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("CSRF validation failed.");
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_category') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        
        if (empty($name)) {
            $message = getAlert('danger', "Category Name is required.");
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO fee_categories (school_id, name, description, status) VALUES (?, ?, ?, 'active')");
                $stmt->execute([$school_id, $name, $description]);
                logActivity("Create Fee Category", "Created fee category: $name");
                $message = getAlert('success', "Fee category '$name' created successfully.");
            } catch (PDOException $e) {
                $message = getAlert('danger', "Failed to create category: " . $e->getMessage());
            }
        }
    } elseif ($action === 'assign_bulk') {
        $standard_id = intval($_POST['standard_id'] ?? 0);
        $category_id = intval($_POST['fee_category_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $due_date = sanitizeInput($_POST['due_date'] ?? '');
        
        if ($standard_id <= 0 || $category_id <= 0 || $amount <= 0 || empty($due_date)) {
            $message = getAlert('danger', "All fields are required to assign fees.");
        } else {
            try {
                // Fetch all students in this standard
                $stmtStus = $db->prepare("SELECT id FROM students WHERE school_id = ? AND standard_id = ? AND status = 'active'");
                $stmtStus->execute([$school_id, $standard_id]);
                $stus = $stmtStus->fetchAll();
                
                if (empty($stus)) {
                    $message = getAlert('warning', "No active students enrolled in this standard to assign fees.");
                } else {
                    $db->beginTransaction();
                    $stmtIns = $db->prepare("INSERT INTO student_fees (school_id, student_id, academic_year_id, fee_category_id, amount, due_date, status, paid_amount) VALUES (?, ?, ?, ?, ?, ?, 'Pending', 0.00)");
                    
                    foreach ($stus as $stu) {
                        $stmtIns->execute([$school_id, $stu['id'], $active_year_id, $category_id, $amount, $due_date]);
                    }
                    $db->commit();
                    logActivity("Assign Bulk Fees", "Assigned Fee Category ID: $category_id to Standard ID: $standard_id");
                    $message = getAlert('success', "Fees assigned successfully to " . count($stus) . " students.");
                }
            } catch (PDOException $e) {
                $db->rollBack();
                $message = getAlert('danger', "Failed to assign bulk fees: " . $e->getMessage());
            }
        }
    } elseif ($action === 'collect_payment') {
        $student_fee_id = intval($_POST['student_fee_id'] ?? 0);
        $amount_paid = floatval($_POST['amount_paid'] ?? 0);
        $payment_method = sanitizeInput($_POST['payment_method'] ?? 'Cash');
        $reference_no = sanitizeInput($_POST['reference_no'] ?? '');
        $remarks = sanitizeInput($_POST['remarks'] ?? '');
        
        if ($student_fee_id <= 0 || $amount_paid <= 0) {
            $message = getAlert('danger', "Payment fee ID and amount paid are required.");
        } else {
            try {
                // Retrieve fee details
                $stmtFee = $db->prepare("SELECT * FROM student_fees WHERE id = ? AND school_id = ?");
                $stmtFee->execute([$student_fee_id, $school_id]);
                $fee = $stmtFee->fetch();
                
                if ($fee) {
                    $newPaidAmount = floatval($fee['paid_amount']) + $amount_paid;
                    $status = 'Pending';
                    if ($newPaidAmount >= floatval($fee['amount'])) {
                        $status = 'Paid';
                    } elseif ($newPaidAmount > 0) {
                        $status = 'Partial';
                    }
                    
                    $db->beginTransaction();
                    
                    // Insert payment log
                    $stmtPay = $db->prepare("INSERT INTO fee_payments (school_id, student_fee_id, amount_paid, payment_date, payment_method, reference_no, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmtPay->execute([$school_id, $student_fee_id, $amount_paid, date('Y-m-d'), $payment_method, $reference_no, $remarks]);
                    
                    // Update fee ledger status
                    $stmtUpFee = $db->prepare("UPDATE student_fees SET paid_amount = ?, status = ? WHERE id = ?");
                    $stmtUpFee->execute([$newPaidAmount, $status, $student_fee_id]);
                    
                    $db->commit();
                    logActivity("Collect Fee Payment", "Collected payment of $amount_paid for student fee ID: $student_fee_id");
                    $message = getAlert('success', "Payment of $$amount_paid recorded successfully.");
                }
            } catch (PDOException $e) {
                $db->rollBack();
                $message = getAlert('danger', "Failed to record payment: " . $e->getMessage());
            }
        }
    }
}

// Fetch categories
$feeCategories = $db->query("SELECT * FROM fee_categories WHERE school_id = $school_id ORDER BY id DESC")->fetchAll();

// Fetch student fees ledger
$stmtLedger = $db->prepare("
    SELECT sf.*, s.first_name, s.last_name, s.student_id, std.name as standard_name, fc.name as category_name 
    FROM student_fees sf 
    JOIN students s ON sf.student_id = s.id 
    JOIN standards std ON s.standard_id = std.id 
    JOIN fee_categories fc ON sf.fee_category_id = fc.id 
    WHERE sf.school_id = ? AND sf.academic_year_id = ? 
    ORDER BY sf.id DESC LIMIT 100
");
$stmtLedger->execute([$school_id, $active_year_id]);
$ledger = $stmtLedger->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-0">Finance & Fee Ledger</h2>
                <p class="text-secondary mb-0">Academic Year: <span class="badge bg-indigo"><?= htmlspecialchars($activeYear['name']) ?></span></p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-indigo rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createCategoryModal"><i class="fa-solid fa-plus me-2"></i>Create Category</button>
                <button class="btn btn-indigo rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#assignBulkModal"><i class="fa-solid fa-coins me-2"></i>Bulk Assign Fees</button>
            </div>
        </div>
    </div>
    
    <?= $message ?>
    
    <div class="card border-0 shadow-sm p-4 glass-card">
        <ul class="nav nav-tabs mb-4" id="feesTab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active fw-bold" id="ledger-tab" data-bs-toggle="tab" data-bs-target="#ledger">Fee Ledgers</button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-bold" id="cats-tab" data-bs-toggle="tab" data-bs-target="#cats">Fee Categories</button>
            </li>
        </ul>
        
        <div class="tab-content" id="feesTabContent">
            <!-- Ledger list tab -->
            <div class="tab-pane fade show active" id="ledger">
                <div class="table-responsive">
                    <table class="table align-middle custom-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Category</th>
                                <th>Total Fee</th>
                                <th>Paid Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ledger)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No fees have been assigned or logged yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ledger as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></div>
                                            <small class="text-muted">ID: <?= htmlspecialchars($row['student_id']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($row['standard_name']) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($row['category_name']) ?></span></td>
                                        <td class="fw-bold">$<?= htmlspecialchars($row['amount']) ?></td>
                                        <td class="text-success fw-bold">$<?= htmlspecialchars($row['paid_amount']) ?></td>
                                        <td><?= htmlspecialchars($row['due_date']) ?></td>
                                        <td>
                                            <?php 
                                            $bg = $row['status'] === 'Paid' ? 'bg-success' : ($row['status'] === 'Partial' ? 'bg-info' : 'bg-warning');
                                            ?>
                                            <span class="badge <?= $bg ?>"><?= htmlspecialchars($row['status']) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($row['status'] !== 'Paid'): ?>
                                                <button class="btn btn-indigo btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#payModal<?= $row['id'] ?>"><i class="fa-solid fa-file-invoice-dollar me-1"></i>Collect</button>
                                                
                                                <!-- Collect Payment Modal -->
                                                <div class="modal fade" id="payModal<?= $row['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <form method="POST">
                                                            <?= getCSRFInput() ?>
                                                            <input type="hidden" name="action" value="collect_payment">
                                                            <input type="hidden" name="student_fee_id" value="<?= $row['id'] ?>">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title fw-bold">Collect Fee Payment</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body text-start">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Student</label>
                                                                        <input type="text" class="form-control" disabled value="<?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?> (<?= htmlspecialchars($row['student_id']) ?>)">
                                                                    </div>
                                                                    <div class="row mb-3">
                                                                        <div class="col-6">
                                                                            <label class="form-label">Pending Amount</label>
                                                                            <input type="text" class="form-control" disabled value="$<?= floatval($row['amount']) - floatval($row['paid_amount']) ?>">
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <label class="form-label">Amount Paid Now <span class="text-danger">*</span></label>
                                                                            <input type="number" name="amount_paid" class="form-control" min="1" step="0.01" max="<?= floatval($row['amount']) - floatval($row['paid_amount']) ?>" required>
                                                                        </div>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Payment Method</label>
                                                                        <select name="payment_method" class="form-select">
                                                                            <option value="Cash">Cash</option>
                                                                            <option value="Cheque">Cheque</option>
                                                                            <option value="Bank Transfer">Bank Transfer</option>
                                                                            <option value="Online">Online Gateway Transfer</option>
                                                                            <option value="Other">Other Mode</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Ref Number / Cheque #</label>
                                                                        <input type="text" name="reference_no" class="form-control" placeholder="TXN12345678">
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Collector Remarks</label>
                                                                        <input type="text" name="remarks" class="form-control" placeholder="Payment received.">
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-indigo">Record Payment</button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted"><i class="fa-solid fa-lock me-1"></i>Settled</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Categories tab -->
            <div class="tab-pane fade" id="cats">
                <div class="table-responsive">
                    <table class="table align-middle custom-table">
                        <thead>
                            <tr>
                                <th>Category Name</th>
                                <th>Description</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($feeCategories)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">No fee categories created yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($feeCategories as $cat): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($cat['name']) ?></td>
                                        <td><?= htmlspecialchars($cat['description'] ?: '—') ?></td>
                                        <td><span class="badge bg-success"><?= ucfirst($cat['status']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Category Modal -->
<div class="modal fade" id="createCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <?= getCSRFInput() ?>
            <input type="hidden" name="action" value="create_category">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Create Fee Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Tuition Fee, Exam Fee, Transport Fee">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Category coverage explanation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-indigo">Save Category</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Assign Bulk Modal -->
<div class="modal fade" id="assignBulkModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <?= getCSRFInput() ?>
            <input type="hidden" name="action" value="assign_bulk">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Assign Fees in Bulk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-semibold">Target Standard <span class="text-danger">*</span></label>
                        <select name="standard_id" class="form-select" required>
                            <option value="">Select Standard</option>
                            <?php foreach ($standards as $std): ?>
                                <option value="<?= $std['id'] ?>"><?= htmlspecialchars($std['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Fee Category <span class="text-danger">*</span></label>
                        <select name="fee_category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php foreach ($feeCategories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label font-semibold">Fee Amount <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" min="1" step="0.01" required placeholder="e.g. 500">
                        </div>
                        <div class="col-6">
                            <label class="form-label font-semibold">Due Date <span class="text-danger">*</span></label>
                            <input type="date" name="due_date" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-indigo">Assign Fees</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
