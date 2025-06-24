<?php
session_start();
require_once '../php/db.php';

// Get search parameters
$search = $_GET['search'] ?? '';
$location = $_GET['location'] ?? '';
$salary_min = $_GET['salary_min'] ?? '';
$salary_max = $_GET['salary_max'] ?? '';
$category = $_GET['category'] ?? '';

// Build query with filters
$where_conditions = ["j.status = 'Active'"];
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
    <link href="/css/style.css" rel="stylesheet">
    <style>
        .search-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .filter-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .job-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            border: none;
        }
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .salary-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
        }
        .company-badge {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
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
            color: #6c757d;
        }
        .search-input {
            border-radius: 25px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }
        .search-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .filter-btn {
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            border: 2px solid #667eea;
            background: transparent;
            color: #667eea;
            transition: all 0.3s ease;
        }
        .filter-btn:hover, .filter-btn.active {
            background: #667eea;
            color: white;
        }
        .clear-filters {
            color: #dc3545;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .clear-filters:hover {
            color: #c82333;
        }
        .results-count {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include 'header-jobseeker.php'; ?>

    <!-- Search Container -->
    <div class="search-container">
        <div class="container">
            <h2 class="text-white text-center mb-4">
                <i class="fas fa-search me-2"></i>Find Your Dream Job
            </h2>
            
            <!-- Search Form -->
            <form id="searchForm" class="row g-3">
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
                    <button type="submit" class="btn btn-light w-100" style="border-radius: 25px;">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="container">
        <!-- Filters -->
        <div class="filter-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h6 class="mb-2">Filter by Category:</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn filter-btn <?= empty($category) ? 'active' : '' ?>" 
                                data-category="">All Jobs</button>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <button class="btn filter-btn <?= $category === $cat['designation'] ? 'active' : '' ?>" 
                                    data-category="<?= htmlspecialchars($cat['designation']) ?>">
                                <?= htmlspecialchars($cat['designation']) ?>
                            </button>
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
                                <h5 class="mb-2">
                                    <a href="job-details.php?jobid=<?= $job['jobid'] ?>" 
                                       class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($job['designation']) ?>
                                    </a>
                                </h5>
                                <div class="mb-2">
                                    <span class="company-badge me-2">
                                        <i class="fas fa-building me-1"></i>
                                        <?= htmlspecialchars($job['company_name']) ?>
                                    </span>
                                    <span class="text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?= htmlspecialchars($job['location']) ?>
                                    </span>
                                </div>
                                <p class="text-muted mb-0">
                                    <?= htmlspecialchars(substr($job['description'], 0, 150)) ?>...
                                </p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <div class="salary-badge mb-2">
                                    $<?= number_format($job['salary']) ?>/year
                                </div>
                                <a href="job-details.php?jobid=<?= $job['jobid'] ?>" 
                                   class="btn btn-primary">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4>No jobs found</h4>
                    <p>Try adjusting your search criteria or browse all available positions.</p>
                    <a href="job-list.php" class="btn btn-primary">View All Jobs</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Debounce function for search
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Real-time search functionality
        const searchInput = document.getElementById('searchInput');
        const locationSelect = document.getElementById('locationSelect');
        const salaryMin = document.getElementById('salaryMin');
        const salaryMax = document.getElementById('salaryMax');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const jobResults = document.getElementById('jobResults');
        const resultsCount = document.getElementById('resultsCount');

        function performSearch() {
            const formData = new FormData();
            formData.append('search', searchInput.value);
            formData.append('location', locationSelect.value);
            formData.append('salary_min', salaryMin.value);
            formData.append('salary_max', salaryMax.value);

            // Show loading spinner
            loadingSpinner.style.display = 'block';
            jobResults.style.display = 'none';

            fetch('job-list.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // Parse the HTML and extract job results
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newResults = doc.getElementById('jobResults');
                const newCount = doc.getElementById('resultsCount');
                
                if (newResults) {
                    jobResults.innerHTML = newResults.innerHTML;
                }
                if (newCount) {
                    resultsCount.innerHTML = newCount.innerHTML;
                }
                
                // Hide loading spinner
                loadingSpinner.style.display = 'none';
                jobResults.style.display = 'block';
            })
            .catch(error => {
                console.error('Search error:', error);
                loadingSpinner.style.display = 'none';
                jobResults.style.display = 'block';
            });
        }

        // Add event listeners for real-time search
        const debouncedSearch = debounce(performSearch, 500);
        
        searchInput.addEventListener('input', debouncedSearch);
        locationSelect.addEventListener('change', debouncedSearch);
        salaryMin.addEventListener('input', debouncedSearch);
        salaryMax.addEventListener('input', debouncedSearch);

        // Category filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const category = this.dataset.category;
                
                // Update active state
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Update URL and reload
                const url = new URL(window.location);
                if (category) {
                    url.searchParams.set('category', category);
                } else {
                    url.searchParams.delete('category');
                }
                window.location.href = url.toString();
            });
        });

        // Form submission
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch();
        });
    </script>
</body>
</html>