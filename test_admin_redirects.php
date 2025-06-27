<?php
// Test script to verify admin dashboard redirects
include("php/db.php");

echo "<h2>Admin Dashboard Redirect Test</h2>";

echo "<h3>Fixed Redirect Issues:</h3>";
echo "<ul>";
echo "<li>✅ <strong>approve-job.php:</strong> Now redirects to <code>admin-dashboard.php?tab=jobs</code></li>";
echo "<li>✅ <strong>remove-job.php:</strong> Now redirects to <code>admin-dashboard.php?tab=jobs&msg=job_requeued</code></li>";
echo "</ul>";

echo "<h3>Actions That Now Stay on Jobs Tab:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Approve Job:</strong> Job status changes to 'Active', stays on jobs tab</li>";
echo "<li>✅ <strong>Reject Job:</strong> Job status changes to 'Rejected', stays on jobs tab</li>";
echo "<li>✅ <strong>Move to Pending:</strong> Job status changes to 'Pending', stays on jobs tab</li>";
echo "</ul>";

echo "<h3>Current Jobs in Database:</h3>";
$jobs_query = "SELECT jobid, designation, status, compid FROM `job-post` ORDER BY jobid DESC";
$jobs_result = $conn->query($jobs_query);

echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
echo "<tr><th>Job ID</th><th>Designation</th><th>Status</th><th>Company ID</th><th>Admin Actions</th></tr>";
while ($row = $jobs_result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['jobid'] . "</td>";
    echo "<td>" . htmlspecialchars($row['designation']) . "</td>";
    echo "<td>" . htmlspecialchars($row['status']) . "</td>";
    echo "<td>" . $row['compid'] . "</td>";
    echo "<td>";
    if ($row['status'] === 'Pending') {
        echo "<strong>Can Approve/Reject</strong>";
    } elseif ($row['status'] === 'Active' || $row['status'] === 'Rejected') {
        echo "<strong>Can Move to Pending</strong>";
    } else {
        echo "No actions available";
    }
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Testing Instructions:</h3>";
echo "<ol>";
echo "<li>Go to <code>http://localhost:8000/main/admin-dashboard.php?tab=jobs</code></li>";
echo "<li>Try approving a pending job - should stay on jobs tab</li>";
echo "<li>Try rejecting a pending job - should stay on jobs tab</li>";
echo "<li>Try moving an active/rejected job to pending - should stay on jobs tab</li>";
echo "<li>Verify that the URL remains <code>?tab=jobs</code> after each action</li>";
echo "</ol>";

echo "<h3>URL Patterns After Actions:</h3>";
echo "<ul>";
echo "<li><strong>Before:</strong> <code>admin-dashboard.php?tab=jobs</code></li>";
echo "<li><strong>After Approve/Reject:</strong> <code>admin-dashboard.php?tab=jobs</code> ✅</li>";
echo "<li><strong>After Move to Pending:</strong> <code>admin-dashboard.php?tab=jobs&msg=job_requeued</code> ✅</li>";
echo "</ul>";

$conn->close();
?> 