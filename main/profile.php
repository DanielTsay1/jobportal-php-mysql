<?php
session_start();
require_once '../php/db.php';

$username = $_SESSION['username'] ?? 'Guest';

// Fetch user profile information
$stmt = $conn->prepare("SELECT * FROM user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($username ?? '') ?>'s Profile</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="/img/favicon.ico" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<?php include 'header.php'; ?>
<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="bg-light rounded p-4 mb-4">
                <div class="text-center">
                    <img src="<?= htmlspecialchars($user['profile_picture'] ?? '/img/profile-placeholder.jpg') ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="width: 120px; height: 120px;">
                    <h4 class="mb-0"><?= htmlspecialchars($username ?? '') ?></h4>
                    <p class="text-muted">Job Seeker</p>
                    <button class="btn btn-primary btn-sm mt-3" id="edit-profile-picture">Edit Picture</button>
                    <form id="profile-picture-form" action="/php/upload-profile.php" method="POST" enctype="multipart/form-data" style="display: none;">
                        <input type="file" name="profile_picture" id="profile_picture" class="form-control mb-3">
                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    </form>
                </div>
                <hr>
                <form id="resume-form" action="/php/upload-resume.php" method="POST" enctype="multipart/form-data">
                    <label for="resume" class="form-label">Upload Resume:</label>
                    <input type="file" name="resume" id="resume" class="form-control mb-3">
                    <button type="submit" class="btn btn-primary btn-sm">Upload Resume</button>
                </form>
                <hr>
                <div id="contact-details">
                    <p>
                        <i class="fa fa-envelope text-primary me-2"></i>
                        <span id="email"><?= htmlspecialchars($user['email'] ?? '') ?></span>
                        <button class="btn btn-sm btn-link edit-contact" data-field="email">Edit</button>
                    </p>
                    <p>
                        <i class="fa fa-phone-alt text-primary me-2"></i>
                        <span id="phone"><?= htmlspecialchars($user['phone'] ?? '') ?></span>
                        <button class="btn btn-sm btn-link edit-contact" data-field="phone">Edit</button>
                    </p>
                    <p>
                        <i class="fa fa-map-marker-alt text-primary me-2"></i>
                        <span id="location"><?= htmlspecialchars($user['location'] ?? '') ?></span>
                        <button class="btn btn-sm btn-link edit-contact" data-field="location">Edit</button>
                    </p>
                    <p>
                        <i class="fa fa-globe text-primary me-2"></i>
                        <span id="website"><?= htmlspecialchars($user['website'] ?? '') ?></span>
                        <button class="btn btn-sm btn-link edit-contact" data-field="website">Edit</button>
                    </p>
                </div>
            </div>
        </div>
        <!-- Main Content -->
        <div class="col-lg-8">
            <div class="bg-light rounded p-4 mb-4">
                <h4 class="mb-4">About</h4>
                <p id="about"><?= htmlspecialchars($user['about'] ?? 'Write something about yourself...') ?></p>
                <button class="btn btn-sm btn-link edit-section" data-section="about">Edit</button>
                <button class="btn btn-sm btn-primary save-section" data-section="about" style="display: none;">Save</button>
            </div>
            <div class="bg-light rounded p-4 mb-4">
                <h4 class="mb-4">Education</h4>
                <div id="education">
                    <?= htmlspecialchars($user['education'] ?? 'Add your education details...') ?>
                </div>
                <button class="btn btn-sm btn-link edit-section" data-section="education">Edit</button>
                <button class="btn btn-sm btn-primary add-entry" data-section="education" style="display: none;">+ Add</button>
                <button class="btn btn-sm btn-primary save-section" data-section="education" style="display: none;">Save</button>
            </div>
            <div class="bg-light rounded p-4 mb-4">
                <h4 class="mb-4">Experience</h4>
                <div id="experience">
                    <?= htmlspecialchars($user['experience'] ?? 'Add your work experience...') ?>
                </div>
                <button class="btn btn-sm btn-link edit-section" data-section="experience">Edit</button>
                <button class="btn btn-sm btn-primary add-entry" data-section="experience" style="display: none;">+ Add</button>
                <button class="btn btn-sm btn-primary save-section" data-section="experience" style="display: none;">Save</button>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function () {
    // Toggle profile picture form
    $('#edit-profile-picture').click(function () {
        $('#profile-picture-form').toggle();
        $(this).toggle();
    });

    // Inline editing for contact details
    $('.edit-contact').click(function () {
        const field = $(this).data('field');
        const value = $(`#${field}`).text();
        const input = `<input type="text" id="${field}-input" value="${value}" class="form-control mb-2">`;
        const saveButton = `<button class="btn btn-primary btn-sm save-contact" data-field="${field}">Save</button>`;
        $(`#${field}`).html(input + saveButton);
        $(this).hide();
    });

    // Save contact details
    $(document).on('click', '.save-contact', function () {
        const field = $(this).data('field');
        const value = $(`#${field}-input`).val();
        $.post('/php/update-contact.php', { field, value }, function (response) {
            if (response.trim() === "success" || response.trim() === "nochange") {
                $(`#${field}`).html(value);
                $(`.edit-contact[data-field="${field}"]`).show();
            } else {
                alert("Failed to update. Please try again.");
            }
        });
    });

    // Inline editing for sections
    $('.edit-section').click(function () {
        const section = $(this).data('section');
        const value = $(`#${section}`).html();
        let content = '';
        if (section === 'about') {
            content = `<textarea id="${section}-textarea" class="form-control mb-2">${$('<div>').html(value).text()}</textarea>`;
        } else {
            // Split by <br> for multi-entry fields
            const entries = value === '' || value.includes('Add your') ? [''] : value.split('<br>');
            content = '';
            entries.forEach(function (entry) {
                content += `
                    <div class="entry-row mb-2">
                        <textarea class="form-control d-inline-block" style="width:85%">${$('<div>').html(entry).text().trim()}</textarea>
                        <button type="button" class="btn btn-danger btn-sm delete-entry" style="vertical-align:top;" title="Delete">&times;</button>
                    </div>`;
            });
        }
        $(`#${section}`).html(content);
        if (section !== 'about') {
            $(`.add-entry[data-section="${section}"]`).show();
        }
        $(`.save-section[data-section="${section}"]`).show();
        $(this).hide();
    });

    // Add new entry for education or experience
    $('.add-entry').click(function () {
        const section = $(this).data('section');
        const newEntry = `
            <div class="entry-row mb-2">
                <textarea class="form-control d-inline-block" style="width:85%" placeholder="Add new ${section} entry"></textarea>
                <button type="button" class="btn btn-danger btn-sm delete-entry" style="vertical-align:top;" title="Delete">&times;</button>
            </div>`;
        $(`#${section}`).append(newEntry);
    });

    // Delete entry for education or experience
    $(document).on('click', '.delete-entry', function () {
        $(this).closest('.entry-row').remove();
    });

    // Save sections
    $('.save-section').click(function () {
        const section = $(this).data('section');
        let entries = [];
        if (section === 'about') {
            entries = [$(`#${section}-textarea`).val()];
        } else {
            $(`#${section} textarea`).each(function () {
                entries.push($(this).val());
            });
        }
        $.post('/php/update-section.php', { section, entries }, function (response) {
            $(`#${section}`).html(entries.join('<br>'));
            $(`.edit-section[data-section="${section}"]`).show();
            $(`.add-entry[data-section="${section}"], .save-section[data-section="${section}"]`).hide();
        });
    });
});
</script>
</body>
</html>