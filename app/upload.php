<?php
require 'includes/db.php';
require 'includes/auth.php';
require 'includes/functions.php';

requireResearcher();
$user = currentUser();

$UPLOAD_DIR = '/var/www/researchportal/uploads/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type           = $_POST['upload_type'] ?? '';
    $title          = trim($_POST['title'] ?? '');
    $classification = $_POST['classification'] ?? 'public';
    $description    = trim($_POST['description'] ?? '');

    if (!in_array($classification,
        ['public','confidential'])) {
        $classification = 'public';
    }

    if (empty($title)) {
        setFlash('error', 'Please provide a title.');
        header("Location: /upload.php");
        exit();
    }

    if (!isset($_FILES['file']) ||
        $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
        setFlash('error', 'Please select a file.');
        header("Location: /upload.php");
        exit();
    }

    $err = validateUpload($_FILES['file']);
    if ($err) {
        setFlash('error', $err);
        header("Location: /upload.php");
        exit();
    }

    $filename = generateFilename($_FILES['file']['name']);
    $dest     = $UPLOAD_DIR . $filename;

    if (!move_uploaded_file(
        $_FILES['file']['tmp_name'], $dest)) {
        setFlash('error', 'Failed to save file.');
        header("Location: /upload.php");
        exit();
    }

    if ($type === 'proposal') {
        $stmt = $pdo->prepare(
            "INSERT INTO proposals
             (title, researcher_id, file_path,
              status, classification)
             VALUES (?, ?, ?, 'pending', ?)"
        );
        $stmt->execute([
            $title, $user['id'],
            $filename, $classification
        ]);

        if ($classification === 'confidential') {
            $log = $pdo->prepare(
                "INSERT INTO research_access_log
                 (user_id, document_type, action, ip_address)
                 VALUES (?, 'confidential',
                 'proposal_upload', ?)"
            );
            $log->execute([
                $user['id'], $_SERVER['REMOTE_ADDR']
            ]);
            setFlash('success',
                '🔴 Confidential proposal submitted!');
        } else {
            setFlash('success',
                '🟢 Public proposal submitted!');
        }
    } else {
        if ($classification === 'confidential') {
            $stmt = $pdo->prepare(
                "INSERT INTO sensitive_documents
                 (doc_name, description,
                  uploader_id, file_path)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([
                $title,
                $description ?: 'Confidential document',
                $user['id'], $filename
            ]);
            $log = $pdo->prepare(
                "INSERT INTO research_access_log
                 (user_id, document_type, action, ip_address)
                 VALUES (?, 'confidential', 'doc_upload', ?)"
            );
            $log->execute([
                $user['id'], $_SERVER['REMOTE_ADDR']
            ]);
            setFlash('success',
                '🔴 Confidential document stored securely!');
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO documents
                 (doc_name, uploader_id, file_path)
                 VALUES (?, ?, ?)"
            );
            $stmt->execute([
                $title, $user['id'], $filename
            ]);
            setFlash('success',
                '🟢 Document uploaded successfully!');
        }
    }

    error_log("UPLOAD: " . $user['username'] .
              " | " . $classification .
              " | " . $filename .
              " | IP: " . $_SERVER['REMOTE_ADDR']);

    header("Location: /dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Upload Research — ResearchPortal</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="app-layout">
    <?php require 'includes/nav.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-title">
                <h1>📤 Upload Research</h1>
                <p>Submit proposals or upload research documents</p>
            </div>
        </div>

        <div class="page-content">
            <?php showFlash(); ?>

            <div style="display:grid;
                        grid-template-columns:1fr 340px;
                        gap:24px;align-items:start;">

                <!-- Upload Form -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h2>Upload File</h2>
                            <div class="card-subtitle">
                                PDF or DOCX — Max 5MB
                            </div>
                        </div>
                    </div>

                    <form method="POST"
                          action="/upload.php"
                          enctype="multipart/form-data">

                        <div class="form-group">
                            <label>Upload Type</label>
                            <select name="upload_type"
                                    required>
                                <option value="proposal">
                                    📋 Research Proposal
                                </option>
                                <option value="document">
                                    📁 Project Document
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Classification Level</label>
                            <div style="display:grid;
                                grid-template-columns:1fr 1fr;
                                gap:10px;margin-top:6px;">
                                <label style="border:2px solid
                                    var(--border);border-radius:10px;
                                    padding:14px;cursor:pointer;
                                    transition:all 0.2s;"
                                    id="lbl-public">
                                    <input type="radio"
                                           name="classification"
                                           value="public"
                                           checked
                                           onchange="setClass('public')"
                                           style="display:none">
                                    <div style="text-align:center;">
                                        <div style="font-size:24px;
                                            margin-bottom:6px;">🟢</div>
                                        <div style="font-weight:700;
                                            font-size:13px;">Public</div>
                                        <div style="font-size:11px;
                                            color:var(--gray);
                                            margin-top:3px;">
                                            Visible to all researchers
                                        </div>
                                    </div>
                                </label>
                                <label style="border:2px solid
                                    var(--border);border-radius:10px;
                                    padding:14px;cursor:pointer;
                                    transition:all 0.2s;"
                                    id="lbl-confidential">
                                    <input type="radio"
                                           name="classification"
                                           value="confidential"
                                           onchange="setClass('confidential')"
                                           style="display:none">
                                    <div style="text-align:center;">
                                        <div style="font-size:24px;
                                            margin-bottom:6px;">🔴</div>
                                        <div style="font-weight:700;
                                            font-size:13px;">Confidential</div>
                                        <div style="font-size:11px;
                                            color:var(--gray);
                                            margin-top:3px;">
                                            Isolated — admin only
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Title</label>
                            <input type="text"
                                   name="title"
                                   placeholder="Enter research title"
                                   required maxlength="200">
                        </div>

                        <div class="form-group">
                            <label>Description
                                <span style="color:var(--gray);
                                    font-weight:400;">
                                    (optional)
                                </span>
                            </label>
                            <textarea name="description"
                                      rows="3"
                                      placeholder="Brief description of this research..."
                                      style="resize:vertical;"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Select File</label>
                            <div style="border:2px dashed
                                var(--border);border-radius:10px;
                                padding:24px;text-align:center;
                                cursor:pointer;"
                                onclick="document.getElementById('fileInput').click()">
                                <div style="font-size:32px;
                                    margin-bottom:8px;">📎</div>
                                <div style="font-weight:600;
                                    color:var(--dark);
                                    font-size:14px;">
                                    Click to select file
                                </div>
                                <div style="font-size:12px;
                                    color:var(--gray);
                                    margin-top:4px;">
                                    PDF, DOCX — Max 5MB
                                </div>
                                <input type="file"
                                       id="fileInput"
                                       name="file"
                                       accept=".pdf,.docx"
                                       required
                                       style="display:none"
                                       onchange="showFile(this)">
                                <div id="fileName"
                                     style="margin-top:10px;
                                     font-size:13px;
                                     color:var(--primary);
                                     font-weight:600;
                                     display:none;"></div>
                            </div>
                        </div>

                        <div style="display:flex;gap:12px;">
                            <button type="submit"
                                    class="btn btn-primary"
                                    style="flex:1;
                                    justify-content:center;
                                    padding:12px;">
                                Upload →
                            </button>
                            <a href="/dashboard.php"
                               class="btn btn-secondary"
                               style="padding:12px 20px;">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Info panel -->
                <div>
                    <div class="card">
                        <h2 style="font-size:14px;
                            margin-bottom:16px;">
                            📋 Upload Guidelines
                        </h2>
                        <div style="font-size:13px;
                            color:var(--gray);
                            line-height:1.8;">
                            <div style="margin-bottom:12px;">
                                <strong style="color:var(--dark);">
                                    Accepted Formats
                                </strong><br>
                                PDF (.pdf) and Word (.docx) only
                            </div>
                            <div style="margin-bottom:12px;">
                                <strong style="color:var(--dark);">
                                    File Size Limit
                                </strong><br>
                                Maximum 5MB per file
                            </div>
                            <div style="margin-bottom:12px;">
                                <strong style="color:var(--dark);">
                                    🟢 Public Research
                                </strong><br>
                                Visible to all authenticated
                                researchers on the portal
                            </div>
                            <div>
                                <strong style="color:#ef4444;">
                                    🔴 Confidential Research
                                </strong><br>
                                Stored in isolated database table.
                                Only you and administrators can
                                access. All access is logged.
                            </div>
                        </div>
                    </div>

                    <div class="card"
                         style="background:linear-gradient(
                         135deg,#fef2f2,#fff5f5);
                         border-color:#fecaca;">
                        <div style="font-size:13px;
                            color:#991b1b;">
                            <strong>🔒 Security Notice</strong><br>
                            All uploads are scanned and logged.
                            Confidential documents are stored in
                            a separate isolated table with strict
                            access controls and audit trail.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function setClass(val) {
    const pubLbl = document.getElementById('lbl-public');
    const confLbl = document.getElementById('lbl-confidential');
    if (val === 'public') {
        pubLbl.style.borderColor = 'var(--success)';
        pubLbl.style.background = '#f0fdf4';
        confLbl.style.borderColor = 'var(--border)';
        confLbl.style.background = '';
    } else {
        confLbl.style.borderColor = 'var(--danger)';
        confLbl.style.background = '#fff5f5';
        pubLbl.style.borderColor = 'var(--border)';
        pubLbl.style.background = '';
    }
}
function showFile(input) {
    const el = document.getElementById('fileName');
    if (input.files && input.files[0]) {
        el.textContent = '✓ ' + input.files[0].name;
        el.style.display = 'block';
    }
}
// Set initial state
setClass('public');
</script>
</body>
</html>
