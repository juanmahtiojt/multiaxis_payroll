<?php
include "config.php";
include "functions.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    echo "<div class='alert alert-danger'>You must be logged in to view this page.</div>";
    exit();
}

// Fetch activity logs
$result = $conn->query("SELECT * FROM activity_logs ORDER BY timestamp DESC");
?>

<div class="table-responsive">
    <table class="table table-striped table-bordered table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th scope="col">Username</th>
                <th scope="col">Activity</th>
                <th scope="col">Page</th>
                <th scope="col">IP Address</th>
                <th scope="col">Timestamp</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['activity']) ?></td>
                        <td><?= htmlspecialchars($row['page']) ?></td>
                        <td><?= htmlspecialchars($row['ip_address']) ?></td>
                        <td><?= date("F j, Y g:i A", strtotime($row['timestamp'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center text-muted">No activity logs found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>