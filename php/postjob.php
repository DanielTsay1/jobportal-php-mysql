<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Error: Invalid request method.");
}

// Retrieve and sanitize form data
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$salary = $_POST['salary'] ?? '';
$compid = $_POST['compid'] ?? '';

// Debug: Log the received data
echo "Received data: Title = $title, Description = $description, Salary = $salary, CompID = $compid\n";

// Validate required fields
if (empty($title) || empty($description) || empty($salary) || empty($compid)) {
    die("Error: Missing required fields. Please fill out all fields.");
}

// Validate salary and compid
if (!is_numeric($salary)) {
    die("Error: Salary must be a numeric value.");
}

if (!ctype_digit($compid)) {
    die("Error: CompID must be an integer.");
}

// Convert compid to integer
$compid = (int)$compid;

// Debug: Log the validated data
echo "Validated data: Title = $title, Description = $description, Salary = $salary, CompID = $compid\n";

// Check if the compid exists in the company table
$checkCompidQuery = $conn->prepare("SELECT COUNT(*) FROM company WHERE compid = ?");
$checkCompidQuery->bind_param('i', $compid);
$checkCompidQuery->execute();
$checkCompidQuery->bind_result($compidExists);
$checkCompidQuery->fetch();
$checkCompidQuery->close();

if ($compidExists === 0) {
    die("Error: The provided CompID does not exist in the company table.");
}

// Insert the job post
$insertQuery = $conn->prepare("INSERT INTO `job-post` (title, description, salary, compid) VALUES (?, ?, ?, ?)");
if (!$insertQuery) {
    die("Error: Prepare failed: " . $conn->error);
}

$insertQuery->bind_param('ssdi', $title, $description, $salary, $compid);

if ($insertQuery->execute()) {
    echo "Job post successfully added.";
} else {
    die("Error: " . $insertQuery->error);
}

$insertQuery->close();
$conn->close();