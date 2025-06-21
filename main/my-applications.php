<?php
session_start();
require_once '../php/db.php';

$userid = $_SESSION['userid'] ?? null;
if (!$userid) {
    header('Location: /main/login.html');
    exit;
}

// Fetch user's applications
$applications = [];
$sql = "SELECT 
            a.`S. No` as app_id,
            a.applied_at,
            a.status,
            j.designation,
            j.jobid,
            c.name as company_name
        FROM applied a
        JOIN `job-post` j ON a.jobid = j.jobid
        JOIN company c ON j.compid = c.compid
        WHERE a.userid = ?
        ORDER BY a.applied_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userid);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $applications[] = $row;
}
$stmt->close();
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'header-jobseeker.php'; ?>

    <div class="container py-5">
        <h1 class="mb-4">My Applications</h1>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <?php if (empty($applications)): ?>
                    <div class="alert alert-info m-4">You have not applied to any jobs yet.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($applications as $app): ?>
                            <div class="list-group-item p-4" id="app<?= $app['app_id'] ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">
                                        <a href="/main/job-details.php?jobid=<?= $app['jobid'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($app['designation']) ?>
                                        </a>
                                    </h5>
                                    <small>Applied on <?= date('M d, Y', strtotime($app['applied_at'])) ?></small>
                                </div>
                                <p class="mb-1">
                                    At <?= htmlspecialchars($app['company_name']) ?>
                                </p>
                                <div class="d-flex align-items-center mt-2">
                                    <strong class="me-2">Status:</strong>
                                    <span class="badge 
                                        <?php 
                                        switch($app['status']) {
                                            case 'Hired': echo 'bg-success'; break;
                                            case 'Rejected': echo 'bg-danger'; break;
                                            case 'Shortlisted': echo 'bg-info text-dark'; break;
                                            case 'Viewed': echo 'bg-secondary'; break;
                                            default: echo 'bg-primary';
                                        }
                                        ?>">
                                        <?= htmlspecialchars($app['status']) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>