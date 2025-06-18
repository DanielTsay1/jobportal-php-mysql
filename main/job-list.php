<?php
session_start();
require_once '../php/db.php';

// --- Filter logic ---
$keyword = $_GET['keyword'] ?? '';
$location = $_GET['location'] ?? '';
$company = $_GET['company'] ?? '';
$salary_min = $_GET['salary_min'] ?? '';
$salary_max = $_GET['salary_max'] ?? '';

$where = ["jp.status = 'Active'"];
$params = [];
$types = '';

if ($keyword !== '') {
    $where[] = "(jp.designation LIKE ? OR jp.description LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $types .= 'ss';
}
if ($location !== '') {
    $where[] = "jp.location LIKE ?";
    $params[] = "%$location%";
    $types .= 's';
}
if ($company !== '') {
    $where[] = "c.name LIKE ?";
    $params[] = "%$company%";
    $types .= 's';
}
if ($salary_min !== '') {
    $where[] = "jp.salary >= ?";
    $params[] = $salary_min;
    $types .= 'i';
}
if ($salary_max !== '') {
    $where[] = "jp.salary <= ?";
    $params[] = $salary_max;
    $types .= 'i';
}

$where_sql = implode(' AND ', $where);

$sql = "SELECT jp.*, c.name AS company_name, c.location AS company_location, c.website AS company_website 
        FROM `job-post` jp 
        JOIN company c ON jp.compid = c.compid 
        WHERE $where_sql 
        ORDER BY jp.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$jobs = [];
while ($row = $result->fetch_assoc()) {
    $jobs[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Browse Jobs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php include 'header-jobseeker.php'; ?>
<div class="container py-4">
    <h2 class="mb-4"><i class="fa fa-search"></i> Browse Jobs</h2>
    <!-- Filter Form -->
    <form class="row g-3 mb-4" method="get">
        <div class="col-md-3">
            <input type="text" name="keyword" class="form-control" placeholder="Keyword (title or description)" value="<?= htmlspecialchars($keyword) ?>">
        </div>
        <div class="col-md-2">
            <input type="text" name="location" class="form-control" placeholder="Location" value="<?= htmlspecialchars($location) ?>">
        </div>
        <div class="col-md-2">
            <input type="text" name="company" class="form-control" placeholder="Company" value="<?= htmlspecialchars($company) ?>">
        </div>
        <div class="col-md-2">
            <input type="number" name="salary_min" class="form-control" placeholder="Min Salary" value="<?= htmlspecialchars($salary_min) ?>">
        </div>
        <div class="col-md-2">
            <input type="number" name="salary_max" class="form-control" placeholder="Max Salary" value="<?= htmlspecialchars($salary_max) ?>">
        </div>
        <div class="col-md-1 d-grid">
            <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button>
        </div>
    </form>
    <?php if (count($jobs) > 0): ?>
        <div class="row g-4">
        <?php foreach ($jobs as $job): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($job['designation']) ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($job['company_name']) ?></h6>
                        <p class="card-text"><?= nl2br(htmlspecialchars($job['description'])) ?></p>
                        <ul class="list-unstyled mb-2">
                            <li><i class="fa fa-map-marker-alt text-danger"></i> <?= htmlspecialchars($job['location']) ?></li>
                            <li><i class="fa fa-dollar-sign text-success"></i> <?= htmlspecialchars($job['salary']) ?></li>
                        </ul>
                        <a href="job-details.php?jobid=<?= urlencode($job['jobid']) ?>" class="btn btn-primary btn-sm">View Details</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No jobs found. Try adjusting your filters.</div>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>