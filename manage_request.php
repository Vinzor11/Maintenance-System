<?php
// manage_request.php
session_start();
require 'db.php';

// Only allow admins to access this page
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Get the request ID from the URL
$request_id = $_GET['id'] ?? null;
if (!$request_id) {
    echo "<div class='alert alert-danger'>No request selected.</div>";
    exit;
}


// Get the current request
$stmt = $pdo->prepare("SELECT r.*, u.username FROM maintenance_requests r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
$stmt->execute([$request_id]);
$request = $stmt->fetch();

if (!$request) {
    echo "<div class='alert alert-danger'>Request not found.</div>";
    exit;
}

// Handle status update, comment, file upload, and cost tracking
$update_success = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update status
    $new_status = $_POST['status'] ?? $request['status'];
    if ($new_status !== $request['status']) {
        $stmt = $pdo->prepare("UPDATE maintenance_requests SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $request_id]);
        // Add audit log
        $pdo->prepare("INSERT INTO audit_logs(action, user_id, request_id, details) VALUES ('Status Updated', ?, ?, ?)")
            ->execute([$_SESSION['userid'], $request_id, "New status: $new_status"]);
        $update_success .= "Status updated. ";
    }

    // Update labor amount and total cost
    $labor_amount = trim($_POST['labor_amount'] ?? "");
    $total_cost = trim($_POST['total_cost'] ?? "");
    
    if ($labor_amount !== "" || $total_cost !== "") {
        $updateFields = [];
        $updateValues = [];
        
        if ($labor_amount !== "") {
            $updateFields[] = "labor_amount = ?";
            $updateValues[] = floatval($labor_amount);
        }
        if ($total_cost !== "") {
            $updateFields[] = "total_cost = ?";
            $updateValues[] = floatval($total_cost);
        }
        
        if (!empty($updateFields)) {
            $updateValues[] = $request_id;
            $sql = "UPDATE maintenance_requests SET " . implode(", ", $updateFields) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($updateValues);
            
            // Add audit log for cost update
            $costDetails = "";
            if ($labor_amount !== "") $costDetails .= "Labor: ₱" . number_format($labor_amount, 2);
            if ($total_cost !== "") $costDetails .= ($costDetails ? ", " : "") . "Total: ₱" . number_format($total_cost, 2);
            
            $pdo->prepare("INSERT INTO audit_logs(action, user_id, request_id, details) VALUES ('Cost Updated', ?, ?, ?)")
                ->execute([$_SESSION['userid'], $request_id, $costDetails]);
            
            $update_success .= "Cost information updated. ";
        }
        
        

    }
        $emergency_flag = isset($_POST['emergency_flag']) ? (int)$_POST['emergency_flag'] : 0;
        if ($emergency_flag != $request['emergency_flag']) {
        $stmt = $pdo->prepare("UPDATE maintenance_requests SET emergency_flag = ? WHERE id = ?");
        $stmt->execute([$emergency_flag, $request_id]);
        $pdo->prepare("INSERT INTO audit_logs (action, user_id, request_id, details) VALUES (?, ?, ?, ?)")
            ->execute(['Emergency Flag Updated', $_SESSION['userid'], $request_id, 'Emergency set to ' . $emergency_flag]);
        $update_success .= ' Emergency flag updated.';
        $request['emergency_flag'] = $emergency_flag;
        }

    // Add comment
    $comment = trim($_POST['comment'] ?? "");
    if ($comment !== "") {
        $stmt = $pdo->prepare("INSERT INTO comments (request_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$request_id, $_SESSION['userid'], $comment]);
        $update_success .= "Comment added. ";
    }

    // File upload
    if (!empty($_FILES['files']['name'][0])) {
        $upload_dir = 'uploads/';
        foreach ($_FILES['files']['tmp_name'] as $i => $tmp) {
            $orig = basename($_FILES['files']['name'][$i]);
            $ext = pathinfo($orig, PATHINFO_EXTENSION);
            $fname = uniqid() . "." . $ext;
            move_uploaded_file($tmp, $upload_dir . $fname);
            $stmtF = $pdo->prepare("INSERT INTO request_files(request_id, file_path, original_name) VALUES (?,?,?)");
            $stmtF->execute([$request_id, $upload_dir . $fname, $orig]);
        }
        $update_success .= "File(s) uploaded. ";
    }

    // Update request object to reflect changes
    $stmt = $pdo->prepare("SELECT r.*, u.username FROM maintenance_requests r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Request #<?= htmlspecialchars($request_id) ?> - Maintenance Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 50px;
        }
        
        .container {
            max-width: 900px;
            margin-top: 40px;
        }
        
        .btn-back {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            border-radius: 8px;
            font-weight: 500;
            padding: 10px 24px;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .main-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 30px;
            border: none;
        }
        .card-header-custom h3 {
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        .card-header-custom h3 i {
            margin-right: 12px;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .info-table {
            width: 100%;
            margin-bottom: 0;
        }
        .info-table tr {
            border-bottom: 1px solid #e9ecef;
        }
        .info-table tr:last-child {
            border-bottom: none;
        }
        .info-table th {
            font-weight: 600;
            color: #2d3748;
            padding: 15px 20px 15px 0;
            width: 180px;
            vertical-align: top;
        }
        .info-table th i {
            margin-right: 8px;
            color: #667eea;
        }
        .info-table td {
            padding: 15px 0;
            color: #4a5568;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
        }
        .status-badge i {
            margin-right: 6px;
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
        
        .cost-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 5px;
        }
        .cost-badge i {
            margin-right: 6px;
        }
        .cost-badge.labor {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        
        .file-link {
            display: inline-block;
            padding: 8px 16px;
            margin: 4px 4px 4px 0;
            background-color: #e0e7ff;
            color: #4338ca;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .file-link:hover {
            background-color: #c7d2fe;
            color: #3730a3;
            transform: translateY(-2px);
        }
        .file-link i {
            margin-right: 6px;
        }
        
        .comment-box {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 12px 16px;
            margin-bottom: 12px;
            border-radius: 8px;
        }
        .comment-box .comment-author {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 4px;
        }
        .comment-box .comment-author i {
            margin-right: 6px;
            color: #667eea;
        }
        .comment-box .comment-text {
            color: #4a5568;
            margin-bottom: 4px;
        }
        .comment-box .comment-time {
            font-size: 12px;
            color: #718096;
        }
        .comment-box .comment-time i {
            margin-right: 4px;
        }
        
        .update-section {
            background-color: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
        }
        .update-section h5 {
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .update-section h5 i {
            margin-right: 10px;
            color: #667eea;
        }
        
        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }
        .form-label i {
            margin-right: 6px;
            color: #667eea;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 10px 14px;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .input-group-text {
            background-color: #e9ecef;
            border: 1px solid #dee2e6;
            border-radius: 8px 0 0 8px;
            font-weight: 600;
        }
        .input-group .form-control {
            border-radius: 0 8px 8px 0;
        }
        
        .btn-update {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 40px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .btn-update:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .alert-custom {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .alert-custom i {
            font-size: 20px;
            margin-right: 12px;
        }
        
        .cost-input-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media(max-width: 768px) {
            .cost-input-group {
                grid-template-columns: 1fr;
            }
            .info-table th {
                width: 150px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="mb-4">
        <a class="btn btn-back" href="admin_dashboard.php">
            <i class="fas fa-arrow-left me-2"></i>Back to Admin Dashboard
        </a>
    </div>
    
    <?php if ($update_success): ?>
        <div id="timed-alert" class="alert alert-success alert-custom">
            <i class="fas fa-check-circle"></i>
            <span><?= $update_success ?></span>
        </div>
        <script>
        setTimeout(function() {
            var el = document.getElementById('timed-alert');
            if (el) {
                el.style.opacity = '0';
                el.style.transition = 'opacity 0.5s';
                setTimeout(() => el.remove(), 500);
            }
        }, 4000);
        </script>
    <?php endif; ?>
    
    <div class="main-card mb-3">
        <div class="card-header-custom">
            <h3><i class="fas fa-tools"></i>Manage Request #<?= $request['id'] ?></h3>
        </div>
        <div class="card-body">
            <table class="info-table">
                <tr>
                    <th><i class="fas fa-user"></i>Submitted By</th>
                    <td><?= htmlspecialchars($request['username']) ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-heading"></i>Title</th>
                    <td><strong><?= htmlspecialchars($request['title']) ?></strong></td>
                </tr>
                <tr>
                    <th><i class="fas fa-align-left"></i>Description</th>
                    <td><?= nl2br(htmlspecialchars($request['description'])) ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-cog"></i>System</th>
                    <td><?= htmlspecialchars($request['system_type']) ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-flag"></i>Status</th>
                    <td>
                        <?php
                        $statusClass = strtolower(str_replace(' ', '-', $request['status']));
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
                            <?= htmlspecialchars($request['status']) ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th><i class="fas fa-money-bill-wave"></i>Cost Information</th>
                    <td>
                        <?php
                        $labor = $request['labor_amount'] ?? 0;
                        $total = $request['total_cost'] ?? 0;
                        ?>
                        <?php if ($labor > 0): ?>
                            <span class="cost-badge labor">
                                <i class="fas fa-user-hard-hat"></i>Labor: ₱<?= number_format($labor, 2) ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($total > 0): ?>
                            <span class="cost-badge">
                                <i class="fas fa-calculator"></i>Total Cost of Work: ₱<?= number_format($total, 2) ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($labor <= 0 && $total <= 0): ?>
                            <span class="text-muted">No cost information yet</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                <th><i class="fas fa-exclamation-triangle"></i> Emergency</th>
                <td>
                    <?php if (!empty($request['emergency_flag'])): ?>
                    <span class="badge bg-danger"><i class="fas fa-bolt"></i> Emergency</span>
                    <?php else: ?>
                    <span class="text-muted">No</span>
                    <?php endif; ?>
                </td>
                </tr>
                <tr>
                    <th><i class="fas fa-clock"></i>Submitted</th>
                    <td><?= date("F j, Y - g:i A", strtotime($request['created_at'])) ?></td>
                </tr>
                <tr>
                    <th><i class="fas fa-paperclip"></i>Attachments</th>
                    <td>
                        <?php
                        $stmtF = $pdo->prepare("SELECT * FROM request_files WHERE request_id = ?");
                        $stmtF->execute([$request_id]);
                        $files = $stmtF->fetchAll();
                        if ($files) {
                            foreach ($files as $f) {
                                echo "<a class='file-link' target='_blank' href='" . htmlspecialchars($f['file_path']) . "'>";
                                echo "<i class='fas fa-file-download'></i>" . htmlspecialchars($f['original_name']);
                                echo "</a> ";
                            }
                        } else {
                            echo "<span class='text-muted'>No files attached</span>";
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><i class="fas fa-comments"></i>Comments & History</th>
                    <td>
                        <?php
                        $stmtC = $pdo->prepare("SELECT c.comment, c.created_at, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.request_id = ? ORDER BY c.created_at DESC");
                        $stmtC->execute([$request_id]);
                        $comments = $stmtC->fetchAll();
                        if ($comments) {
                            foreach ($comments as $c) {
                                echo "<div class='comment-box'>";
                                echo "<div class='comment-author'><i class='fas fa-user-circle'></i>" . htmlspecialchars($c['username']) . "</div>";
                                echo "<div class='comment-text'>" . htmlspecialchars($c['comment']) . "</div>";
                                echo "<div class='comment-time'><i class='far fa-clock'></i>" . date("F j, Y - g:i A", strtotime($c['created_at'])) . "</div>";
                                echo "</div>";
                            }
                        } else {
                            echo "<span class='text-muted'>No comments yet</span>";
                        }
                        ?>
                    </td>
                </tr>
                    
            </table>
            
            <div class="update-section">
                <h5><i class="fas fa-edit"></i>Update Request Information</h5>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-flag"></i>Update Status</label>
                        <select class="form-select" name="status">
                            <?php
                            $statuses = ['Submitted', 'In Progress', 'Completed', 'Rejected'];
                            foreach ($statuses as $status) {
                                $selected = ($request['status'] === $status) ? " selected" : "";
                                echo "<option value='$status'$selected>$status</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-exclamation-triangle"></i> Emergency Request</label>
                        <select class="form-select" name="emergency_flag">
                            <option value="0" <?php if (empty($request['emergency_flag'])) echo 'selected'; ?>>No</option>
                            <option value="1" <?php if (!empty($request['emergency_flag'])) echo 'selected'; ?>>Yes</option>
                        </select>
                        <small class="text-muted"><i class="fas fa-info-circle"></i> Mark this as an emergency to prioritize response.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-money-bill-wave"></i>Cost Information</label>
                        <div class="cost-input-group">
                            <div>
                                <label class="form-label small text-muted mb-1">Labor Amount (₱)</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" min="0" class="form-control" name="labor_amount" 
                                           placeholder="0.00" value="<?= $request['labor_amount'] ?? '' ?>">
                                </div>
                            </div>
                            <div>
                                <label class="form-label small text-muted mb-1">Total Cost of Work (₱)</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" min="0" class="form-control" name="total_cost" 
                                           placeholder="0.00" value="<?= $request['total_cost'] ?? '' ?>">
                                </div>
                            </div>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> Enter labor cost and total work cost for this maintenance request
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-comment"></i>Add Comment</label>
                        <textarea class="form-control" name="comment" rows="3" 
                                  placeholder="Add notes, updates, or assignment details..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-upload"></i>Upload Additional Files</label>
                        <input type="file" name="files[]" multiple class="form-control">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> Upload inspection reports, photos, or related documents
                        </small>
                    </div>
                    
                    <div class="text-center mt-4">
                        <button class="btn btn-update" type="submit">
                            <i class="fas fa-save me-2"></i>Update Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>