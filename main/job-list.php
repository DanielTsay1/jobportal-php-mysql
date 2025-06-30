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
    <title>TechJobs - Premium Job Portal</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #2563eb;
            --primary-blue-dark: #1d4ed8;
            --accent-blue: #3b82f6;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --border-light: #e5e7eb;
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.08), 0 2px 4px -2px rgb(0 0 0 / 0.08);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
        }
        .navbar {
            background: var(--bg-white);
            box-shadow: var(--shadow-md);
            padding: 1rem 0;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        .navbar-brand {
            font-weight: 900;
            font-size: 1.5rem;
            color: var(--primary-blue);
            text-decoration: none;
        }
        .hero-section {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            color: white;
            padding: 7rem 0 3rem;
            text-align: center;
            animation: fadeInUp 0.4s ease-out;
        }
        .hero-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 900;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            letter-spacing: -0.02em;
            animation: fadeInUp 0.4s ease-out 0.05s both;
        }
        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2.5rem;
            opacity: 0.95;
            font-weight: 400;
            animation: fadeInUp 0.4s ease-out 0.1s both;
        }
        .search-container, .filter-section {
            background: var(--bg-white);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            padding: 2rem 2rem 1.5rem 2rem;
            margin: -3rem auto 2rem auto;
            max-width: 1000px;
            animation: fadeInUp 0.4s ease-out 0.15s both;
        }
        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: end;
        }
        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        .search-input, select {
            background: var(--bg-light);
            border: 2px solid var(--border-light);
            border-radius: 12px;
            padding: 0.85rem 1.2rem;
            font-size: 1rem;
            color: var(--text-dark);
            transition: all 0.2s ease;
        }
        .search-input:focus, select:focus {
            border-color: var(--primary-blue);
            background: var(--bg-white);
            outline: none;
            box-shadow: 0 0 0 2px #2563eb22;
            transform: translateY(-1px);
        }
        .search-btn {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            border: none;
            border-radius: 12px;
            padding: 0.55rem 0.9rem;
            font-size: 1.15rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            min-width: 42px;
            min-height: 42px;
            font-weight: 600;
            color: white;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-md);
        }
        .search-btn i {
            margin: 0;
            font-size: 1.25rem;
            display: block;
            transition: transform 0.2s ease;
        }
        .search-btn:hover {
            background: linear-gradient(135deg, var(--accent-blue) 0%, var(--primary-blue) 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.13);
            transform: translateY(-2px);
        }
        .search-btn:hover i {
            transform: scale(1.1);
        }
        .filter-btn {
            background: var(--bg-light);
            border: 2px solid var(--border-light);
            border-radius: 30px;
            padding: 0.5rem 1.25rem;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }
        .filter-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(37, 99, 235, 0.1), transparent);
            transition: left 0.3s ease;
        }
        .filter-btn:hover::before {
            left: 100%;
        }
        .filter-btn:hover, .filter-btn.active {
            background: var(--primary-blue);
            border-color: var(--primary-blue);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .job-card {
            background: var(--bg-white);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            padding: 2rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-light);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.3s ease-out both;
            animation-delay: calc(var(--delay, 0) + 0.05s);
        }
        .job-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-blue), var(--accent-blue));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        .job-card:hover::before {
            transform: scaleX(1);
        }
        .job-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 35px rgba(37,99,235,0.15);
            border-color: var(--primary-blue);
        }
        .job-title {
            font-size: 1.375rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .job-title:hover {
            color: var(--primary-blue);
        }
        .company-badge {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }
        .company-badge:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }
        .job-location {
            color: var(--text-light);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .salary-badge {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 0.4rem 0.9rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.08);
            /* Subtle breathing animation */
            animation: salaryFloat 5s ease-in-out infinite;
        }
        @keyframes salaryFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-2px); }
        }
        .salary-badge:hover {
            /* No animation on hover */
            transform: none;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.08);
        }
        .view-btn {
            background: var(--bg-light);
            border: 2px solid var(--primary-blue);
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            color: var(--primary-blue);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }
        .view-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(37, 99, 235, 0.1), transparent);
            transition: left 0.3s ease;
        }
        .view-btn:hover::before {
            left: 100%;
        }
        .view-btn:hover {
            background: var(--primary-blue);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .view-btn:hover i {
            transform: translateX(3px);
        }
        .view-btn i {
            transition: transform 0.2s ease;
        }
        .compact-btn {
            background: var(--bg-light);
            border: 2px solid var(--primary-blue);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            color: var(--primary-blue);
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .compact-btn:hover {
            background: var(--primary-blue);
            color: white;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        .text-link {
            color: var(--primary-blue);
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .text-link:hover {
            color: var(--primary-blue-dark);
            text-decoration: underline;
        }
        .small-btn {
            background: var(--primary-blue);
            border: none;
            border-radius: 6px;
            padding: 0.4rem 0.8rem;
            color: white;
            font-weight: 500;
            font-size: 0.8rem;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-block;
        }
        .small-btn:hover {
            background: var(--primary-blue-dark);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);
        }
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-light);
            animation: fadeInUp 0.4s ease-out 0.3s both;
        }
        .no-results i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            color: var(--primary-blue);
            opacity: 0.5;
            animation: pulse 1s ease-in-out infinite;
        }
        .no-results h3 {
            color: var(--text-dark);
            margin-bottom: 1rem;
        }
        .footer {
            background: var(--bg-white);
            border-top: 1px solid var(--border-light);
            padding: 3rem 0 2rem;
            margin-top: 4rem;
            text-align: center;
            box-shadow: 0 -4px 6px -1px rgb(0 0 0 / 0.05);
            animation: fadeInUp 0.4s ease-out 0.35s both;
        }
        .footer-content {
            max-width: 800px;
            margin: 0 auto;
        }
        .footer-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--primary-blue);
        }
        .footer p {
            color: var(--text-light);
        }
        .footer a {
            color: var(--primary-blue);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .footer a:hover {
            color: var(--primary-blue-dark);
        }
        .footer i {
            color: var(--primary-blue);
        }
        .admin-link {
            font-size: 0.85rem;
            opacity: 0.7;
            margin-top: 1rem;
            display: inline-block;
        }
        .admin-link:hover {
            opacity: 1;
        }
        .border-secondary {
            border-color: var(--border-light) !important;
        }
        .text-secondary {
            color: var(--text-light) !important;
        }
        .text-light {
            color: var(--text-dark) !important;
        }
        .glass {
            background: var(--bg-white);
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-md);
        }

        /* Animation Keyframes */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% { 
                transform: scale(1);
                opacity: 0.5;
            }
            50% { 
                transform: scale(1.05);
                opacity: 0.7;
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes bounceIn {
            from {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Loading Animation */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 3rem 2rem;
            animation: fadeInUp 0.3s ease-out;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid var(--border-light);
            border-top: 4px solid var(--primary-blue);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Results count animation */
        .results-count {
            animation: bounceIn 0.4s ease-out 0.2s both;
        }

        /* Filter buttons staggered animation */
        .filter-btn {
            animation: fadeInUp 0.4s ease-out both;
        }

        .filter-btn:nth-child(1) { animation-delay: 0.2s; }
        .filter-btn:nth-child(2) { animation-delay: 0.225s; }
        .filter-btn:nth-child(3) { animation-delay: 0.25s; }
        .filter-btn:nth-child(4) { animation-delay: 0.275s; }
        .filter-btn:nth-child(5) { animation-delay: 0.3s; }
        .filter-btn:nth-child(6) { animation-delay: 0.325s; }
        .filter-btn:nth-child(7) { animation-delay: 0.35s; }
        .filter-btn:nth-child(8) { animation-delay: 0.375s; }

        /* Floating animation for hero elements */
        .hero-title {
            animation: fadeInUp 0.4s ease-out 0.05s both, float 3s ease-in-out infinite 0.8s;
        }

        .hero-subtitle {
            animation: fadeInUp 0.4s ease-out 0.1s both, float 3s ease-in-out infinite 1s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-6px); }
        }

        /* Enhanced hover effects */
        .search-container:hover, .filter-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.1);
        }

        .search-container, .filter-section {
            transition: all 0.2s ease;
        }

        @media (max-width: 900px) {
            .search-container, .filter-section, .job-card {
                max-width: 98vw;
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
        }
        @media (max-width: 600px) {
            .search-container, .filter-section, .job-card {
                padding: 1.2rem 0.5rem;
            }
            .hero-title { font-size: 2.1rem; }
        }

        #floatingChatBtn {
            position: fixed;
            bottom: 32px;
            right: 32px;
            z-index: 99999;
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            color: #fff;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px rgba(37,99,235,0.18);
            font-size: 2rem;
            transition: background 0.2s, box-shadow 0.2s, transform 0.2s;
            border: none;
            outline: none;
            cursor: pointer;
            text-decoration: none;
        }
        #floatingChatBtn:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
            box-shadow: 0 12px 32px rgba(37,99,235,0.25);
            transform: translateY(-2px) scale(1.07);
            color: #fff;
            text-decoration: none;
        }
        #floatingChatBtn:active {
            transform: scale(0.97);
        }
        #floatingChatBtn i {
            pointer-events: none;
        }
        @media (max-width: 600px) {
            #floatingChatBtn {
                right: 16px;
                bottom: 16px;
                width: 48px;
                height: 48px;
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'header-jobseeker.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="hero-title">Find Your Next Tech Adventure</h1>
            <p class="hero-subtitle">Discover cutting-edge opportunities at the world's most innovative companies</p>
        </div>
    </section>

    <!-- Search Container -->
    <div class="container">
        <div class="search-container">
            <form id="searchForm" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label mb-2">
                        <i class="fas fa-search me-2"></i>Search Jobs
                    </label>
                    <input type="text" class="form-control search-input" id="searchInput" 
                           name="search" placeholder="React Developer, AI Engineer..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-2">
                        <i class="fas fa-map-marker-alt me-2"></i>Location
                    </label>
                    <select class="form-control search-input" id="locationSelect" name="location">
                        <option value="">Anywhere</option>
                        <?php while ($loc = $locations->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($loc['location']) ?>" 
                                    <?= $location === $loc['location'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($loc['location']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-2">Min Salary</label>
                    <input type="number" class="form-control search-input" id="salaryMin" 
                           name="salary_min" placeholder="50000" 
                           value="<?= htmlspecialchars($salary_min) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-2">Max Salary</label>
                    <input type="number" class="form-control search-input" id="salaryMax" 
                           name="salary_max" placeholder="200000" 
                           value="<?= htmlspecialchars($salary_max) ?>">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn search-btn w-100">
                        <i class="fas fa-rocket"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h6 class="mb-3">
                        <i class="fas fa-filter me-2"></i>Filter by Category
                    </h6>
                    <div class="d-flex flex-wrap">
                        <button class="btn filter-btn <?= empty($category) ? 'active' : '' ?>" 
                                data-category="">All Roles</button>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <button class="btn filter-btn <?= $category === $cat['designation'] ? 'active' : '' ?>" 
                                    data-category="<?= htmlspecialchars($cat['designation']) ?>">
                                <?= htmlspecialchars($cat['designation']) ?>
                            </button>
                        <?php endwhile; ?>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="stats-section">
                        <div class="results-count" id="resultsCount">
                            <i class="fas fa-briefcase me-2"></i>
                            <?= $jobs->num_rows ?> opportunities found
                        </div>
                        <?php if (!empty($search) || !empty($location) || !empty($salary_min) || !empty($salary_max) || !empty($category)): ?>
                            <a href="job-list.php" class="clear-filters">
                                <i class="fas fa-times me-1"></i>Clear filters
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner"></div>
            <p>Discovering amazing opportunities...</p>
        </div>

        <!-- Job Results -->
        <div id="jobResults">
            <?php if ($jobs->num_rows > 0): ?>
                <?php $delay = 0; ?>
                <?php while ($job = $jobs->fetch_assoc()): ?>
                    <div class="job-card" style="--delay: <?= $delay * 0.05 ?>s;">
                        <div class="row align-items-center">
                            <div class="col-lg-8">
                                <h3 class="job-title mb-3">
                                    <a href="job-details.php?jobid=<?= $job['jobid'] ?>" class="job-title">
                                        <?= htmlspecialchars($job['designation']) ?>
                                    </a>
                                </h3>
                                <div class="mb-3">
                                    <span class="company-badge">
                                        <i class="fas fa-building"></i>
                                        <?= htmlspecialchars($job['company_name']) ?>
                                    </span>
                                    <span class="job-location">
                                        <i class="fas fa-map-marker-alt me-2"></i>
                                        <?= htmlspecialchars($job['location']) ?>
                                    </span>
                                </div>
                                <p class="text-secondary mb-0 lh-relaxed">
                                    <?= htmlspecialchars(substr($job['description'], 0, 180)) ?>...
                                </p>
                            </div>
                            <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                                <div class="salary-badge mb-3">
                                    <i class="fas fa-dollar-sign me-1"></i>
                                    <?= number_format($job['salary']) ?>/year
                                </div>
                                <div>
                                    <a href="job-details.php?jobid=<?= $job['jobid'] ?>" class="view-btn">
                                        <i class="fas fa-arrow-right"></i>
                                        Explore Role
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php $delay++; ?>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-search-plus"></i>
                    <h3 class="mb-3">No opportunities found</h3>
                    <p class="mb-4">Try adjusting your search criteria or explore all available positions.</p>
                    <a href="job-list.php" class="small-btn">
                        Explore All Jobs
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <h4 class="footer-title">Ready to Shape the Future?</h4>
                <p class="mb-4">Join thousands of tech professionals finding their dream roles</p>
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <p class="mb-2">
                            <i class="fas fa-envelope me-2"></i>
                            <a href="mailto:hello@jobportal.com" class="text-decoration-none">
                                hello@jobportal.com
                            </a>
                        </p>
                        <p class="mb-3">
                            <i class="fas fa-phone me-2"></i>
                            <span>+1 (555) JOB-PORTAL</span>
                        </p>
                    </div>
                </div>
                <div class="border-top border-secondary pt-3 mt-4">
                    <p class="mb-0 text-secondary">
                        &copy; <?= date('Y') ?> <span style="color: var(--primary-blue);">Job</span><span style="color: var(--accent-blue);">Portal</span> 
                        &mdash; Where innovation meets opportunity
                    </p>
                    <a href="admin-login.php" class="admin-link">
                        <i class="fas fa-user-shield me-1"></i>Admin Login
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Floating Chat Button -->
    <a href="https://www.stack-ai.com/chat/68623c004fe0ebb9c4eaeec8-6jBGBvdYxWKz2625u0mQhn" target="_blank" rel="noopener" id="floatingChatBtn" title="Chat with JobPortal AI Agent">
      <i class="fas fa-comments"></i>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enhanced debounce function
        function debounce(func, wait, immediate) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    if (!immediate) func(...args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func(...args);
            };
        }

        // Initialize animations when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Add entrance animation to job cards
            const jobCards = document.querySelectorAll('.job-card');
            jobCards.forEach((card, index) => {
                card.style.animationDelay = `${0.2 + (index * 0.05)}s`;
            });

            // Filter button functionality with animations
            const filterButtons = document.querySelectorAll('.filter-btn');
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const category = this.getAttribute('data-category');
                    
                    // Remove active class from all buttons
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Add active class to clicked button with animation
                    this.classList.add('active');
                    this.style.transform = 'scale(1.05)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 200);

                    // Filter job cards with animation
                    filterJobCards(category);
                });
            });

            // Enhanced search form with animations
            const searchForm = document.getElementById('searchForm');
            const searchInput = document.getElementById('searchInput');
            const searchBtn = document.querySelector('.search-btn');

            // Add focus animations to search inputs
            const searchInputs = document.querySelectorAll('.search-input');
            searchInputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = '';
                });
            });

            // Search button click animation
            searchBtn.addEventListener('click', function(e) {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });

            // Real-time search with debouncing
            const debouncedSearch = debounce(function() {
                performSearch();
            }, 300);

            searchInput.addEventListener('input', debouncedSearch);

            // Add hover effects to company badges and salary badges
            const companyBadges = document.querySelectorAll('.company-badge');
            const salaryBadges = document.querySelectorAll('.salary-badge');

            companyBadges.forEach(badge => {
                badge.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.05)';
                });
                
                badge.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                });
            });

            salaryBadges.forEach(badge => {
                badge.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.05)';
                });
                
                badge.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                });
            });

            // Add click animations to view buttons
            const viewButtons = document.querySelectorAll('.view-btn');
            viewButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    // Add ripple effect
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.cssText = `
                        position: absolute;
                        width: ${size}px;
                        height: ${size}px;
                        left: ${x}px;
                        top: ${y}px;
                        background: rgba(37, 99, 235, 0.3);
                        border-radius: 50%;
                        transform: scale(0);
                        animation: ripple 0.4s linear;
                        pointer-events: none;
                    `;
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 400);
                });
            });

            // Results count animation
            const resultsCount = document.getElementById('resultsCount');
            if (resultsCount) {
                resultsCount.style.animationDelay = '0.2s';
            }

            // Add scroll-triggered animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.animationPlayState = 'running';
                        
                        // Add staggered animation to child elements
                        const children = entry.target.querySelectorAll('.job-title, .company-badge, .salary-badge, .view-btn');
                        children.forEach((child, index) => {
                            setTimeout(() => {
                                child.style.opacity = '1';
                                child.style.transform = 'translateY(0)';
                            }, index * 100);
                        });
                    }
                });
            }, observerOptions);

            // Observe job cards for scroll animations
            jobCards.forEach(card => {
                observer.observe(card);
            });

            // Add floating animation to hero elements
            const heroTitle = document.querySelector('.hero-title');
            const heroSubtitle = document.querySelector('.hero-subtitle');
            
            if (heroTitle && heroSubtitle) {
                // Add subtle glow effect on hover
                heroTitle.addEventListener('mouseenter', function() {
                    this.style.textShadow = '0 0 20px rgba(255, 255, 255, 0.5)';
                });
                
                heroTitle.addEventListener('mouseleave', function() {
                    this.style.textShadow = '';
                });
            }

            // Add loading animation for form submission
            searchForm.addEventListener('submit', function(e) {
                const loadingSpinner = document.getElementById('loadingSpinner');
                const jobResults = document.getElementById('jobResults');
                
                if (loadingSpinner && jobResults) {
                    // Show loading spinner with fade animation
                    loadingSpinner.style.display = 'block';
                    loadingSpinner.style.opacity = '0';
                    loadingSpinner.style.transform = 'translateY(20px)';
                    
                    setTimeout(() => {
                        loadingSpinner.style.opacity = '1';
                        loadingSpinner.style.transform = 'translateY(0)';
                    }, 100);
                    
                    // Hide job results with fade out
                    jobResults.style.opacity = '0';
                    jobResults.style.transform = 'translateY(-20px)';
                }
            });

            // Add smooth scroll to top when filters are applied
            const clearFiltersLink = document.querySelector('.clear-filters');
            if (clearFiltersLink) {
                clearFiltersLink.addEventListener('click', function(e) {
                    // Smooth scroll to top
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            }
        });

        // Function to filter job cards with animation
        function filterJobCards(category) {
            const jobCards = document.querySelectorAll('.job-card');
            const resultsCount = document.getElementById('resultsCount');
            let visibleCount = 0;

            jobCards.forEach((card, index) => {
                const jobTitle = card.querySelector('.job-title').textContent.toLowerCase();
                const shouldShow = category === '' || jobTitle.includes(category.toLowerCase());
                
                if (shouldShow) {
                    visibleCount++;
                    card.style.display = 'block';
                    card.style.animation = 'fadeInUp 0.4s ease-out';
                    card.style.animationDelay = `${index * 0.05}s`;
                } else {
                    card.style.animation = 'fadeOut 0.2s ease-out';
                    setTimeout(() => {
                        card.style.display = 'none';
                    }, 200);
                }
            });

            // Update results count with animation
            if (resultsCount) {
                resultsCount.style.animation = 'bounceIn 0.4s ease-out';
                setTimeout(() => {
                    resultsCount.innerHTML = `<i class="fas fa-briefcase me-2"></i>${visibleCount} opportunities found`;
                }, 200);
            }
        }

        // Function to perform search
        function performSearch() {
            const searchInput = document.getElementById('searchInput');
            const searchTerm = searchInput.value.toLowerCase();
            const jobCards = document.querySelectorAll('.job-card');
            const resultsCount = document.getElementById('resultsCount');
            let visibleCount = 0;

            jobCards.forEach((card, index) => {
                const jobTitle = card.querySelector('.job-title').textContent.toLowerCase();
                const companyName = card.querySelector('.company-badge').textContent.toLowerCase();
                const jobLocation = card.querySelector('.job-location').textContent.toLowerCase();
                
                const shouldShow = jobTitle.includes(searchTerm) || 
                                 companyName.includes(searchTerm) || 
                                 jobLocation.includes(searchTerm);
                
                if (shouldShow) {
                    visibleCount++;
                    card.style.display = 'block';
                    card.style.animation = 'fadeInUp 0.4s ease-out';
                    card.style.animationDelay = `${index * 0.05}s`;
                } else {
                    card.style.animation = 'fadeOut 0.2s ease-out';
                    setTimeout(() => {
                        card.style.display = 'none';
                    }, 200);
                }
            });

            // Update results count
            if (resultsCount) {
                resultsCount.style.animation = 'bounceIn 0.4s ease-out';
                setTimeout(() => {
                    resultsCount.innerHTML = `<i class="fas fa-briefcase me-2"></i>${visibleCount} opportunities found`;
                }, 200);
            }
        }

        // Add CSS for ripple effect
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            @keyframes fadeOut {
                from {
                    opacity: 1;
                    transform: translateY(0);
                }
                to {
                    opacity: 0;
                    transform: translateY(-20px);
                }
            }
            
            .job-card .job-title,
            .job-card .company-badge,
            .job-card .salary-badge,
            .job-card .view-btn {
                opacity: 0;
                transform: translateY(20px);
                transition: all 0.6s ease-out;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>