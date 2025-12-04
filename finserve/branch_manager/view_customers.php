<?php
session_start();
include('../config/db.php');

// শুধুমাত্র branch_manager প্রবেশ করতে পারবে
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'branch_manager') {
    header('Location: ../login.php');
    exit();
}

$result = mysqli_query($conn, "
    SELECT u.full_name, u.email, c.account_number, c.account_type, c.balance, c.status, c.opened_at
    FROM customers c
    JOIN users u ON c.id = u.id
    ORDER BY c.opened_at DESC
");
?>

<?php include('../includes/header.php'); ?>

<div class="container mt-5">
    <h2>👥 All Customers</h2>
    <p>Branch Manager: <?php echo $_SESSION['full_name']; ?></p>
    <hr>

    <table class="table table-bordered table-hover mt-3">
        <thead class="table-dark">
            <tr>
                <th>Customer Name</th>
                <th>Email</th>
                <th>Account Number</th>
                <th>Type</th>
                <th>Balance (৳)</th>
                <th>Status</th>
                <th>Opened At</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo $row['account_number']; ?></td>
                    <td><?php echo ucfirst($row['account_type']); ?></td>
                    <td><?php echo number_format($row['balance'], 2); ?></td>
                    <td>
                        <?php if ($row['status'] == 'active') { ?>
                            <span class="badge bg-success">Active</span>
                        <?php } else { ?>
                            <span class="badge bg-danger">Inactive</span>
                        <?php } ?>
                    </td>
                    <td><?php echo date("d M Y, h:i A", strtotime($row['opened_at'])); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <a href="branch_manager_dashboard.php" class="btn btn-secondary mt-3">⬅ Back to Dashboard</a>
</div>

<?php include('../includes/footer.php'); ?>
