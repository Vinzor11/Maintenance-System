<?php
session_start();
require 'db.php';
require 'vendor/autoload.php'; // For PHPMailer via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if (isset($_GET['deleteid'])) {
    $request_id = intval($_GET['deleteid']);
    $stmt = $pdo->prepare("DELETE FROM maintenance_requests WHERE id = ?");
    $stmt->execute([$request_id]);
    header("Location: admin_update_requests.php?deleted=1");
    exit;
}

// For feedback messages
$msg = '';
$msgType = 'info';

// Function to send email with timeout protection
function sendMaintenanceEmail($emailTo, $username, $requestId, $requestTitle, $newStatus) {
    $mail = new PHPMailer(true);
    try {
        // Set timeout limits to prevent hanging
        $mail->Timeout = 10; // Connection timeout (seconds)
        $mail->SMTPDebug = 0; // Disable debug output
        
        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = true;
        $mail->Username = "abenalesbrix06@gmail.com"; // change to your address
        $mail->Password = "phwptrtzvgvpjtvo"; // use app password or your config
        $mail->SMTPSecure = "tls";
        $mail->Port = 587;
        
        // Additional timeout settings
        $mail->SMTPKeepAlive = false; // Don't keep connection alive
        $mail->SMTPAutoTLS = false; // Disable automatic TLS
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        
        $mail->setFrom("abenalesbrix06@gmail.com", "Maintenance Team");
        
        // Basic email validation before attempting to send
        if (!filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email format'];
        }
        
        $mail->addAddress($emailTo, $username);
        $mail->Subject = "Maintenance Request #$requestId Status Update";
        $mail->Body = "Hello $username,\n\n"
            ."Status of request \"$requestTitle\" has been updated to: $newStatus\n\n"
            ."Please log in for more details.";
        
        $mail->send();
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

// ------- SINGLE USER EMAIL (Send Update) ---------
if (isset($_POST['single_notify_id'])) {
    $requestId = intval($_POST['single_notify_id']);

    // Get request and user email
    $stmt = $pdo->prepare("SELECT r.title AS reqtitle, r.status AS reqstatus, r.id AS reqid, u.email AS reqemail, u.username AS requsername
                           FROM maintenance_requests r JOIN users u ON r.user_id = u.id
                           WHERE r.id = ?");
    $stmt->execute([$requestId]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($req && !empty($req['reqemail'])) {
        $result = sendMaintenanceEmail(
            $req['reqemail'], 
            $req['requsername'], 
            $req['reqid'], 
            $req['reqtitle'], 
            $req['reqstatus']
        );
        
        if ($result['success']) {
            $msg = "User notified successfully!";
            $msgType = 'success';
        } else {
            $msg = "Failed to notify user: " . $result['error'];
            $msgType = 'warning';
        }
    } else {
        $msg = "Could not find user for that request.";
        $msgType = 'warning';
    }
}

// ------- BULK UPDATE & EMAIL ---------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $success = 0;
    $fail = 0;
    $errors = [];
    
    // Refresh data after single update for up-to-date statuses.
    $stmt = $pdo->query("SELECT r.*, u.email, u.username FROM maintenance_requests r JOIN users u ON r.user_id = u.id");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($_POST['update'] as $reqId => $new_status) {
        if ($new_status) {
            // Update database first
            $pdo->prepare("UPDATE maintenance_requests SET status=? WHERE id=?")
                ->execute([$new_status, $reqId]);
            
            // Get current user info for this request
            $req = null;
            foreach ($requests as $r) {
                if ($r['id'] == $reqId) { $req = $r; break; }
            }
            
            if ($req && !empty($req['email'])) {
                $result = sendMaintenanceEmail(
                    $req['email'], 
                    $req['username'], 
                    $req['id'], 
                    $req['title'], 
                    $new_status
                );
                
                if ($result['success']) {
                    $success++;
                } else {
                    $fail++;
                    $errors[] = "Request #{$req['id']}: " . $result['error'];
                }
            }
        }
    }
    
    $msg = "$success request(s) updated and users notified.";
    if ($fail > 0) {
        $msg .= " $fail notification(s) failed.";
        if (count($errors) > 0 && count($errors) <= 3) {
            $msg .= " Errors: " . implode("; ", $errors);
        }
    }
    $msgType = $fail > 0 ? 'warning' : 'success';
    
    // Refresh the data
    $stmt = $pdo->query("SELECT r.*, u.email, u.username FROM maintenance_requests r JOIN users u ON r.user_id = u.id");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->query("SELECT r.*, u.email, u.username FROM maintenance_requests r JOIN users u ON r.user_id = u.id");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Calculate statistics
$total = count($requests);
$submitted = count(array_filter($requests, fn($r) => $r['status'] == 'Submitted'));
$inProgress = count(array_filter($requests, fn($r) => $r['status'] == 'In Progress'));
$completed = count(array_filter($requests, fn($r) => $r['status'] == 'Completed'));
$rejected = count(array_filter($requests, fn($r) => $r['status'] == 'Rejected'));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Request Status Update</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 50px;
        }
        
        .page-header {
            background: white;
            border-radius: 15px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .page-header h2 {
            margin: 0;
            color: #2d3748;
            font-weight: 700;
            display: flex;
            align-items: center;
        }
        .page-header h2 i {
            margin-right: 15px;
            color: #667eea;
        }
        
        .stats-row {
            margin-bottom: 30px;
        }
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border-left: 4px solid;
            min-height: 100px;
        }
        .stats-card:hover {
            transform: translateY(-3px);
        }
        .stats-card.total { border-left-color: #667eea; }
        .stats-card.submitted { border-left-color: #0d6efd; }
        .stats-card.progress { border-left-color: #ffc107; }
        .stats-card.completed { border-left-color: #198754; }
        .stats-card.rejected { border-left-color: #dc3545; }
        
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
        }
        .stats-card.total .stats-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stats-card.submitted .stats-icon { background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); }
        .stats-card.progress .stats-icon { background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); }
        .stats-card.completed .stats-icon { background: linear-gradient(135deg, #198754 0%, #146c43 100%); }
        .stats-card.rejected .stats-icon { background: linear-gradient(135deg, #dc3545 0%, #bb2d3b 100%); }
        
        .stats-number {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
            line-height: 1;
        }
        .stats-label {
            color: #718096;
            font-size: 12px;
            margin: 5px 0 0 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }
        
        .main-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            overflow: hidden;
            margin-bottom: 40px;
        }
        .card-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 30px;
            border: none;
        }
        .card-header-custom h4 {
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        .card-header-custom h4 i {
            margin-right: 12px;
        }
        
        .table-container {
            padding: 30px;
        }
        .table {
            margin-bottom: 0;
        }
        .table thead th {
            background-color: #f8f9fa;
            color: #2d3748;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dee2e6;
            padding: 15px 12px;
            white-space: nowrap;
        }
        .table thead th i {
            margin-right: 5px;
            color: #667eea;
        }
        .table tbody tr {
            transition: background-color 0.2s ease;
        }
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .table td {
            vertical-align: middle;
            padding: 15px 12px;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
        }
        .status-badge i {
            margin-right: 5px;
        }
        .status-badge.submitted {
            background-color: #cfe2ff;
            color: #084298;
        }
        .status-badge.in-progress {
            background-color: #fff3cd;
            color: #664d03;
        }
        .status-badge.completed {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .status-badge.rejected {
            background-color: #f8d7da;
            color: #842029;
        }
        
        .btn-custom {
            border-radius: 8px;
            font-weight: 500;
            padding: 10px 24px;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-back {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            margin-bottom: 20px;
        }
        .btn-back:hover {
            background: #667eea;
            color: white;
        }
        
        .alert-custom {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 15px 20px;
            display: flex;
            align-items: center;
        }
        .alert-custom i {
            font-size: 20px;
            margin-right: 12px;
        }
        
        .form-select {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            transition: all 0.3s ease;
        }
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-notify {
            background: linear-gradient(135deg, #00d2ff 0%, #3a7bd5 100%);
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-notify:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(58, 123, 213, 0.4);
            color: white;
        }
        
        .btn-update-all {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 14px 40px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .btn-update-all:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .email-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
        }
        .email-badge i {
            margin-right: 5px;
            color: #667eea;
        }
        
        /* Loading overlay */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .loading-overlay.active {
            display: flex;
        }
        .loading-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
        }
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media(max-width: 768px) {
            .stats-card {
                margin-bottom: 15px;
            }
            .page-header h2 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <p style="margin: 0; font-weight: 600; color: #2d3748;">Sending notifications...</p>
        <p style="margin: 5px 0 0 0; font-size: 14px; color: #718096;">Please wait</p>
    </div>
</div>

<div class="container-fluid" style="max-width:1400px;margin-top:40px;padding:0 20px;">
    <a href="admin_dashboard.php" class="btn btn-back btn-custom">
        <i class="fas fa-arrow-left me-2"></i>Back to Admin Dashboard
    </a>
    
    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="fas fa-sync-alt"></i>Update Maintenance Request Status</h2>
    </div>
    
    <!-- Alert Messages -->
    <?php if ($msg): ?>
        <div id="timed-alert" class="alert alert-<?= $msgType ?> alert-custom">
            <i class="fas fa-<?= $msgType == 'success' ? 'check-circle' : ($msgType == 'danger' ? 'exclamation-circle' : 'info-circle') ?>"></i>
            <span><?= htmlspecialchars($msg) ?></span>
        </div>
        <script>
        setTimeout(function() {
            var el = document.getElementById('timed-alert');
            if (el) {
                el.style.opacity = '0';
                el.style.transition = 'opacity 0.5s';
                setTimeout(() => el.remove(), 500);
            }
        }, 5000);
        </script>
    <?php endif; ?>
    
    <!-- Statistics Cards -->
    <div class="row stats-row">
        <div class="col-lg col-md-4 col-sm-6">
            <div class="stats-card total">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stats-label">Total Requests</p>
                        <h3 class="stats-number"><?= $total ?></h3>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-sm-6">
            <div class="stats-card submitted">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stats-label">Submitted</p>
                        <h3 class="stats-number"><?= $submitted ?></h3>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-sm-6">
            <div class="stats-card progress">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stats-label">In Progress</p>
                        <h3 class="stats-number"><?= $inProgress ?></h3>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-spinner"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-sm-6">
            <div class="stats-card completed">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stats-label">Completed</p>
                        <h3 class="stats-number"><?= $completed ?></h3>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-sm-6">
            <div class="stats-card rejected">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stats-label">Rejected</p>
                        <h3 class="stats-number"><?= $rejected ?></h3>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Table Card -->
    <div class="main-card">
        <div class="card-header-custom">
            <h4><i class="fas fa-bell"></i>Status Update & Notification Center</h4>
        </div>
        <div class="table-container">
            <form method="POST" onsubmit="showLoading()">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="display:none;">ID</th>
                                <th><i class="fas fa-heading"></i>Title</th>
                                <th><i class="fas fa-user"></i>User</th>
                                <th><i class="fas fa-envelope"></i>Email</th>
                                <th><i class="fas fa-flag"></i>Current Status</th>
                                <th><i class="fas fa-sync-alt"></i>Update Status</th>
                                <th><i class="fas fa-paper-plane"></i>Notify</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $req): ?>
                            <tr>
                                <td style="display:none"><?= htmlspecialchars($req['id']) ?></td>
                                <td><strong><?= htmlspecialchars($req['title']) ?></strong></td>
                                <td>
                                    <i class="fas fa-user-circle me-2" style="color: #667eea;"></i>
                                    <?= htmlspecialchars($req['username']) ?>
                                </td>
                                <td>
                                    <span class="email-badge">
                                        <i class="fas fa-envelope"></i>
                                        <?= htmlspecialchars($req['email']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = strtolower(str_replace(' ', '-', $req['status']));
                                    $statusIcons = [
                                        'submitted' => 'fa-paper-plane',
                                        'in-progress' => 'fa-spinner',
                                        'completed' => 'fa-check-circle',
                                        'rejected' => 'fa-times-circle'
                                    ];
                                    $icon = $statusIcons[$statusClass] ?? 'fa-flag';
                                    ?>
                                    <span class="status-badge <?= $statusClass ?>">
                                        <i class="fas <?= $icon ?>"></i>
                                        <?= htmlspecialchars($req['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <select name="update[<?= $req['id'] ?>]" class="form-select">
                                        <option value="">-- No Change --</option>
                                        <option value="Submitted" <?= ($req['status'] == 'Submitted' ? 'selected' : '') ?>>Submitted</option>
                                        <option value="In Progress" <?= ($req['status'] == 'In Progress' ? 'selected' : '') ?>>In Progress</option>
                                        <option value="Completed" <?= ($req['status'] == 'Completed' ? 'selected' : '') ?>>Completed</option>
                                        <option value="Rejected" <?= ($req['status'] == 'Rejected' ? 'selected' : '') ?>>Rejected</option>
                                    </select>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;" onsubmit="showLoading()">
                                        <input type="hidden" name="single_notify_id" value="<?= $req['id'] ?>">
                                        <button type="submit" class="btn btn-notify">
                                            <i class="fas fa-paper-plane me-1"></i>Send Update
                                        </button>
                                        <a href="admin_update_requests.php?deleteid=<?= $req['id'] ?>" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Are you sure you want to delete?')">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </a>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-update-all">
                        <i class="fas fa-sync-alt me-2"></i>Update All & Notify Users
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showLoading() {
    document.getElementById('loadingOverlay').classList.add('active');
}
</script>
</body>
</html>