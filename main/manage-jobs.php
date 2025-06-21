<?php
session_start();
require_once '../php/db.php';

$username = $_SESSION['username'] ?? '';
$compid = $_SESSION['compid'] ?? null;

// Get recruiter info
$stmt = $conn->prepare("SELECT * FROM recruiter WHERE username = ?");
$stmt->bind_param("s", $username);
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
if ($company && isset($_GET['action'], $_GET['jobid'])) {
    $jobid = intval($_GET['jobid']);
    if ($_GET['action'] === 'delete') {
        $stmt = $conn->prepare("DELETE FROM `job-post` WHERE jobid = ? AND compid = ?");
        $stmt->bind_param("ii", $jobid, $company['compid']);
        $stmt->execute();
        header("Location: manage-jobs.php");
        exit;
    }
    if ($_GET['action'] === 'unpost') {
        $stmt = $conn->prepare("UPDATE `job-post` SET status = 'Inactive' WHERE jobid = ? AND compid = ?");
        $stmt->bind_param("ii", $jobid, $company['compid']);
        $stmt->execute();
        header("Location: manage-jobs.php");
        exit;
    }
    if ($_GET['action'] === 'repost') {
        $stmt = $conn->prepare("UPDATE `job-post` SET status = 'Active' WHERE jobid = ? AND compid = ?");
        $stmt->bind_param("ii", $jobid, $company['compid']);
        $stmt->execute();
        header("Location: manage-jobs.php");
        exit;
    }
}

// Handle edit (POST)
if ($company && isset($_POST['edit_jobid'])) {
    $jobid = intval($_POST['edit_jobid']);
    $designation = $_POST['designation'] ?? '';
    $location = $_POST['location'] ?? '';
    $salary = $_POST['salary'] ?? '';
    $description = $_POST['description'] ?? '';
    $spots = intval($_POST['spots'] ?? 1);
    $stmt = $conn->prepare("UPDATE `job-post` SET designation=?, location=?, salary=?, description=?, spots=? WHERE jobid=? AND compid=?");
    $stmt->bind_param("ssdsiii", $designation, $location, $salary, $description, $spots, $jobid, $company['compid']);
    $stmt->execute();
    header("Location: manage-jobs.php");
    exit;
}

// Fetch all jobs for this company, with applicant count
$jobs = [];
if ($company) {
    $stmt = $conn->prepare("SELECT * FROM `job-post` WHERE compid = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $company['compid']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Get applicant count for this job
        $stmt2 = $conn->prepare("SELECT COUNT(*) AS applicant_count FROM applied WHERE jobid = ?");
        $stmt2->bind_param("i", $row['jobid']);
        $stmt2->execute();
        $count_result = $stmt2->get_result()->fetch_assoc();
        $row['applicant_count'] = $count_result['applicant_count'] ?? 0;
        $jobs[] = $row;
    }
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
        .badge-inactive { background: #adb5bd; }
        .modal-header { background: #f8f9fa; }
        .table thead th { vertical-align: middle; }
        .spots-badge { background: #0d6efd; }
        .applicant-badge { background: #20c997; }
    </style>
</head>
<body>
<?php include 'header-recruiter.php'; ?>
<div class="container py-4">
    <h2 class="mb-4"><i class="fa fa-briefcase"></i> Manage Jobs</h2>
    <a href="post-job.php" class="btn btn-primary mb-3"><i class="fa fa-plus"></i> Post New Job</a>
    <?php if (count($jobs) > 0): ?>
        <div class="table-responsive">
        <table class="table table-bordered align-middle shadow-sm">
            <thead class="table-light">
                <tr>
                    <th>Job Title</th>
                    <th>Location</th>
                    <th>Salary</th>
                    <th>Applicants</th>
                    <th>Spots</th>
                    <th>Status</th>
                    <th>Posted</th>
                    <th style="width:260px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($jobs as $job): ?>
                <tr>
                    <td>
                        <i class="fa fa-briefcase text-primary"></i>
                        <?= htmlspecialchars($job['designation']) ?>
                    </td>
                    <td><i class="fa fa-map-marker-alt text-danger"></i> <?= htmlspecialchars($job['location']) ?></td>
                    <td><i class="fa fa-dollar-sign text-success"></i> <?= htmlspecialchars($job['salary']) ?></td>
                    <td>
                        <span class="badge applicant-badge">
                            <i class="fa fa-users"></i> <?= $job['applicant_count'] ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge spots-badge" title="Spots available">
                            <i class="fa fa-user-plus"></i>
                            <?= isset($job['spots']) ? ($job['spots'] - $job['applicant_count']) . " / " . $job['spots'] : "1" ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $job['status'] === 'Active' ? 'bg-success' : 'badge-inactive' ?>">
                            <?= htmlspecialchars($job['status']) ?>
                        </span>
                    </td>
                    <td><?= date('M d, Y', strtotime($job['created_at'])) ?></td>
                    <td>
                        <!-- Edit Button triggers modal -->
                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $job['jobid'] ?>">
                            <i class="fa fa-edit"></i> Edit
                        </button>
                        <?php if ($job['status'] === 'Active'): ?>
                            <a href="manage-jobs.php?action=unpost&jobid=<?= $job['jobid'] ?>" class="btn btn-sm btn-secondary" onclick="return confirm('Unpost this job?');">
                                <i class="fa fa-eye-slash"></i> Unpost
                            </a>
                        <?php else: ?>
                            <a href="manage-jobs.php?action=repost&jobid=<?= $job['jobid'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Repost this job?');">
                                <i class="fa fa-undo"></i> Repost
                            </a>
                        <?php endif; ?>
                        <a href="manage-jobs.php?action=delete&jobid=<?= $job['jobid'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this job?');">
                            <i class="fa fa-trash"></i> Delete
                        </a>
                    </td>
                </tr>
                <!-- Edit Modal -->
                <div class="modal fade" id="editModal<?= $job['jobid'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $job['jobid'] ?>" aria-hidden="true">
                  <div class="modal-dialog">
                    <form method="post" class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel<?= $job['jobid'] ?>"><i class="fa fa-edit"></i> Edit Job</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <input type="hidden" name="edit_jobid" value="<?= $job['jobid'] ?>">
                        <div class="mb-3">
                            <label class="form-label">Job Title/Designation</label>
                            <input type="text" name="designation" class="form-control" value="<?= htmlspecialchars($job['designation']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($job['location']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Salary</label>
                            <input type="number" name="salary" class="form-control" value="<?= htmlspecialchars($job['salary']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Spots Available</label>
                            <input type="number" name="spots" class="form-control" value="<?= htmlspecialchars($job['spots'] ?? 1) ?>" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4" required><?= htmlspecialchars($job['description']) ?></textarea>
                        </div>
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
        <div class="alert alert-info">No jobs posted yet.</div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>