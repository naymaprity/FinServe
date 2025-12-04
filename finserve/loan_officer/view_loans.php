<?php
session_start();
require '../config/db.php';
require '../includes/header.php';


// ✅ Fetch all loan requests with applicant info
$query = "
    SELECT 
        l.id, 
        u.full_name, 
        l.loan_type, 
        l.amount, 
        l.reason, 
        l.term_months, 
        l.status, 
        l.applied_at 
    FROM loans l
    JOIN customers u ON l.customer_id = u.id
    ORDER BY l.applied_at DESC
";
$result = $pdo->query($query);
?>

<style>
body {
    background: #f2f5f9;
    font-family: 'Poppins', sans-serif;
}
.container {
    max-width: 1200px;
    margin: 60px auto;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    padding: 40px 45px;
    transition: 0.3s ease;
}
.container:hover {
    transform: scale(1.01);
}

.header-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #0061ff, #60efff);
    padding: 18px 0;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0,97,255,0.3);
}

.header-bar h2 {
    color: white;
    font-size: 26px;
    font-weight: 700;
    letter-spacing: 0.8px;
    margin: 0;
}

.table {
    width: 100%;
    border-collapse: collapse;
    overflow: hidden;
    border-radius: 12px;
}
.table thead {
    background: #0d6efd;
    color: #fff;
}
.table th, .table td {
    text-align: center;
    padding: 14px;
    font-size: 15px;
    vertical-align: middle;
}
.table tbody tr {
    background: #fff;
    transition: 0.3s;
}
.table tbody tr:hover {
    background: #f1f7ff;
    box-shadow: inset 2px 0 0 #007bff;
}
.table td {
    color: #333;
    font-weight: 500;
}

.status-badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 25px;
    font-weight: 600;
    text-transform: capitalize;
    font-size: 13px;
}
.status-pending {
    background: #fff3cd;
    color: #856404;
}
.status-approved {
    background: #d4edda;
    color: #155724;
}
.status-rejected {
    background: #f8d7da;
    color: #721c24;
}

.btn-group {
    display: flex;
    gap: 8px; /* spacing between buttons */
    justify-content: center;
}

.btn {
    border-radius: 10px;
    padding: 6px 14px;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.25s ease;
}
.btn-success {
    background: linear-gradient(135deg, #198754, #28a745);
    border: none;
}
.btn-success:hover {
    background: linear-gradient(135deg, #157347, #1e9b5a);
    transform: scale(1.05);
}
.btn-danger {
    background: linear-gradient(135deg, #dc3545, #ff4d5a);
    border: none;
}
.btn-danger:hover {
    background: linear-gradient(135deg, #bb2d3b, #ff1a2e);
    transform: scale(1.05);
}

.no-data {
    text-align: center;
    color: #777;
    font-style: italic;
    padding: 30px;
}
.footer-text {
    text-align: center;
    margin-top: 20px;
    color: #666;
    font-size: 13px;
}
</style>

<div class="container">
    <div class="header-bar">
        <h2><i class="fas fa-hand-holding-usd me-2"></i>All Loan Requests</h2>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Applicant</th>
                    <th>Loan Type</th>
                    <th>Amount (৳)</th>
                    <th>Reason</th>
                    <th>Term (Months)</th>
                    <th>Status</th>
                    <th>Applied On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->rowCount() > 0): ?>
                <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['loan_type']) ?></td>
                        <td><b><?= number_format($row['amount'], 2) ?></b></td>
                        <td><?= htmlspecialchars($row['reason']) ?></td>
                        <td><?= htmlspecialchars($row['term_months']) ?></td>
                        <td>
                            <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>
                        <td><?= date('d M Y, h:i A', strtotime($row['applied_at'])) ?></td>
                        <td>
    <?php if ($row['status'] == 'pending'): ?>
        <div class="btn-group">
            <a href="approve_reject_loan.php?id=<?= $row['id'] ?>&action=approve" class="btn btn-success btn-sm">
                <i class="fas fa-check-circle"></i> Approve
            </a>
            <a href="approve_reject_loan.php?id=<?= $row['id'] ?>&action=reject" class="btn btn-danger btn-sm">
                <i class="fas fa-times-circle"></i> Reject
            </a>
        </div>
    <?php else: ?>
        <span class="text-muted">—</span>
    <?php endif; ?>
</td>

                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8" class="no-data">No loan requests found</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="footer-text">
        <i class="fas fa-university me-1"></i> FinServe Loan Management System © <?= date('Y') ?>
    </div>
</div>


