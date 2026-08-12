<?php
// C:\xampp\htdocs\school-erp\student\fees.php

$activePage = 'fees';
require_once __DIR__ . '/../includes/student_auth.php';
require_once __DIR__ . '/../includes/header.php';

$student_id = $_SESSION['student_id'];
$school_id = $_SESSION['school_id'];
$db = getDBConnection();

// Fetch Student profile to map active year
$stmtStu = $db->prepare("SELECT academic_year_id FROM students WHERE id = ? AND school_id = ?");
$stmtStu->execute([$student_id, $school_id]);
$stu = $stmtStu->fetch();

$ledger = [];
$payments = [];

if ($stu) {
    // Fetch assigned student fees
    $stmtFee = $db->prepare("
        SELECT sf.*, fc.name as category_name 
        FROM student_fees sf 
        JOIN fee_categories fc ON sf.fee_category_id = fc.id 
        WHERE sf.student_id = ? AND sf.school_id = ? AND sf.academic_year_id = ? 
        ORDER BY sf.due_date ASC
    ");
    $stmtFee->execute([$student_id, $school_id, $stu['academic_year_id']]);
    $ledger = $stmtFee->fetchAll();
    
    // Fetch historical payment logs
    $stmtPay = $db->prepare("
        SELECT fp.*, fc.name as category_name 
        FROM fee_payments fp 
        JOIN student_fees sf ON fp.student_fee_id = sf.id 
        JOIN fee_categories fc ON sf.fee_category_id = fc.id 
        WHERE sf.student_id = ? AND sf.school_id = ?
        ORDER BY fp.payment_date DESC
    ");
    $stmtPay->execute([$student_id, $school_id]);
    $payments = $stmtPay->fetchAll();
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold">My Fees & Payments Ledger</h2>
            <p class="text-secondary">Track outstanding balances, due dates, and past payment logs.</p>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Fees Ledger List -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 glass-card h-100">
                <h5 class="fw-bold mb-4"><i class="fa-solid fa-receipt text-indigo me-2"></i>Outstanding Fees</h5>
                <div class="table-responsive">
                    <table class="table align-middle custom-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Total Fee</th>
                                <th>Paid</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ledger)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No fee records found for this academic cycle.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ledger as $row): ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($row['category_name']) ?></td>
                                        <td class="fw-bold">$<?= htmlspecialchars($row['amount']) ?></td>
                                        <td class="text-success fw-bold">$<?= htmlspecialchars($row['paid_amount']) ?></td>
                                        <td><?= htmlspecialchars($row['due_date']) ?></td>
                                        <td>
                                            <?php 
                                            $bg = $row['status'] === 'Paid' ? 'bg-success' : ($row['status'] === 'Partial' ? 'bg-info' : 'bg-warning');
                                            ?>
                                            <span class="badge <?= $bg ?>"><?= htmlspecialchars($row['status']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Payment logs history -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 glass-card h-100">
                <h5 class="fw-bold mb-4"><i class="fa-solid fa-history text-indigo me-2"></i>Recent Payments</h5>
                <div class="list-group list-group-flush">
                    <?php if (empty($payments)): ?>
                        <div class="text-center text-muted py-5">No payments recorded.</div>
                    <?php else: ?>
                        <?php foreach ($payments as $pay): ?>
                            <div class="list-group-item bg-transparent px-0 py-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold text-indigo" style="font-size: 14px;"><?= htmlspecialchars($pay['category_name']) ?></div>
                                    <small class="text-muted">Method: <?= htmlspecialchars($pay['payment_method']) ?> | Date: <?= htmlspecialchars($pay['payment_date']) ?></small>
                                    <?php if (!empty($pay['reference_no'])): ?>
                                        <small class="d-block text-secondary">Ref: <code><?= htmlspecialchars($pay['reference_no']) ?></code></small>
                                    <?php endif; ?>
                                </div>
                                <h5 class="fw-bold text-success mb-0">+$<?= htmlspecialchars($pay['amount_paid']) ?></h5>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
