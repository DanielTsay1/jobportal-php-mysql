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
    header('Location: ../main/recruiter-jobpost.html?error=Missing+required+fields');
    exit;
}

// Validate salary and compid
if (!is_numeric($salary)) {
    header('Location: ../main/recruiter-jobpost.html?error=Salary+must+be+numeric');
    exit;
}

if (!ctype_digit($compid)) {
    header('Location: ../main/recruiter-jobpost.html?error=CompID+must+be+an+integer');
    exit;
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
    header('Location: ../main/recruiter-jobpost.html?error=Company+ID+not+registered');
    exit;
}

// Insert the job post
$insertQuery = $conn->prepare("INSERT INTO `job-post` (title, description, salary, compid) VALUES (?, ?, ?, ?)");
if (!$insertQuery) {
    header('Location: ../main/recruiter-jobpost.html?error=Server+error');
    exit;
}

$insertQuery->bind_param('ssdi', $title, $description, $salary, $compid);

if ($insertQuery->execute()) {
    header('Location: ../main/recruiter-jobpost.html?success=Job+post+successfully+added');
} else {
    header('Location: ../main/recruiter-jobpost.html?error=Failed+to+add+job+post');
}

$insertQuery->close();
$conn->close();