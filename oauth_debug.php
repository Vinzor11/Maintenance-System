<?php
// oauth_debug.php
// Debug page to display OAuth userinfo data

session_start();
require 'oauth_config.php';

// Check if we have debug data in session
$userinfo = $_SESSION['oauth_debug_userinfo'] ?? null;
$token_data = $_SESSION['oauth_debug_token'] ?? null;
$role_analysis = $_SESSION['oauth_debug_role_analysis'] ?? null;
$determined_role = $_SESSION['oauth_debug_determined_role'] ?? null;

// If no debug data, show message
$has_data = $userinfo !== null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth Debug - UserInfo Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .debug-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .debug-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .debug-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            margin: -20px -20px 20px -20px;
        }
        .json-display {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .role-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        .role-admin {
            background: #28a745;
            color: white;
        }
        .role-user {
            background: #6c757d;
            color: white;
        }
        .field-label {
            font-weight: 600;
            color: #495057;
            margin-top: 10px;
            margin-bottom: 5px;
        }
        .field-value {
            color: #212529;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 3px solid #667eea;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .btn-refresh {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="debug-container">
        <div class="debug-card">
            <div class="debug-header">
                <h2><i class="fas fa-bug"></i> OAuth UserInfo Debug Page</h2>
                <p class="mb-0">View all data fetched from the OAuth provider</p>
            </div>

            <?php if (!$has_data): ?>
                <div class="no-data">
                    <i class="fas fa-info-circle fa-3x mb-3" style="color: #6c757d;"></i>
                    <h4>No Debug Data Available</h4>
                    <p>This page displays OAuth userinfo data after a successful SSO login.</p>
                    <p>To see debug data:</p>
                    <ol class="text-start" style="display: inline-block;">
                        <li>Make sure <code>OAUTH_DEBUG</code> is enabled in <code>oauth_config.php</code></li>
                        <li>Complete an OAuth login flow</li>
                        <li>Return to this page to view the data</li>
                    </ol>
                    <a href="index.php" class="btn btn-primary btn-refresh">
                        <i class="fas fa-sign-in-alt"></i> Go to Login
                    </a>
                </div>
            <?php else: ?>
                <!-- Role Analysis Section -->
                <div class="mb-4">
                    <h4><i class="fas fa-user-shield"></i> Role Analysis</h4>
                    <div class="field-label">Determined Role:</div>
                    <div class="field-value">
                        <span class="role-badge role-<?php echo htmlspecialchars($determined_role ?? 'user'); ?>">
                            <?php echo htmlspecialchars(strtoupper($determined_role ?? 'user')); ?>
                        </span>
                    </div>
                    
                    <?php if ($role_analysis): ?>
                        <div class="field-label mt-3">Role Check Details:</div>
                        <div class="field-value">
                            <?php echo nl2br(htmlspecialchars($role_analysis)); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- UserInfo Raw Data -->
                <div class="mb-4">
                    <h4><i class="fas fa-database"></i> Raw UserInfo Response</h4>
                    <div class="json-display"><?php echo htmlspecialchars(json_encode($userinfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?></div>
                </div>

                <!-- Parsed UserInfo Fields -->
                <div class="mb-4">
                    <h4><i class="fas fa-list"></i> Parsed UserInfo Fields</h4>
                    <?php if ($userinfo): ?>
                        <?php foreach ($userinfo as $key => $value): ?>
                            <div class="field-label">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($key); ?>
                                <small class="text-muted">(<?php echo gettype($value); ?>)</small>
                            </div>
                            <div class="field-value">
                                <?php 
                                if (is_array($value)) {
                                    echo '<pre>' . htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '</pre>';
                                } elseif (is_bool($value)) {
                                    echo $value ? 'true' : 'false';
                                } elseif (is_null($value)) {
                                    echo '<em class="text-muted">null</em>';
                                } else {
                                    echo htmlspecialchars($value);
                                }
                                ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Role-Specific Analysis -->
                <div class="mb-4">
                    <h4><i class="fas fa-search"></i> Role Field Analysis</h4>
                    <?php
                    $has_role_field = isset($userinfo['role']);
                    $has_roles_field = isset($userinfo['roles']);
                    ?>
                    <div class="field-label">Role Field (singular):</div>
                    <div class="field-value">
                        <?php if ($has_role_field): ?>
                            <strong>Found:</strong> 
                            <code><?php echo htmlspecialchars($userinfo['role']); ?></code>
                            <br>
                            <small class="text-muted">Type: <?php echo gettype($userinfo['role']); ?></small>
                        <?php else: ?>
                            <em class="text-muted">Not present in userinfo</em>
                        <?php endif; ?>
                    </div>

                    <div class="field-label mt-3">Roles Field (plural array):</div>
                    <div class="field-value">
                        <?php if ($has_roles_field): ?>
                            <strong>Found:</strong> 
                            <?php if (is_array($userinfo['roles'])): ?>
                                <ul class="mb-0">
                                    <?php foreach ($userinfo['roles'] as $idx => $role_item): ?>
                                        <li>
                                            <code><?php echo htmlspecialchars($role_item); ?></code>
                                            <?php 
                                            $role_item_trimmed = trim($role_item);
                                            $role_item_lower = strtolower($role_item_trimmed);
                                            if ($role_item_lower === 'maintenance-director' || 
                                                $role_item_lower === 'maintenance_director' ||
                                                $role_item_lower === 'maintenance-head' ||
                                                $role_item_lower === 'maintenance_head'): ?>
                                                <span class="badge bg-success ms-2">✓ Matches Admin Criteria</span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <code><?php echo htmlspecialchars($userinfo['roles']); ?></code>
                                <br>
                                <small class="text-warning">Warning: Not an array (type: <?php echo gettype($userinfo['roles']); ?>)</small>
                            <?php endif; ?>
                        <?php else: ?>
                            <em class="text-muted">Not present in userinfo</em>
                        <?php endif; ?>
                    </div>

                    <div class="field-label mt-3">Admin Assignment Check:</div>
                    <div class="field-value">
                        <?php
                        $should_be_admin = false;
                        $check_details = [];
                        
                        // Only check roles array for maintenance-director or maintenance-head
                        if ($has_roles_field && is_array($userinfo['roles'])) {
                            $found_in_roles = false;
                            foreach ($userinfo['roles'] as $r) {
                                $role_value = is_string($r) ? trim($r) : (string)$r;
                                $role_value_lower = strtolower($role_value);
                                if ($role_value_lower === 'maintenance-director' || 
                                    $role_value_lower === 'maintenance_director' ||
                                    $role_value_lower === 'maintenance-head' ||
                                    $role_value_lower === 'maintenance_head') {
                                    $should_be_admin = true;
                                    $found_in_roles = true;
                                    $check_details[] = "✓ Found '{$role_value}' in 'roles' array (matches admin criteria: maintenance-director or maintenance-head)";
                                    break;
                                }
                            }
                            if (!$found_in_roles) {
                                $check_details[] = "✗ 'roles' array does not contain 'maintenance-director' or 'maintenance-head'";
                            }
                        } else {
                            $check_details[] = "✗ 'roles' field not found or not an array";
                        }
                        
                        foreach ($check_details as $detail) {
                            echo htmlspecialchars($detail) . "<br>";
                        }
                        ?>
                        <hr>
                        <strong>Result:</strong> 
                        <?php if ($should_be_admin): ?>
                            <span class="badge bg-success">Should be assigned ADMIN role</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Should be assigned USER role</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Token Data (if available) -->
                <?php if ($token_data): ?>
                <div class="mb-4">
                    <h4><i class="fas fa-key"></i> Token Response (Sanitized)</h4>
                    <div class="json-display"><?php 
                        $sanitized_token = $token_data;
                        // Hide sensitive data
                        if (isset($sanitized_token['access_token'])) {
                            $sanitized_token['access_token'] = substr($sanitized_token['access_token'], 0, 20) . '...' . substr($sanitized_token['access_token'], -10);
                        }
                        if (isset($sanitized_token['refresh_token'])) {
                            $sanitized_token['refresh_token'] = substr($sanitized_token['refresh_token'], 0, 20) . '...' . substr($sanitized_token['refresh_token'], -10);
                        }
                        echo htmlspecialchars(json_encode($sanitized_token, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    ?></div>
                </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="text-center">
                    <?php if (isset($_SESSION['userid'])): ?>
                        <!-- User is logged in, show continue button -->
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <a href="admin_dashboard.php" class="btn btn-success btn-lg">
                                <i class="fas fa-arrow-right"></i> Continue to Admin Dashboard
                            </a>
                        <?php else: ?>
                            <a href="dashboard.php" class="btn btn-success btn-lg">
                                <i class="fas fa-arrow-right"></i> Continue to Dashboard
                            </a>
                        <?php endif; ?>
                        <br><br>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Back to Login
                    </a>
                    <button onclick="location.reload()" class="btn btn-secondary">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <button onclick="clearDebugData()" class="btn btn-warning">
                        <i class="fas fa-trash"></i> Clear Debug Data
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function clearDebugData() {
            if (confirm('Clear all debug data from session?')) {
                fetch('oauth_debug.php?clear=1', {
                    method: 'GET'
                }).then(() => {
                    location.reload();
                });
            }
        }

        // Handle clear parameter
        <?php if (isset($_GET['clear']) && $_GET['clear'] == '1'): ?>
            <?php
            unset($_SESSION['oauth_debug_userinfo']);
            unset($_SESSION['oauth_debug_token']);
            unset($_SESSION['oauth_debug_role_analysis']);
            unset($_SESSION['oauth_debug_determined_role']);
            ?>
            window.location.href = 'oauth_debug.php';
        <?php endif; ?>
    </script>
</body>
</html>

