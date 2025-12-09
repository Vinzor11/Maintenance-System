<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require 'db.php';

// Only allow logged-in non-admin users
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'user') {
    header('Location: login.php');
    exit;
}

// For non-JS fallback OR direct form POST handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $system = $_POST['system_type'] ?? '';
    $uid = $_SESSION['userid'];
    $department = $_SESSION['department'] ?? null; // Get department from session (set during SSO login)

    $errors = [];
    if (!$title) $errors[] = "Title required.";
    if (!$desc) $errors[] = "Description required.";
    if (!$system) $errors[] = "System type required.";

    if (empty($errors)) {
        // Insert the request (include department if available)
        $stmt = $pdo->prepare("INSERT INTO maintenance_requests (user_id, title, description, system_type, department) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$uid, $title, $desc, $system, $department]);
        $req_id = $pdo->lastInsertId();

        // Handle file uploads (if present)
        if (!empty($_FILES['files']['name'][0])) {
            $upload_dir = 'uploads/';
            foreach ($_FILES['files']['tmp_name'] as $i => $tmp) {
                if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $orig = basename($_FILES['files']['name'][$i]);
                $ext = pathinfo($orig, PATHINFO_EXTENSION);
                $fname = uniqid().'.'.$ext;
                if (move_uploaded_file($tmp, $upload_dir.$fname)) {
                    $stmtF = $pdo->prepare("INSERT INTO request_files(request_id, file_path, original_name) VALUES (?,?,?)");
                    $stmtF->execute([$req_id, $upload_dir.$fname, $orig]);
                }
            }
        }

        // (OPTIONAL: Add email notification here!)

        // For non-AJAX fallback: Redirect to dashboard after success
        header('Location: dashboard.php?msg=success');
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<title>Submit Maintenance Request</title>
</head>
<body class="container">
    <a class="btn btn-secondary" href="dashboard.php">Back</a>
<h2 class="mt-4">Submit Maintenance Request</h2>

<?php
// Show server-side validation errors
if (!empty($errors)) {
    echo "<div class='alert alert-danger'>" . implode("<br>", $errors) . "</div>";
}
?>

<form id="reqForm" method="POST" action="" enctype="multipart/form-data" autocomplete="off">
    <input class="form-control mb-2" type="text" name="title" required placeholder="Title"
        value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
    <textarea class="form-control mb-2" name="description" required placeholder="Description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
    <select class="form-select mb-2" name="system_type" required>
        <option value="">Select System</option>
        <option value="Electrical" <?= (($_POST['system_type'] ?? '') == "Electrical") ? "selected" : "" ?>>Electrical</option>
        <option value="Plumbing" <?= (($_POST['system_type'] ?? '') == "Plumbing") ? "selected" : "" ?>>Plumbing</option>
        <option value="Sound" <?= (($_POST['system_type'] ?? '') == "Sound") ? "selected" : "" ?>>Sound</option>
    </select>
    <input type="file" name="files[]" multiple class="form-control mb-2" id="fileInput" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx">
    <div id="preview" class="mb-2"></div>
    <!-- Add anywhere inside your <form> ... </form> (preferably before the submit button) -->

<div class="card my-3">
  <div class="card-header bg-light"><strong>Utilities - Electrical</strong></div>
  <div class="card-body row">
    <div class="col-md-4 mb-2">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="el_util[]" value="Air Conditioning" id="el1">
        <label class="form-check-label" for="el1">Air Conditioning</label>
      </div>
    </div>
    <div class="col-md-4 mb-2">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="el_util[]" value="Electric Fan" id="el2">
        <label class="form-check-label" for="el2">Electric Fan</label>
      </div>
    </div>
    <div class="col-md-4 mb-2">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="el_util[]" value="Lightings" id="el3">
        <label class="form-check-label" for="el3">Lightings</label>
      </div>
    </div>
    <div class="col-md-4 mb-2">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="el_util[]" value="Power Supply" id="el4">
        <label class="form-check-label" for="el4">Power Supply</label>
      </div>
    </div>
    <div class="col-md-4 mb-2">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="el_util[]" value="Switch/Outlets" id="el5">
        <label class="form-check-label" for="el5">Switch/Outlets</label>
      </div>
    </div>
    <div class="col-md-4 mb-2">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="el_util[]" value="Telephone" id="el6">
        <label class="form-check-label" for="el6">Telephone</label>
      </div>
    </div>
    <div class="col-md-4 mb-2">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="el_util[]" value="Wirings" id="el7">
        <label class="form-check-label" for="el7">Wirings</label>
      </div>
    </div>
    <div class="col-md-8 mb-2">
      <input class="form-control" type="text" name="el_util_other" placeholder="Other (specify)">
    </div>
  </div>
</div>

<div class="card my-3">
  <div class="card-header bg-light"><strong>Utilities - Plumbing</strong></div>
  <div class="card-body row">
    <div class="col-md-4 mb-2">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="plmb_util[]" value="Comfort Room" id="pl1">
        <label class="form-check-label" for="pl1">Comfort Room</label>
      </div>
    </div>
    <div class="col-md-4 mb-2">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="plmb_util[]" value="Faucet" id="pl2">
        <label class="form-check-label" for="pl2">Faucet</label>
      </div>
    </div>
    <div class="col-md-4 mb-2">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="plmb_util[]" value="Floor Drain" id="pl3">
        <label class="form-check-label" for="pl3">Floor Drain</label>
      </div>
    </div>
    <div class="col-md-4 mb-2">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="plmb_util[]" value="Lavatory" id="pl4">
        <label class="form-check-label" for="pl4">Lavatory</label>
      </div>
    </div>
    <div class="col-md-4 mb-2">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="plmb_util[]" value="Urinal" id="pl5">
        <label class="form-check-label" for="pl5">Urinal</label>
      </div>
    </div>
    <div class="col-md-4 mb-2">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="plmb_util[]" value="Water Closet" id="pl6">
        <label class="form-check-label" for="pl6">Water Closet</label>
      </div>
    </div>
    <div class="col-md-4 mb-2">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="plmb_util[]" value="Water Supply" id="pl7">
        <label class="form-check-label" for="pl7">Water Supply</label>
      </div>
    </div>
    <div class="col-md-8 mb-2">
      <input class="form-control" type="text" name="plmb_util_other" placeholder="Other (specify)">
    </div>
  </div>
</div>

    <button type="submit" class="btn btn-primary">Submit Request</button>
    <div class="progress mt-2" style="height:20px; display:none;">
        <div id="progBar" class="progress-bar" style="width:0%"></div>
    </div>
</form>

<script>
document.getElementById('fileInput').onchange = function(e) {
    let preview = document.getElementById('preview'); preview.innerHTML = "";
    Array.from(e.target.files).forEach(file => {
        let item = document.createElement('div');
        item.textContent = file.name; preview.appendChild(item);
    });
};

// Only use AJAX submit if JS is enabled
document.getElementById('reqForm').onsubmit = function(e) {
    // To support both JS and fallback, check for FormData and XMLHttpRequest support
    if (window.FormData && window.XMLHttpRequest) {
        e.preventDefault();
        let formData = new FormData(this);
        let xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.upload.onprogress = function(ev) {
            let percent = ev.lengthComputable ? ev.loaded/ev.total*100 : 0;
            document.querySelector('.progress').style.display='block';
            document.getElementById('progBar').style.width = percent + '%';
            document.getElementById('progBar').textContent = Math.round(percent) + '%';
        };
        xhr.onload = function() {
            if (xhr.status === 200) {
                // If the response contains a redirect, follow it
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.redirect) window.location = res.redirect;
                } catch {
                    alert('Request submitted');
                    window.location = "dashboard.php";
                }
            } else {
                alert('Submission failed! Try again or contact support.');
            }
        };
        xhr.send(formData);
    } // else, let the normal post occur so non-JS browsers still work
};
</script>
</body>
</html>
