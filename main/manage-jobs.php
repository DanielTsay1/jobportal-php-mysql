<?php
session_start();
require_once '../php/db.php';

// Check if user is a recruiter
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'A' || !isset($_SESSION['compid'])) {
    header('Location: /main/login.php');
    exit;
}

$compid = $_SESSION['compid'];

// Get status from GET param, default to 'Active'
$status_filter = $_GET['status'] ?? 'Active';

// Get recruiter info
$stmt = $conn->prepare("SELECT * FROM recruiter WHERE username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$recruiter = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get company info by compid
$company = null;
if ($compid) {
    $stmt = $conn->prepare("SELECT * FROM company WHERE compid = ?");
    $stmt->bind_param("i", $compid);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Handle actions (delete, unpost, repost, edit)
if (isset($_GET['action'], $_GET['jobid'])) {
    $jobid = intval($_GET['jobid']);
    // All actions require a simple redirect back to the current view
    $redirect_url = "manage-jobs.php?status=" . urlencode($status_filter);
    
    if ($_GET['action'] === 'delete') {
        $stmt = $conn->prepare("DELETE FROM `job-post` WHERE jobid = ? AND compid = ?");
        $stmt->bind_param("ii", $jobid, $compid);
        $stmt->execute();
        header("Location: $redirect_url");
        exit;
    }
    if ($_GET['action'] === 'unpost') {
        $stmt = $conn->prepare("UPDATE `job-post` SET status = 'Inactive' WHERE jobid = ? AND compid = ?");
        $stmt->bind_param("ii", $jobid, $compid);
        $stmt->execute();
        header("Location: $redirect_url");
        exit;
    }
    if ($_GET['action'] === 'repost') {
        $stmt = $conn->prepare("UPDATE `job-post` SET status = 'Active' WHERE jobid = ? AND compid = ?");
        $stmt->bind_param("ii", $jobid, $compid);
        $stmt->execute();
        header("Location: $redirect_url");
        exit;
    }
}

// Handle edit (POST)
if (isset($_POST['edit_jobid'])) {
    $jobid = intval($_POST['edit_jobid']);
    $designation = $_POST['designation'] ?? '';
    $location = $_POST['location'] ?? '';
    $salary = $_POST['salary'] ?? '';
    $description = $_POST['description'] ?? '';
    $spots = intval($_POST['spots'] ?? 1);
    $stmt = $conn->prepare("UPDATE `job-post` SET designation=?, location=?, salary=?, description=?, spots=? WHERE jobid=? AND compid=?");
    $stmt->bind_param("ssdsiii", $designation, $location, $salary, $description, $spots, $jobid, $compid);
    $stmt->execute();
    header("Location: manage-jobs.php?status=" . urlencode($status_filter));
    exit;
}

// Fetch jobs based on status filter
$jobs = [];
$sql = "SELECT jp.*, (SELECT COUNT(*) FROM applied WHERE jobid = jp.jobid) AS applicant_count 
        FROM `job-post` jp 
        WHERE jp.compid = ?";
$params = [$compid];
$types = 'i';

if ($status_filter !== 'All') {
    $sql .= " AND jp.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
$sql .= " ORDER BY jp.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $jobs[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Jobs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        .nav-pills .nav-link.active { background-color: #0d6efd; color: white; }
        .nav-pills .nav-link { color: #0d6efd; }
        .badge-inactive { background: #adb5bd; }
        .modal-header { background: #f8f9fa; }
        .table thead th { vertical-align: middle; }
        .spots-badge { background: #0d6efd; }
        .applicant-badge { background: #20c997; }
    </style>
</head>
<body>
<?php include 'header-recruiter.php'; ?>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="fa fa-briefcase me-2"></i>Manage Jobs</h2>
        <a href="post-job.php" class="btn btn-primary"><i class="fa fa-plus me-2"></i>Post New Job</a>
    </div>

    <!-- Status Filter Pills -->
    <ul class="nav nav-pills mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $status_filter === 'Active' ? 'active' : '' ?>" href="?status=Active">Active</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $status_filter === 'Inactive' ? 'active' : '' ?>" href="?status=Inactive">Inactive</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $status_filter === 'All' ? 'active' : '' ?>" href="?status=All">All</a>
        </li>
    </ul>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if (count($jobs) > 0): ?>
                <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3">Job Title</th>
                            <th>Applicants</th>
                            <th>Spots Left</th>
                            <th>Status</th>
                            <th>Posted On</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td class="px-4">
                                <strong><?= htmlspecialchars($job['designation']) ?></strong>
                                <div class="text-muted small"><i class="fa fa-map-marker-alt me-1"></i><?= htmlspecialchars($job['location']) ?> | <i class="fa fa-dollar-sign ms-2 me-1"></i><?= htmlspecialchars($job['salary']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <i class="fa fa-users me-1"></i> <?= $job['applicant_count'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-primary">
                                    <?= max(0, $job['spots'] - $job['applicant_count']) ?> / <?= $job['spots'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $job['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= htmlspecialchars($job['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y', strtotime($job['created_at'])) ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $job['jobid'] ?>">
                                    <i class="fa fa-edit me-1"></i>Edit
                                </button>
                                <?php if ($job['status'] === 'Active'): ?>
                                    <a href="?action=unpost&jobid=<?= $job['jobid'] ?>&status=<?= urlencode($status_filter) ?>" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Unpost this job?');">
                                        <i class="fa fa-eye-slash me-1"></i>Unpost
                                    </a>
                                <?php else: ?>
                                    <a href="?action=repost&jobid=<?= $job['jobid'] ?>&status=<?= urlencode($status_filter) ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Repost this job?');">
                                        <i class="fa fa-undo me-1"></i>Repost
                                    </a>
                                <?php endif; ?>
                                <a href="?action=delete&jobid=<?= $job['jobid'] ?>&status=<?= urlencode($status_filter) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to permanently delete this job and all its applications?');">
                                    <i class="fa fa-trash me-1"></i>Delete
                                </a>
                            </td>
                        </tr>
                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?= $job['jobid'] ?>" tabindex="-1" aria-hidden="true">
                          <div class="modal-dialog">
                            <form method="post" class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title"><i class="fa fa-edit me-2"></i>Edit Job</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                              </div>
                              <div class="modal-body">
                                <input type="hidden" name="edit_jobid" value="<?= $job['jobid'] ?>">
                                <div class="mb-3"><label class="form-label">Job Title</label><input type="text" name="designation" class="form-control" value="<?= htmlspecialchars($job['designation']) ?>" required></div>
                                <div class="mb-3"><label class="form-label">Location</label><input type="text" name="location" class="form-control" value="<?= htmlspecialchars($job['location']) ?>" required></div>
                                <div class="mb-3"><label class="form-label">Salary</label><input type="number" name="salary" class="form-control" value="<?= htmlspecialchars($job['salary']) ?>" required></div>
                                <div class="mb-3"><label class="form-label">Spots Available</label><input type="number" name="spots" class="form-control" value="<?= htmlspecialchars($job['spots']) ?>" min="1" required></div>
                                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4" required><?= htmlspecialchars($job['description']) ?></textarea></div>
                              </div>
                              <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                              </div>
                            </form>
                          </div>
                        </div>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info m-4">No jobs found for the '<?= htmlspecialchars($status_filter) ?>' filter.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>