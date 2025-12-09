<?php
// dashboard.php
session_start();
require 'db.php';
// Only allow logged-in users with 'user' role
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'user') {
    header('Location: index.php');
    exit;
}
$uid = $_SESSION['userid'];
$username = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$uid]);
$requests = $stmt->fetchAll();

// Calculate statistics
$total = count($requests);
$submitted = count(array_filter($requests, fn($r) => $r['status'] == 'Submitted'));
$inProgress = count(array_filter($requests, fn($r) => $r['status'] == 'In Progress'));
$completed = count(array_filter($requests, fn($r) => $r['status'] == 'Completed'));
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .dashboard-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .dashboard-header h2 {
            margin: 0;
            color: #2d3748;
            font-weight: 600;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid;
            min-height: 120px;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .stats-card.total { border-left-color: #667eea; }
        .stats-card.submitted { border-left-color: #0d6efd; }
        .stats-card.progress { border-left-color: #ffc107; }
        .stats-card.completed { border-left-color: #198754; }
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-left: 50px;
            color: white;
        }
        .stats-card.total .stats-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stats-card.submitted .stats-icon { background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); }
        .stats-card.progress .stats-icon { background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); }
        .stats-card.completed .stats-icon { background: linear-gradient(135deg, #198754 0%, #146c43 100%); }
        .stats-number {
            font-size: 32px;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
        }
        .stats-label {
            color: #718096;
            font-size: 14px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        .main-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
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
        }
        .table-container {
            padding: 30px;
        }
        .table {
            margin-bottom: 0;
        }
        .table thead th {
            background-color: #f7fafc;
            color: #2d3748;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
            padding: 15px;
        }
        .table tbody tr {
            transition: background-color 0.2s ease;
        }
        .table tbody tr:hover {
            background-color: #f7fafc;
        }
        .table td {
            vertical-align: middle;
            padding: 15px;
        }
        .badge {
            padding: 6px 12px;
            font-weight: 500;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .system-badge {
            background-color: #e2e8f0;
            color: #4a5568;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
        }
        .comment-box {
            background-color: #f7fafc;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 8px;
            border-left: 3px solid #667eea;
        }
        .comment-box:last-child {
            margin-bottom: 0;
        }
        .attachment-link {
            color: #667eea;
            text-decoration: none;
            font-size: 13px;
            display: inline-block;
            margin-bottom: 5px;
            transition: color 0.2s;
        }
        .attachment-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state i {
            font-size: 64px;
            color: #cbd5e0;
            margin-bottom: 20px;
        }
        .btn-custom {
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
<div class="container" style="max-width:1200px;margin-top:40px;margin-bottom:40px;">
    <!-- Header -->
    <div class="dashboard-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-user-circle me-2"></i>Welcome, <?= htmlspecialchars($username) ?></h2>
            </div>
            <div>
                <a class="btn btn-success btn-custom me-2" href="request_form.php">
                    <i class="fas fa-plus-circle me-2"></i>Submit New Request
                </a>
                <a class="btn btn-danger btn-custom" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
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
        <div class="col-md-3">
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
        <div class="col-md-3">
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
        <div class="col-md-3">
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
    </div>

    <!-- Main Table Card -->
    <div class="main-card">
        <div class="card-header-custom">
            <h4><i class="fas fa-list me-2"></i>Your Maintenance Requests</h4>
        </div>
        <div class="table-container">
            <?php if (!$requests): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h5 class="text-muted">No maintenance requests yet</h5>
                    <p class="text-muted">Click "Submit New Request" to create your first maintenance request.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="display:none;">ID</th>
                                <th><i class="fas fa-heading me-1"></i>Title</th>
                                <th><i class="fas fa-cog me-1"></i>System</th>
                                <th><i class="fas fa-flag me-1"></i>Status</th>
                                <th><i class="fas fa-calendar me-1"></i>Submitted</th>
                                <th><i class="fas fa-paperclip me-1"></i>Attachments</th>
                                <th><i class="fas fa-comments me-1"></i>Comments</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                 <td style="display:none;"><?= $r['id'] ?></td>
                                <td><?= htmlspecialchars($r['title']) ?></td>
                                <td>
                                    <span class="system-badge">
                                        <?php
                                        $systemIcons = [
                                            'Electrical' => 'fa-bolt',
                                            'Plumbing' => 'fa-wrench',
                                            'HVAC' => 'fa-wind',
                                            'Sound' => 'fa-volume-up',
                                            'Other' => 'fa-tools'
                                        ];
                                        $icon = $systemIcons[$r['system_type']] ?? 'fa-tools';
                                        ?>
                                        <i class="fas <?= $icon ?> me-1"></i>
                                        <?= htmlspecialchars($r['system_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $badge = ($r['status']=='Submitted' ? 'primary' : 
                                             ($r['status']=='In Progress' ? 'warning' :
                                             ($r['status']=='Completed' ? 'success' : 'danger')));
                                    ?>
                                    <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($r['status']) ?></span>
                                </td>
                                <td><?= date('Y-m-d H:i', strtotime($r['created_at'])) ?></td>
                                <td>
                                    <?php
                                    $stmtF = $pdo->prepare("SELECT * FROM request_files WHERE request_id = ?");
                                    $stmtF->execute([$r['id']]);
                                    foreach ($stmtF->fetchAll() as $f) {
                                        echo "<a class='attachment-link' target='_blank' href='" . htmlspecialchars($f['file_path']) . "'><i class='fas fa-file me-1'></i>" . htmlspecialchars($f['original_name']) . "</a><br>";
                                    }
                                    if ($stmtF->rowCount() == 0) echo "<span class='text-muted'><i class='fas fa-minus-circle me-1'></i>None</span>";
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $stmtC = $pdo->prepare("SELECT comments.comment, comments.created_at, users.username FROM comments JOIN users ON users.id=comments.user_id WHERE comments.request_id = ? ORDER BY comments.created_at DESC");
                                    $stmtC->execute([$r['id']]);
                                    foreach ($stmtC->fetchAll() as $c) {
                                        echo "<div class='comment-box'><strong><i class='fas fa-user me-1'></i>" . htmlspecialchars($c['username']) . ":</strong> " . htmlspecialchars($c['comment']) . "<br><small class='text-muted'><i class='far fa-clock me-1'></i>" . date("Y-m-d H:i", strtotime($c['created_at'])) . "</small></div>";
                                    }
                                    if ($stmtC->rowCount() == 0) echo "<span class='text-muted'><i class='fas fa-minus-circle me-1'></i>None</span>";
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>