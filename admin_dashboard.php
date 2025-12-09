<?php
session_start();
require 'db.php';

// Check admin
if (!isset($_SESSION['userid']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

if (isset($_GET['deleteid'])) {
    $id = intval($_GET['deleteid']);
    // Delete main request (and rely on ON DELETE CASCADE for request_workers, comments, files, etc, if set up)
    $stmt = $pdo->prepare("DELETE FROM maintenance_requests WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: admin_dashboard.php?deleted=1");
    exit;
}


// Worker assignment logic
if (isset($_POST['assign_workers_btn'], $_POST['assign_request_id'])) {
    $req_id = intval($_POST['assign_request_id']);
    $worker_ids = $_POST['worker_ids'] ?? [];

    // Remove previous assignments
    $pdo->prepare("DELETE FROM request_workers WHERE request_id = ?")->execute([$req_id]);

    // Assign selected workers
    $stmtAdd = $pdo->prepare("INSERT INTO request_workers (request_id, worker_id) VALUES (?, ?)");
    foreach ($worker_ids as $wid) {
        $stmtAdd->execute([$req_id, intval($wid)]);
    }
    header("Location: admin_dashboard.php?worker_assigned=1");
    exit;
}

// Get all workers from 'workers' table
$stmtW = $pdo->prepare("SELECT id, name FROM workers ORDER BY name ASC");
$stmtW->execute();
$workers = $stmtW->fetchAll(PDO::FETCH_ASSOC);

// Get requests
$systemFilter = $_GET['system'] ?? 'All';
$params = [];
$sql = "SELECT requests.*, users.username AS requester FROM maintenance_requests requests JOIN users ON users.id = requests.user_id";
if ($systemFilter !== 'All') {
    $sql .= " WHERE system_type = ?";
    $params[] = $systemFilter;
}
$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

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
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    body {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        min-height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .menu-btn {
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1051;
        background: white;
        border: none;
        outline: none;
        cursor: pointer;
        padding: 12px 15px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
    }
    .menu-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    }
    .menu-btn i {
        font-size: 24px;
        color: #1e3c72;
    }
    
    .side-nav {
        position: fixed;
        top: 0;
        left: -280px;
        width: 280px;
        height: 100%;
        background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
        color: #fff;
        transition: left 0.3s ease;
        z-index: 1052;
        padding-top: 80px;
        box-shadow: 4px 0 20px rgba(0,0,0,0.3);
    }
    .side-nav.show {
        left: 0 !important;
    }
    .side-nav a {
        display: flex;
        align-items: center;
        padding: 16px 25px;
        color: #e0e0e0;
        text-decoration: none;
        font-size: 1rem;
        margin: 5px 15px;
        border-radius: 10px;
        transition: all 0.3s ease;
    }
    .side-nav a i {
        margin-right: 12px;
        font-size: 18px;
        width: 25px;
        text-align: center;
    }
    .side-nav a:hover {
        background: rgba(255,255,255,0.1);
        color: #fff;
        transform: translateX(5px);
    }
    .side-nav .close-nav {
        position: absolute;
        top: 20px;
        right: 20px;
        font-size: 28px;
        color: #fff;
        background: none;
        border: none;
        cursor: pointer;
        transition: transform 0.3s ease;
    }
    .side-nav .close-nav:hover {
        transform: rotate(90deg);
    }
    .side-nav .nav-header {
        padding: 0 25px 20px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        margin-bottom: 20px;
    }
    .side-nav .nav-header h5 {
        color: #fff;
        font-weight: 600;
        margin: 0;
    }
    
    .main-header {
        background: white;
        border-radius: 15px;
        padding: 25px 30px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .main-header h2 {
        margin: 0;
        color: #1e3c72;
        font-weight: 700;
        display: flex;
        align-items: center;
    }
    .main-header h2 i {
        margin-right: 15px;
        color: #2a5298;
    }
    
    .stats-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-left: 5px solid;
        min-height: 130px;
    }
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    .stats-card.total { border-left-color: #667eea; }
    .stats-card.submitted { border-left-color: #0d6efd; }
    .stats-card.progress { border-left-color: #ffc107; }
    .stats-card.completed { border-left-color: #198754; }
    .stats-card.rejected { border-left-color: #dc3545; }
    
    .stats-icon {
        width: 65px;
        height: 65px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: white;
    }
    .stats-card.total .stats-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .stats-card.submitted .stats-icon { background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); }
    .stats-card.progress .stats-icon { background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); }
    .stats-card.completed .stats-icon { background: linear-gradient(135deg, #198754 0%, #146c43 100%); }
    .stats-card.rejected .stats-icon { background: linear-gradient(135deg, #dc3545 0%, #bb2d3b 100%); }
    
    .stats-number {
        font-size: 36px;
        font-weight: 700;
        color: #2d3748;
        margin: 0;
        line-height: 1;
    }
    .stats-label {
        color: #718096;
        font-size: 13px;
        margin: 8px 0 0 0;
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
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
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
        color: #6c757d;
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
    
    .badge {
        padding: 6px 12px;
        font-weight: 500;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .system-badge {
        background-color: #e9ecef;
        color: #495057;
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
    }
    .system-badge i {
        margin-right: 5px;
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
    
    .btn-sm {
        padding: 5px 12px;
        font-size: 12px;
        border-radius: 6px;
    }
    
    .comment-box {
        background-color: #f8f9fa;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 8px;
        border-left: 3px solid #1e3c72;
        font-size: 13px;
    }
    .comment-box:last-child {
        margin-bottom: 0;
    }
    
    .attachment-link {
        color: #1e3c72;
        text-decoration: none;
        font-size: 13px;
        display: inline-block;
        margin-bottom: 5px;
        transition: color 0.2s;
    }
    .attachment-link:hover {
        color: #2a5298;
        text-decoration: underline;
    }
    
    .alert-success {
        border-radius: 10px;
        border: none;
        box-shadow: 0 4px 15px rgba(25, 135, 84, 0.2);
    }
    
    .modal-content {
        border-radius: 15px;
        border: none;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    }
    .modal-header {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: white;
        border-radius: 15px 15px 0 0;
        padding: 20px 25px;
    }
    .modal-header .btn-close {
        filter: invert(1);
    }
    .modal-body {
        padding: 25px;
    }
    .modal-footer {
        padding: 20px 25px;
        border-top: 1px solid #dee2e6;
    }
    
    @media(max-width: 768px) {
        .side-nav {
            width: 80vw;
            min-width: 250px;
        }
        .main-header {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }
        .stats-card {
            margin-bottom: 15px;
        }
    }

    
    </style>
</head>
<body>

<button class="menu-btn" onclick="document.getElementById('sideNav').classList.add('show');">
    <i class="fas fa-bars"></i>
</button>

<div class="side-nav" id="sideNav">
    <button class="close-nav" onclick="document.getElementById('sideNav').classList.remove('show');">&times;</button>
    <div class="nav-header">
        <h5><i class="fas fa-shield-alt me-2"></i>Admin Menu</h5>
    </div>
    <a href="admin_dashboard.php">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a href="accomplishment_matrix.php">
        <i class="fas fa-chart-bar"></i> Accomplishment Matrix
    </a>
    <a href="admin_update_requests.php">
        <i class="fas fa-bell"></i> Update & Notify Status
    </a>
    <a href="manage_workers.php">
        <i class="fas fa-user-cog"></i> Manage Workers
    </a>
</div>

<script>
  document.addEventListener('click', function(e) {
      var nav=document.getElementById('sideNav');
      var btn=document.querySelector('.menu-btn');
      if(nav.classList.contains('show') && !nav.contains(e.target) && !btn.contains(e.target)){
          nav.classList.remove('show');
      }
  },true);
</script>

<div class="container-fluid" style="max-width:1600px;margin-top:40px;margin-bottom:40px;padding-left:80px;padding-right:30px;">
    
    <!-- Header -->
    <div class="main-header">
        <h2><i class="fas fa-shield-alt"></i>Admin Dashboard</h2>
        <a class="btn btn-danger btn-custom" href="logout.php">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
        </a>
    </div>
    
    <?php if (isset($_GET['worker_assigned'])): ?>
        <div class="alert alert-success fade show" id="success-alert">
            <i class="fas fa-check-circle me-2"></i>Workers Assigned Successfully.
        </div>
        <script>
            setTimeout(function() {
                var alert = document.getElementById('success-alert');
                if (alert) {
                    alert.classList.remove('show');
                    setTimeout(() => alert.remove(), 300);
                }
            }, 2500);
        </script>
    <?php endif; ?>

    <div class="card-header-custom">
    <div class="d-flex justify-content-between align-items-center">
        <h4><i class="fas fa-list"></i>All Maintenance Requests</h4>
        <form method="GET" class="d-flex align-items-center">
            <label class="me-2 mb-0" style="white-space: nowrap;">
                <i class="fas fa-filter me-1"></i>Filter by System:
            </label>
            <select name="system" class="form-select form-select-sm me-2" style="width: auto;" onchange="this.form.submit()">
                <option value="All" <?= $systemFilter == 'All' ? 'selected' : '' ?>>All Systems</option>
                <option value="Electrical" <?= $systemFilter == 'Electrical' ? 'selected' : '' ?>>Electrical</option>
                <option value="Plumbing" <?= $systemFilter == 'Plumbing' ? 'selected' : '' ?>>Plumbing</option>
                <option value="Sound" <?= $systemFilter == 'Sound' ? 'selected' : '' ?>>Sound</option>
            </select>
        </form>
    </div>
</div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
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
            <h4><i class="fas fa-list"></i>All Maintenance Requests</h4>
        </div>
        <div class="table-container">
            <?php if (!$requests): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox" style="font-size: 64px; color: #cbd5e0;"></i>
                    <h5 class="text-muted mt-3">No requests found for filter</h5>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="display:none;">ID</th>
                            <th><i class="fas fa-user"></i>Submitted By</th>
                            <th><i class="fas fa-building"></i>Department</th>
                            <th><i class="fas fa-heading"></i>Title</th>
                            <th><i class="fas fa-exclamation-triangle"></i>Emergency</th>
                            <th><i class="fas fa-cog"></i>System</th>
                            <th><i class="fas fa-flag"></i>Status</th>
                            <th><i class="fas fa-calendar"></i>Submitted</th>
                            <th><i class="fas fa-tasks"></i>Actions</th>
                            <th><i class="fas fa-users"></i>Assigned Workers</th>
                            <th><i class="fas fa-comments"></i>Com/His</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $r): ?>
                        <tr>
                            <td style="display:none;"><?= $r['id'] ?></td>
                            <td><strong><?= htmlspecialchars($r['requester']) ?></strong></td>
                            <td>
                                <?php if (!empty($r['department'])): ?>
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-building me-1"></i><?= htmlspecialchars($r['department']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($r['title']) ?></td>
                            <td>
                                <?php if (!empty($r['emergency_flag'])): ?>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-bolt me-1"></i>EMERGENCY
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
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
                                    <i class="fas <?= $icon ?>"></i>
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
                                <a class="btn btn-sm btn-outline-primary mb-1" href="manage_request.php?id=<?= $r['id'] ?>">
                                    <i class="fas fa-edit me-1"></i>Manage
                                </a>
                                <a class="btn btn-sm btn-outline-danger mb-1" href="admin_dashboard.php?deleteid=<?= $r['id'] ?>" onclick="return confirm('Are you sure you want to delete this request?')">
                                    <i class="fas fa-trash me-1"></i>Delete
                                </a>
                                <?php
                                    $stmtF = $pdo->prepare("SELECT * FROM request_files WHERE request_id = ?");
                                    $stmtF->execute([$r['id']]);
                                    foreach ($stmtF->fetchAll() as $f) {
                                        echo "<a class='attachment-link d-block' target='_blank' href='" . htmlspecialchars($f['file_path']) . "'><i class='fas fa-file me-1'></i>" . htmlspecialchars($f['original_name']) . "</a>";
                                    }
                                ?>
                                <button type="button" class="btn btn-sm btn-outline-success mb-1" data-bs-toggle="modal" data-bs-target="#assignWorkerModal<?= $r['id'] ?>">
                                    <i class="fas fa-user-plus me-1"></i>Assign Workers
                                </button>
                            </td>
                            <td>
                                <?php
                                $stmtAW = $pdo->prepare("
                                    SELECT workers.name FROM request_workers 
                                    JOIN workers ON request_workers.worker_id = workers.id 
                                    WHERE request_workers.request_id = ?
                                ");
                                $stmtAW->execute([$r['id']]);
                                $assignedWorkers = $stmtAW->fetchAll(PDO::FETCH_COLUMN);
                                if ($assignedWorkers) {
                                    foreach ($assignedWorkers as $worker) {
                                        echo "<span class='badge bg-info text-dark me-1 mb-1'><i class='fas fa-user me-1'></i>" . htmlspecialchars($worker) . "</span>";
                                    }
                                } else {
                                    echo '<span class="text-muted"><i class="fas fa-minus-circle me-1"></i>None</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                    $stmtC = $pdo->prepare("SELECT comments.comment, comments.created_at, users.username FROM comments JOIN users ON users.id=comments.user_id WHERE comments.request_id = ? ORDER BY comments.created_at DESC");
                                    $stmtC->execute([$r['id']]);
                                    foreach ($stmtC->fetchAll() as $c) {
                                        echo "<div class='comment-box'><strong><i class='fas fa-user-circle me-1'></i>" . htmlspecialchars($c['username']) . ":</strong> " . htmlspecialchars($c['comment']) . "<br><small class='text-muted'><i class='far fa-clock me-1'></i>" . date("Y-m-d H:i", strtotime($c['created_at'])) . "</small></div>";
                                    }
                                    if ($stmtC->rowCount() == 0) echo "<span class='text-muted'><i class='fas fa-minus-circle me-1'></i>None</span>";
                                ?>
                            </td>
                        </tr>
                        <!-- Modal for assigning workers -->
                        <div class="modal fade" id="assignWorkerModal<?= $r['id'] ?>" tabindex="-1"
                             aria-labelledby="assignWorkerModalLabel<?= $r['id'] ?>" aria-hidden="true">
                          <div class="modal-dialog">
                            <div class="modal-content">
                              <form method="POST">
                                <div class="modal-header">
                                  <h5 class="modal-title" id="assignWorkerModalLabel<?= $r['id'] ?>">
                                      <i class="fas fa-user-plus me-2"></i>Assign Worker(s) to Request #<?= $r['id'] ?>
                                  </h5>
                                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                  <input type="hidden" name="assign_request_id" value="<?= $r['id'] ?>">
                                  <label for="workers<?= $r['id'] ?>" class="form-label">
                                      <i class="fas fa-users me-2"></i>Select Worker(s):
                                  </label>
                                  <select multiple class="form-select" id="workers<?= $r['id'] ?>" name="worker_ids[]" size="6">
                                    <?php foreach($workers as $w): ?>
                                      <option value="<?= $w['id'] ?>"
                                        <?php if (in_array($w['name'], $assignedWorkers)) echo 'selected'; ?>>
                                          <?= htmlspecialchars($w['name']) ?>
                                      </option>
                                    <?php endforeach; ?>
                                  </select>
                                  <div class="form-text">
                                      <i class="fas fa-info-circle me-1"></i>Hold Ctrl (Windows) or Command (Mac) to select multiple workers.
                                  </div>
                                </div>
                                <div class="modal-footer">
                                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                      <i class="fas fa-times me-1"></i>Cancel
                                  </button>
                                  <button type="submit" class="btn btn-primary" name="assign_workers_btn">
                                      <i class="fas fa-check me-1"></i>Assign
                                  </button>
                                </div>
                              </form>
                            </div>
                          </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>