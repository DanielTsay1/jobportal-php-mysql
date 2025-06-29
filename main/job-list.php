<?php
require_once '../php/db.php';

// Handle both GET and POST requests for search
$search = $_GET['search'] ?? $_POST['search'] ?? '';
$location = $_GET['location'] ?? $_POST['location'] ?? '';
$salary_min = $_GET['salary_min'] ?? $_POST['salary_min'] ?? '';
$salary_max = $_GET['salary_max'] ?? $_POST['salary_max'] ?? '';
$category = $_GET['category'] ?? $_POST['category'] ?? '';

// Build query with filters
$where_conditions = ["j.status = 'Active'", "(c.suspended IS NULL OR c.suspended = 0)"];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(j.designation LIKE ? OR j.description LIKE ? OR c.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if (!empty($location)) {
    $where_conditions[] = "j.location LIKE ?";
    $params[] = "%$location%";
    $types .= 's';
}

if (!empty($salary_min)) {
    $where_conditions[] = "j.salary >= ?";
    $params[] = $salary_min;
    $types .= 'i';
}

if (!empty($salary_max)) {
    $where_conditions[] = "j.salary <= ?";
    $params[] = $salary_max;
    $types .= 'i';
}

if (!empty($category)) {
    $where_conditions[] = "j.designation LIKE ?";
    $params[] = "%$category%";
    $types .= 's';
}

$where_clause = implode(' AND ', $where_conditions);

$sql = "SELECT j.*, c.name as company_name, c.location as company_location 
        FROM `job-post` j 
        JOIN company c ON j.compid = c.compid 
        WHERE $where_clause 
        ORDER BY j.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$jobs = $stmt->get_result();
$stmt->close();

// Get unique locations and categories for filters
$locations_stmt = $conn->prepare("SELECT DISTINCT location FROM `job-post` WHERE status = 'Active' AND location != '' ORDER BY location");
$locations_stmt->execute();
$locations = $locations_stmt->get_result();
$locations_stmt->close();

$categories_stmt = $conn->prepare("SELECT DISTINCT designation FROM `job-post` WHERE status = 'Active' ORDER BY designation");
$categories_stmt->execute();
$categories = $categories_stmt->get_result();
$categories_stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Job Listings - JobPortal</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #181828 0%, #23233a 100%);
            color: #f3f3fa;
            font-family: 'Inter', Arial, sans-serif;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            padding-top: 68px;
        }
        
        .search-container {
            background: rgba(255,255,255,0.10);
            backdrop-filter: blur(18px) saturate(1.2);
            border: 1.5px solid rgba(255,255,255,0.13);
            border-radius: 24px;
            padding: 2.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(30,20,60,0.13);
        }
        
        .search-container h2 {
            color: #fff;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .filter-card {
            background: rgba(255,255,255,0.13);
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(123,63,228,0.08);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1.5px solid rgba(255,255,255,0.10);
            backdrop-filter: blur(8px) saturate(1.1);
        }
        
        .job-card {
            background: rgba(255,255,255,0.13);
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(123,63,228,0.08);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            border: 1.5px solid rgba(255,255,255,0.10);
            backdrop-filter: blur(8px) saturate(1.1);
            color: #f3f3fa;
        }
        
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(123,63,228,0.13);
            border-color: rgba(0,224,214,0.3);
        }
        
        .salary-badge {
            background: linear-gradient(135deg, #00e0d6 0%, #7b3fe4 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
        }
        
        .company-badge {
            background: linear-gradient(135deg, #7b1fa2 0%, #1976d2 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }
        
        .no-results {
            text-align: center;
            padding: 3rem;
            color: #b3b3c6;
        }
        
        .search-input {
            border-radius: 25px;
            border: 2px solid rgba(255,255,255,0.2);
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.1);
            color: #f3f3fa;
            backdrop-filter: blur(8px);
        }
        
        .search-input:focus {
            border-color: #00e0d6;
            box-shadow: 0 0 0 0.2rem rgba(0,224,214,0.25);
            background: rgba(255,255,255,0.15);
        }
        
        .search-input::placeholder {
            color: #b3b3c6;
        }
        
        .filter-btn {
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            border: 2px solid #00e0d6;
            background: transparent;
            color: #00e0d6;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: linear-gradient(135deg, #00e0d6 0%, #7b3fe4 100%);
            color: white;
            border-color: #00e0d6;
        }
        
        .clear-filters {
            color: #ff6b6b;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .clear-filters:hover {
            color: #ff5252;
        }
        
        .results-count {
            color: #b3b3c6;
            font-size: 0.9rem;
        }
        
        .btn-apply {
            background: linear-gradient(135deg, #00e0d6 0%, #7b3fe4 100%);
            border: none;
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-apply:hover {
            background: linear-gradient(135deg, #7b3fe4 0%, #00e0d6 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,224,214,0.3);
        }
        
        .job-title {
            color: #fff;
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }
        
        .job-company {
            color: #00e0d6;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .job-location {
            color: #b3b3c6;
            font-size: 0.9rem;
        }
        
        .job-description {
            color: #b3b3c6;
            line-height: 1.6;
        }
        
        .main-header-glass {
            position: fixed;
            top: 0; left: 0; width: 100vw;
            height: 68px;
            z-index: 2000;
            background: rgba(30, 30, 50, 0.38);
            backdrop-filter: blur(18px) saturate(1.2);
            box-shadow: 0 2px 16px rgba(30,20,60,0.10);
            border-bottom: 1.5px solid rgba(255,255,255,0.10);
            display: flex;
            align-items: center;
            transition: background 0.18s;
        }
        
        .nav-link-glass {
            color: #f3f3fa;
            font-weight: 500;
            font-size: 1.08rem;
            text-decoration: none;
            padding: 0.3rem 1.1rem;
            border-radius: 18px;
            transition: background 0.18s, color 0.18s;
            opacity: 0.92;
        }
        
        .nav-link-glass:hover, .nav-link-glass:focus {
            background: rgba(0,224,214,0.10);
            color: #00e0d6;
            text-decoration: none;
        }
        
        .nav-link-cta {
            background: linear-gradient(135deg, #00e0d6 0%, #7b3fe4 100%);
            color: #fff !important;
            font-weight: 700;
            border-radius: 22px;
            padding: 0.3rem 1.5rem;
            margin-left: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,224,214,0.10);
            transition: background 0.18s, color 0.18s;
        }
        
        .nav-link-cta:hover, .nav-link-cta:focus {
            background: linear-gradient(135deg, #7b3fe4 0%, #00e0d6 100%);
            color: #fff;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .job-card {
            animation: fadeInUp 0.6s cubic-bezier(.4,1.4,.6,1);
        }
        
        html, body {
            height: 100%;
        }
        
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .main-content {
            flex: 1 0 auto;
        }
        
        footer {
            flex-shrink: 0;
            width: 100vw;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

    <!-- Search Container -->
    <div class="search-container">
        <div class="container">
            <h2 class="text-white text-center mb-4">
                <i class="fas fa-search me-2"></i>Find Your Dream Job
            </h2>
            
            <!-- Search Form -->
            <form id="searchForm" class="row g-3" method="GET">
                <div class="col-md-4">
                    <input type="text" class="form-control search-input" id="searchInput" 
                           name="search" placeholder="Job title, company, or keywords..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-control search-input" id="locationSelect" name="location">
                        <option value="">All Locations</option>
                        <?php while ($loc = $locations->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($loc['location']) ?>" 
                                    <?= $location === $loc['location'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($loc['location']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control search-input" id="salaryMin" 
                           name="salary_min" placeholder="Min Salary" 
                           value="<?= htmlspecialchars($salary_min) ?>">
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control search-input" id="salaryMax" 
                           name="salary_max" placeholder="Max Salary" 
                           value="<?= htmlspecialchars($salary_max) ?>">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-apply w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="container main-content">
        <!-- Error Messages -->
        <?php if (isset($_GET['error']) && $_GET['error'] === 'job_not_available'): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert" style="background: rgba(255,193,7,0.1); border: 1px solid rgba(255,193,7,0.3); color: #ffc107;">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Job Not Available</strong> The job you were looking for is not currently available for applications.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filter-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h6 class="mb-2" style="color: #fff; font-weight: 600;">Filter by Category:</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="job-list.php" class="btn filter-btn <?= empty($category) ? 'active' : '' ?>">All Jobs</a>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <a href="job-list.php?category=<?= urlencode($cat['designation']) ?>" 
                               class="btn filter-btn <?= $category === $cat['designation'] ? 'active' : '' ?>">
                                <?= htmlspecialchars($cat['designation']) ?>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <span class="results-count" id="resultsCount">
                        <?= $jobs->num_rows ?> job<?= $jobs->num_rows !== 1 ? 's' : '' ?> found
                    </span>
                    <?php if (!empty($search) || !empty($location) || !empty($salary_min) || !empty($salary_max) || !empty($category)): ?>
                        <br><a href="job-list.php" class="clear-filters">Clear all filters</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Searching for jobs...</p>
        </div>

        <!-- Job Results -->
        <div id="jobResults">
            <?php if ($jobs->num_rows > 0): ?>
                <?php while ($job = $jobs->fetch_assoc()): ?>
                    <div class="job-card p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="job-title mb-2">
                                    <a href="job-details.php?jobid=<?= $job['jobid'] ?>" 
                                       class="text-decoration-none" style="color: #fff;">
                                        <?= htmlspecialchars($job['designation']) ?>
                                    </a>
                                </h5>
                                <div class="job-company mb-2">
                                    <span class="company-badge me-2">
                                        <i class="fas fa-building me-1"></i>
                                        <?= htmlspecialchars($job['company_name']) ?>
                                    </span>
                                    <span class="job-location">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?= htmlspecialchars($job['location']) ?>
                                    </span>
                                </div>
                                <p class="job-description mb-0">
                                    <?= htmlspecialchars(substr($job['description'], 0, 150)) ?>...
                                </p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <div class="salary-badge mb-3">
                                    $<?= number_format($job['salary']) ?>/year
                                </div>
                                <a href="job-details.php?jobid=<?= $job['jobid'] ?>" 
                                   class="btn btn-apply">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-search fa-3x mb-3" style="color: #b3b3c6;"></i>
                    <h4 style="color: #fff;">No jobs found</h4>
                    <p>Try adjusting your search criteria or browse all available positions.</p>
                    <a href="job-list.php" class="btn btn-apply">View All Jobs</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple form submission - no need for complex AJAX since we're using GET
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            // Form will submit normally with GET method
            // Show loading spinner
            document.getElementById('loadingSpinner').style.display = 'block';
            document.getElementById('jobResults').style.display = 'none';
        });

        // Show loading when filters change
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('loadingSpinner').style.display = 'block';
                document.getElementById('jobResults').style.display = 'none';
            });
        });
    </script>
</body>
</html>