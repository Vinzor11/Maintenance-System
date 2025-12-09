<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require 'db.php';

// If you want to use email, require mailer. Comment if not needed.
require 'mailer.php';

// Only allow logged-in user role
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'user') {
    // AJAX support: send redirect in JSON
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['redirect' => 'login.php']);
        exit;
    }
    header('Location: login.php');
    exit;
}

// Input validation
$title   = trim($_POST['title'] ?? '');
$desc    = trim($_POST['description'] ?? '');
$system  = $_POST['system_type'] ?? '';
$uid     = $_SESSION['userid'];

$errors = [];

if (!$title)  $errors[] = "Title required.";
if (!$desc)   $errors[] = "Description required.";
if (!$system) $errors[] = "System type required.";

// Early exit on validation errors
if ($errors) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['error' => implode(', ', $errors)]);
        exit;
    }
    echo "<div class='alert alert-danger'>" . implode("<br>", $errors) . "</div>";
    exit;
}

// Save request to database
$stmt = $pdo->prepare("INSERT INTO maintenance_requests (user_id, title, description, system_type) VALUES (?, ?, ?, ?)");
$stmt->execute([$uid, $title, $desc, $system]);
$req_id = $pdo->lastInsertId();

// Handle file uploads if present
if (!empty($_FILES['files']['name'][0])) {
    $upload_dir = 'uploads/';
    foreach ($_FILES['files']['tmp_name'] as $i => $tmp) {
        if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $orig = basename($_FILES['files']['name'][$i]);
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $fname = uniqid().".".$ext;
        if (move_uploaded_file($tmp, $upload_dir.$fname)) {
            $stmtF = $pdo->prepare("INSERT INTO request_files(request_id,file_path,original_name) VALUES (?,?,?)");
            $stmtF->execute([$req_id, $upload_dir.$fname, $orig]);
        }
    }
}

// Send confirmation email (skip if you don't want email)
$stmtU = $pdo->prepare("SELECT email FROM users WHERE id=?");
$stmtU->execute([$uid]);
$email = $stmtU->fetchColumn();
if ($email && function_exists('sendMail')) {
    sendMail($email, "Request Submitted", "Your maintenance request has been received and logged.");
}

// Audit log (fix column count)
$stmtAudit = $pdo->prepare("INSERT INTO audit_logs(action,user_id,request_id,details) VALUES (?, ?, ?, ?)");
$stmtAudit->execute(['Request Submitted', $uid, $req_id, "$title | $system"]);

// AJAX support - return JSON response
if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    echo json_encode(['redirect' => 'dashboard.php']);
    exit;
}

// Non-AJAX/PHP fallback: Redirect to dashboard
header('Location: dashboard.php?msg=submitted');
exit;
?>
