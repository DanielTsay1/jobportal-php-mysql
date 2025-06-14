<!-- filepath: c:\Users\mandy\jobportal-php-mysql\main\job-list.php -->
<?php
session_start();
require_once '../php/db.php';

// Fetch filter options from the query string
$job_type = $_GET['job_type'] ?? 'All Types';
$work_arrangement = $_GET['work_arrangement'] ?? 'All Arrangements';
$min_pay = $_GET['min_pay'] ?? 0;
$sort_by = $_GET['sort_by'] ?? 'Most Recent';

// Build the query dynamically based on filters
$query = "SELECT * FROM `job-post` WHERE salary >= ?";
$params = [$min_pay];

if ($job_type !== 'All Types') {
    $query .= " AND designation LIKE ?";
    $params[] = "%$job_type%";
}

if ($work_arrangement !== 'All Arrangements') {
    $query .= " AND location LIKE ?";
    $params[] = "%$work_arrangement%";
}

$query .= $sort_by === 'Most Recent' ? " ORDER BY jobid DESC" : " ORDER BY salary ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Check if the user is logged in
$username = $_SESSION['username'] ?? null;

// Debugging: Check if the query failed
if (!$result) {
    die("Database query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Job List</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="/img/favicon.ico" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/lib/animate/animate.min.css" rel="stylesheet">
    <link href="/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Navbar Start -->
    <?php include 'header.php'; ?>
    <!-- Navbar End -->

    <!-- Header Start -->
    <div class="container-xxl py-5 bg-dark page-header mb-5">
        <div class="container my-5 pt-5 pb-4">
            <h1 class="display-3 text-white mb-3 animated slideInDown">Job List</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb text-uppercase">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Pages</a></li>
                    <li class="breadcrumb-item text-white active" aria-current="page">Job List</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Header End -->

    <!-- Filter Section -->
    <div class="container mb-5">
        <form method="GET" class="filter-form">
            <div class="row">
                <div class="col-md-3">
                    <select name="job_type" class="form-control">
                        <option>All Types</option>
                        <option>Internship</option>
                        <option>Part-Time</option>
                        <option>Full-Time</option>
                        <option>Volunteer</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="work_arrangement" class="form-control">
                        <option>All Arrangements</option>
                        <option>Remote</option>
                        <option>Hybrid</option>
                        <option>In-Person</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="number" name="min_pay" class="form-control" placeholder="Min. Pay ($)" />
                </div>
                <div class="col-md-3">
                    <select name="sort_by" class="form-control">
                        <option>Most Recent</option>
                        <option>Highest Pay</option>
                        <option>Lowest Pay</option>
                        <option>Most Applied</option>
                        <option>A-Z</option>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12 text-center">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Jobs Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <h1 class="text-center mb-5 wow fadeInUp" data-wow-delay="0.1s">Job Listing</h1>
            <div class="row">
                <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                <div class="col-lg-6 col-md-12 mb-4">
                    <div class="job-item p-4 border rounded shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><?= htmlspecialchars($row['designation']) ?></h5>
                            <span class="badge bg-primary"><?= htmlspecialchars($row['location']) ?></span>
                        </div>
                        <p class="mb-2"><?= htmlspecialchars($row['description']) ?></p>
                        <div class="d-flex flex-wrap mb-3">
                            <span class="badge bg-secondary me-2">Pay: $<?= htmlspecialchars($row['salary']) ?></span>
                            <span class="badge bg-secondary me-2">Company: <?= htmlspecialchars($row['company']) ?></span>
                            <span class="badge bg-secondary me-2"><?= htmlspecialchars($row['work_arrangement'] ?? 'N/A') ?></span>
                        </div>
                        <a href="/main/job-detail.php?jobid=<?= htmlspecialchars($row['jobid']) ?>" class="btn btn-primary">Apply Now</a>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php else: ?>
                <p class="text-center">No jobs available at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Jobs End -->

    <!-- Footer Start -->
    <?php include 'footer.php'; ?>
    <!-- Footer End -->

    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/lib/wow/wow.min.js"></script>
    <script src="/lib/easing/easing.min.js"></script>
    <script src="/lib/waypoints/waypoints.min.js"></script>
    <script src="/lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="/js/main.js"></script>
</body>

</html>