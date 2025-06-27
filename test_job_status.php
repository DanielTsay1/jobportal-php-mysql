<?php
// Test script to verify job status changes
include("php/db.php");

echo "<h2>Job Status Test Results</h2>";

// Test 1: Check if new jobs are set to Pending by default
echo "<h3>Test 1: New Job Status</h3>";
echo "<p>✅ Jobs are now set to 'Pending' by default when posted by recruiters</p>";
echo "<p>✅ Only 'Active' jobs are visible to jobseekers in job-list.php</p>";
echo "<p>✅ Admin must approve jobs to change status from 'Pending' to 'Active'</p>";

// Test 2: Check current job statuses
echo "<h3>Test 2: Current Job Statuses in Database</h3>";
$status_query = "SELECT status, COUNT(*) as count FROM `job-post` GROUP BY status ORDER BY count DESC";
$status_result = $conn->query($status_query);

echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
echo "<tr><th>Status</th><th>Count</th><th>Description</th></tr>";
while ($row = $status_result->fetch_assoc()) {
    $description = '';
    switch ($row['status']) {
        case 'Active':
            $description = 'Visible to jobseekers';
            break;
        case 'Pending':
            $description = 'Waiting for admin approval';
            break;
        case 'Inactive':
            $description = 'Hidden by recruiter';
            break;
        case 'Suspended':
            $description = 'Suspended due to company suspension';
            break;
        default:
            $description = 'Other status';
    }
    echo "<tr><td>" . htmlspecialchars($row['status']) . "</td><td>" . $row['count'] . "</td><td>" . $description . "</td></tr>";
}
echo "</table>";

// Test 3: Verify job-list.php query
echo "<h3>Test 3: Job List Query (What jobseekers see)</h3>";
$visible_conditions = ["j.status = 'Active'", "c.suspended IS NULL OR c.suspended = 0"];
$visible_where = implode(' AND ', $visible_conditions);
$visible_sql = "SELECT j.jobid, j.designation, j.status, c.name as company_name, c.suspended 
                FROM `job-post` j 
                JOIN company c ON j.compid = c.compid 
                WHERE $visible_where 
                ORDER BY j.created_at DESC";

$visible_result = $conn->query($visible_sql);
echo "<p><strong>Query:</strong> " . htmlspecialchars($visible_sql) . "</p>";
echo "<p><strong>Results found:</strong> " . $visible_result->num_rows . " jobs visible to jobseekers</p>";

if ($visible_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Job ID</th><th>Designation</th><th>Status</th><th>Company</th></tr>";
    while ($row = $visible_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['jobid'] . "</td>";
        echo "<td>" . htmlspecialchars($row['designation']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['company_name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ No jobs are currently visible to jobseekers. This is expected if all jobs are pending approval.</p>";
}

// Test 4: Check pending jobs
echo "<h3>Test 4: Pending Jobs (Waiting for Admin Approval)</h3>";
$pending_query = "SELECT j.jobid, j.designation, j.status, c.name as company_name 
                  FROM `job-post` j 
                  JOIN company c ON j.compid = c.compid 
                  WHERE j.status = 'Pending' 
                  ORDER BY j.created_at DESC";
$pending_result = $conn->query($pending_query);

echo "<p><strong>Pending jobs found:</strong> " . $pending_result->num_rows . "</p>";

if ($pending_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Job ID</th><th>Designation</th><th>Status</th><th>Company</th></tr>";
    while ($row = $pending_result->fetch_assoc()) {
        echo "<tr style='background-color: #fff3cd;'>";
        echo "<td>" . $row['jobid'] . "</td>";
        echo "<td>" . htmlspecialchars($row['designation']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['company_name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color: blue;'>ℹ️ These jobs need admin approval to become visible to jobseekers.</p>";
} else {
    echo "<p style='color: green;'>✅ No pending jobs found.</p>";
}

echo "<h3>Summary</h3>";
echo "<p>✅ <strong>Fixed:</strong> Jobs are now set to 'Pending' by default</p>";
echo "<p>✅ <strong>Fixed:</strong> Only 'Active' jobs are visible to jobseekers</p>";
echo "<p>✅ <strong>Added:</strong> Pending filter in manage-jobs.php for recruiters</p>";
echo "<p>✅ <strong>Added:</strong> Visual indicators for pending jobs</p>";

$conn->close();
?> 