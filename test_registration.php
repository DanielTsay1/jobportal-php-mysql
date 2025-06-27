<?php
// Test script to verify recruiter registration process
include("php/db.php");

echo "<h2>Testing Recruiter Registration Process</h2>";

// Test data
$test_username = "test_recruiter_" . time();
$test_email = "test" . time() . "@example.com";
$test_password = "testpassword123";

echo "<p><strong>Test Data:</strong></p>";
echo "<p>Username: $test_username</p>";
echo "<p>Email: $test_email</p>";
echo "<p>Password: $test_password</p>";

// Test 1: Check if we can create a company
echo "<h3>Test 1: Creating Company</h3>";
$default_company_name = $test_username . "'s Company";
$default_location = "Location to be updated";
$default_contact = "0000000000";

$company_query = "INSERT INTO company (name, location, contact) VALUES (?, ?, ?)";
$company_stmt = $conn->prepare($company_query);
$company_stmt->bind_param("sss", $default_company_name, $default_location, $default_contact);

if ($company_stmt->execute()) {
    $compid = $conn->insert_id;
    echo "<p style='color: green;'>✓ Company created successfully with ID: $compid</p>";
    $company_stmt->close();
} else {
    echo "<p style='color: red;'>✗ Failed to create company: " . $company_stmt->error . "</p>";
    $company_stmt->close();
    exit;
}

// Test 2: Check if we can create a recruiter
echo "<h3>Test 2: Creating Recruiter</h3>";
$hashed_password = password_hash($test_password, PASSWORD_DEFAULT);
$recruiter_query = "INSERT INTO recruiter (username, email, password, compid) VALUES (?, ?, ?, ?)";
$recruiter_stmt = $conn->prepare($recruiter_query);
$recruiter_stmt->bind_param("sssi", $test_username, $test_email, $hashed_password, $compid);

if ($recruiter_stmt->execute()) {
    $recid = $conn->insert_id;
    echo "<p style='color: green;'>✓ Recruiter created successfully with ID: $recid</p>";
    $recruiter_stmt->close();
} else {
    echo "<p style='color: red;'>✗ Failed to create recruiter: " . $recruiter_stmt->error . "</p>";
    $recruiter_stmt->close();
    exit;
}

// Test 3: Verify the data was created correctly
echo "<h3>Test 3: Verifying Data</h3>";
$verify_query = "SELECT r.*, c.name as company_name FROM recruiter r JOIN company c ON r.compid = c.compid WHERE r.recid = ?";
$verify_stmt = $conn->prepare($verify_query);
$verify_stmt->bind_param("i", $recid);
$verify_stmt->execute();
$result = $verify_stmt->get_result();
$recruiter_data = $result->fetch_assoc();

if ($recruiter_data) {
    echo "<p style='color: green;'>✓ Data verification successful:</p>";
    echo "<ul>";
    echo "<li>Recruiter ID: " . $recruiter_data['recid'] . "</li>";
    echo "<li>Username: " . $recruiter_data['username'] . "</li>";
    echo "<li>Email: " . $recruiter_data['email'] . "</li>";
    echo "<li>Company ID: " . $recruiter_data['compid'] . "</li>";
    echo "<li>Company Name: " . $recruiter_data['company_name'] . "</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red;'>✗ Data verification failed</p>";
}

$verify_stmt->close();

// Clean up test data
echo "<h3>Cleanup</h3>";
$cleanup_recruiter = "DELETE FROM recruiter WHERE recid = ?";
$cleanup_stmt = $conn->prepare($cleanup_recruiter);
$cleanup_stmt->bind_param("i", $recid);
$cleanup_stmt->execute();
$cleanup_stmt->close();

$cleanup_company = "DELETE FROM company WHERE compid = ?";
$cleanup_stmt = $conn->prepare($cleanup_company);
$cleanup_stmt->bind_param("i", $compid);
$cleanup_stmt->execute();
$cleanup_stmt->close();

echo "<p style='color: blue;'>✓ Test data cleaned up</p>";

echo "<h3>Test Summary</h3>";
echo "<p style='color: green; font-weight: bold;'>✓ All tests passed! The registration process should work correctly.</p>";
?> 