<?php
session_start();
require_once '../php/db.php';

// Ensure recruiter is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'A' || !isset($_SESSION['compid'])) {
    header('Location: /main/login.html');
    exit;
}

$compid = $_SESSION['compid'];

// Check if company is suspended
$suspension_check = $conn->prepare("SELECT suspended, suspension_reason FROM company WHERE compid = ?");
$suspension_check->bind_param("i", $compid);
$suspension_check->execute();
$suspension_result = $suspension_check->get_result();
$company_status = $suspension_result->fetch_assoc();
$suspension_check->close();

if (!empty($company_status['suspended']) && $company_status['suspended'] == 1) {
    // Company is suspended, redirect with error
    header("Location: /main/post-job.php?error=suspended&reason=" . urlencode($company_status['suspension_reason'] ?? 'No reason provided.'));
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and retrieve form data
    $designation = trim($_POST['designation'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $salary = filter_input(INPUT_POST, 'salary', FILTER_VALIDATE_FLOAT);
    $description = trim($_POST['description'] ?? '');
    $spots = filter_input(INPUT_POST, 'spots', FILTER_VALIDATE_INT);
    $questions = $_POST['questions'] ?? [];

    // Basic validation
    if (empty($designation) || empty($location) || $salary === false || empty($description) || $spots === false || $spots < 1) {
        die("Please fill all required fields correctly.");
    }
    
    // Process questions: remove empty ones
    $questions_filtered = array_filter($questions, function($q) {
        return !empty(trim($q));
    });
    $questions_json = !empty($questions_filtered) ? json_encode(array_values($questions_filtered)) : null;

    $recid = $_SESSION['recid']; // Get recid from session

    // Prepare SQL to prevent SQL injection
    $sql = "INSERT INTO `job-post` (recid, compid, designation, location, salary, description, spots, questions, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissdsis", $recid, $compid, $designation, $location, $salary, $description, $spots, $questions_json);
    
    // Execute and redirect
    if ($stmt->execute()) {
        header("Location: /main/manage-jobs.php?status=success");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
} else {
    // Redirect if not a POST request
    header("Location: /main/post-job.php");
    exit();
}
?>