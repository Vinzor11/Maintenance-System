<?php
session_start();
require 'db.php';

// Check admin
if (!isset($_SESSION['userid']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

// Add new worker
if (isset($_POST['add_worker'])) {
    $name = trim($_POST['worker_name']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO workers (name) VALUES (?)");
        $stmt->execute([$name]);
        header("Location: manage_workers.php?added=1");
        exit;
    }
}

// Delete worker
if (isset($_GET['deleteid'])) {
    $id = intval($_GET['deleteid']);
    $stmt = $pdo->prepare("DELETE FROM workers WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage_workers.php?deleted=1");
    exit;
}

// Edit worker
if (isset($_POST['edit_worker'])) {
    $id = intval($_POST['worker_id']);
    $name = trim($_POST['worker_name']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("UPDATE workers SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        header("Location: manage_workers.php?updated=1");
        exit;
    }
}

// Get all workers
$stmt = $pdo->prepare("SELECT * FROM workers ORDER BY name ASC");
$stmt->execute();
$workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Workers</title>
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
    .side-nav a.active {
        background: rgba(255,255,255,0.15);
        color: #fff;
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
    
    .main-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        overflow: hidden;
        margin-bottom: 30px;
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
    
    .card-body-custom {
        padding: 30px;
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
    
    .alert {
        border-radius: 10px;
        border: none;
    }
    .alert-success {
        box-shadow: 0 4px 15px rgba(25, 135, 84, 0.2);
    }
    .alert-danger {
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);
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
    
    .worker-count {
        background: rgba(255,255,255,0.2);
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        margin-left: 15px;
    }
    .worker-count i {
        margin-right: 8px;
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
    <a href="manage_workers.php" class="active">
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

<div class="container-fluid" style="max-width:1400px;margin-top:40px;margin-bottom:40px;padding-left:80px;padding-right:30px;">
    
    <!-- Header -->
    <div class="main-header">
        <h2><i class="fas fa-user-cog"></i>Manage Workers</h2>
        <a class="btn btn-danger btn-custom" href="logout.php">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
        </a>
    </div>
    
    <!-- Success/Error Messages -->
    <?php if (isset($_GET['added'])): ?>
        <div class="alert alert-success fade show" id="success-alert">
            <i class="fas fa-check-circle me-2"></i>Worker added successfully!
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success fade show" id="success-alert">
            <i class="fas fa-check-circle me-2"></i>Worker updated successfully!
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-danger fade show" id="success-alert">
            <i class="fas fa-trash-alt me-2"></i>Worker deleted successfully!
        </div>
    <?php endif; ?>
    
    <script>
        setTimeout(function() {
            var alert = document.getElementById('success-alert');
            if (alert) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 300);
            }
        }, 3000);
    </script>
    
    <!-- Add Worker Card -->
    <div class="main-card">
        <div class="card-header-custom">
            <h4><i class="fas fa-user-plus"></i>Add New Worker</h4>
        </div>
        <div class="card-body-custom">
            <form method="POST" class="row g-3">
                <div class="col-md-8">
                    <label for="worker_name" class="form-label">
                        <i class="fas fa-id-badge me-2"></i>Worker Name
                    </label>
                    <input type="text" class="form-control" id="worker_name" name="worker_name" 
                           placeholder="Enter worker name" required>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" name="add_worker" class="btn btn-primary btn-custom w-100">
                        <i class="fas fa-plus me-2"></i>Add Worker
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Workers List Card -->
    <div class="main-card">
        <div class="card-header-custom">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h4><i class="fas fa-users"></i>All Workers</h4>
                <span class="worker-count">
                    <i class="fas fa-user-check"></i>
                    <strong><?= count($workers) ?></strong> Worker<?= count($workers) != 1 ? 's' : '' ?>
                </span>
            </div>
        </div>
        <div class="card-body-custom">
            <?php if (empty($workers)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-user-slash" style="font-size: 64px; color: #cbd5e0;"></i>
                    <h5 class="text-muted mt-3">No workers found</h5>
                    <p class="text-muted">Add your first worker using the form above.</p>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="display:none;"><i class="fas fa-hashtag me-1"></i>ID</th>
                            <th><i class="fas fa-user me-1"></i>Worker Name</th>
                            <th style="width: 200px;"><i class="fas fa-tasks me-1"></i>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($workers as $worker): ?>
                        <tr>
                            <td style="display:none;"><strong><?= $worker['id'] ?></strong></td>
                            <td>
                                <i class="fas fa-user-circle me-2 text-primary"></i>
                                <?= htmlspecialchars($worker['name']) ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                        data-bs-toggle="modal" data-bs-target="#editModal<?= $worker['id'] ?>">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </button>
                                <a href="manage_workers.php?deleteid=<?= $worker['id'] ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Are you sure you want to delete this worker? This will also remove them from all assigned requests.')">
                                    <i class="fas fa-trash me-1"></i>Delete
                                </a>
                            </td>
                        </tr>
                        
                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?= $worker['id'] ?>" tabindex="-1" 
                             aria-labelledby="editModalLabel<?= $worker['id'] ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editModalLabel<?= $worker['id'] ?>">
                                                <i class="fas fa-edit me-2"></i>Edit Worker
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="worker_id" value="<?= $worker['id'] ?>">
                                            <div class="mb-3">
                                                <label for="edit_worker_name<?= $worker['id'] ?>" class="form-label">
                                                    <i class="fas fa-id-badge me-2"></i>Worker Name
                                                </label>
                                                <input type="text" class="form-control" 
                                                       id="edit_worker_name<?= $worker['id'] ?>" 
                                                       name="worker_name" 
                                                       value="<?= htmlspecialchars($worker['name']) ?>" 
                                                       required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                <i class="fas fa-times me-1"></i>Cancel
                                            </button>
                                            <button type="submit" name="edit_worker" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i>Save Changes
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