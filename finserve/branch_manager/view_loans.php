<?php
session_start();
include('../config/db.php');

// শুধুমাত্র branch_manager প্রবেশ করতে পারবে
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'branch_manager') {
    header('Location: ../login.php');
    exit();
}

$loans = mysqli_query($conn, "
    SELECT l.id, u.full_name, l.loan_type, l.amount, l.term_months, l.status, l.applied_at, lo.full_name AS officer_name
    FROM loans l
    JOIN customers c ON l.customer_id = c.id
    JOIN users u ON c.id = u.id
    LEFT JOIN users lo ON l.officer_id = lo.id
    ORDER BY l.applied_at DESC
");
?>

<?php include('../includes/header.php'); ?>

<div class="container mt-5">
    <h2>💼 Loan Applications Overview</h2>
    <p>Branch Manager: <?php echo $_SESSION['full_name']; ?></p>
    <hr>

    <table class="table table-striped table-hover mt-3">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Loan Type</th>
                <th>Amount (৳)</th>
                <th>Term (Months)</th>
                <th>Status</th>
                <th>Officer</th>
                <th>Applied At</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($loan = mysqli_fetch_assoc($loans)) { ?>
                <tr>
                    <td><?php echo $loan['id']; ?></td>
                    <td><?php echo htmlspecialchars($loan['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($loan['loan_type']); ?></td>
                    <td><?php echo number_format($loan['amount'], 2); ?></td>
                    <td><?php echo $loan['term_months']; ?></td>
                    <td>
                        <?php if ($loan['status'] == 'approved') { ?>
                            <span class="badge bg-success">Approved</span>
                        <?php } elseif ($loan['status'] == 'rejected') { ?>
                            <span class="badge bg-danger">Rejected</span>
                        <?php } else { ?>
                            <span class="badge bg-warning text-dark">Pending</span>
                        <?php } ?>
                    </td>
                    <td><?php echo $loan['officer_name'] ? htmlspecialchars($loan['officer_name']) : 'N/A'; ?></td>
                    <td><?php echo date("d M Y, h:i A", strtotime($loan['applied_at'])); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <a href="branch_manager_dashboard.php" class="btn btn-secondary mt-3">⬅ Back to Dashboard</a>
</div>

<?php include('../includes/footer.php'); ?>
