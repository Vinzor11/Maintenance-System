<?php
// oauth_callback.php
// Handles OAuth callback from provider

session_start();
require 'db.php';
require 'oauth_config.php';

$error = '';
$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';
$error_param = $_GET['error'] ?? '';

// Check for OAuth errors
if ($error_param) {
    header('Location: index.php?error=' . urlencode($error_param));
    exit;
}

// Verify state parameter (CSRF protection)
if (empty($state) || !isset($_SESSION['oauth_state']) || $state !== $_SESSION['oauth_state']) {
    header('Location: index.php?error=invalid_state');
    exit;
}

// Clear the state from session
unset($_SESSION['oauth_state']);

// Check if authorization code is present
if (empty($code)) {
    header('Location: index.php?error=no_authorization_code');
    exit;
}

try {
    // Step 1: Exchange authorization code for access token
    $token_data = [
        'grant_type' => 'authorization_code',
        'client_id' => OAUTH_CLIENT_ID,
        'client_secret' => OAUTH_CLIENT_SECRET,
        'code' => $code,
        'redirect_uri' => OAUTH_REDIRECT_URI,
    ];

    $ch = curl_init(OAUTH_TOKEN_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json',
    ]);

    $token_response = curl_exec($ch);
    $token_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($token_http_code !== 200) {
        throw new Exception('Failed to get access token. HTTP Code: ' . $token_http_code);
    }

    $token_result = json_decode($token_response, true);
    
    if (!isset($token_result['access_token'])) {
        throw new Exception('Access token not received from provider');
    }

    $access_token = $token_result['access_token'];

    // Step 2: Get user information using access token
    $ch = curl_init(OAUTH_USERINFO_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Accept: application/json',
    ]);

    $userinfo_response = curl_exec($ch);
    $userinfo_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($userinfo_http_code !== 200) {
        throw new Exception('Failed to get user info. HTTP Code: ' . $userinfo_http_code);
    }

    $userinfo = json_decode($userinfo_response, true);

    if (!$userinfo || !isset($userinfo['email'])) {
        throw new Exception('Invalid user info received');
    }

    // Step 3: Find or create user in local database
    $email = $userinfo['email'];
    $name = $userinfo['name'] ?? $userinfo['email'];
    $username = $userinfo['email']; // Use email as username, or extract from email
    $department = $userinfo['department'] ?? null; // Get department from userinfo
    
    // Extract username from email (part before @)
    if (strpos($email, '@') !== false) {
        $username = explode('@', $email)[0];
    }

    // Determine role based on userinfo - check roles array for maintenance-director or maintenance-head
    $role = 'user'; // Default to 'user'
    $is_admin = false;
    
    // Check for roles field (plural array) - only check this field
    if (isset($userinfo['roles']) && is_array($userinfo['roles'])) {
        foreach ($userinfo['roles'] as $r) {
            $role_value = is_string($r) ? trim($r) : (string)$r;
            $role_value_lower = strtolower($role_value);
            // Check for maintenance-director or maintenance-head (case-insensitive)
            if ($role_value_lower === 'maintenance-director' || 
                $role_value_lower === 'maintenance_director' ||
                $role_value_lower === 'maintenance-head' ||
                $role_value_lower === 'maintenance_head') {
                $is_admin = true;
                break; // Found one, no need to continue
            }
        }
    }
    
    // Only assign admin if explicitly found
    if ($is_admin) {
        $role = 'admin';
    }
    
    // Build role analysis string for debug page
    $role_analysis = "Role Determination Process:\n";
    $role_analysis .= "==========================\n\n";
    $role_analysis .= "Checking 'roles' field (array) for maintenance-director or maintenance-head:\n";
    if (isset($userinfo['roles']) && is_array($userinfo['roles'])) {
        $role_analysis .= "   - Found array with " . count($userinfo['roles']) . " item(s)\n";
        foreach ($userinfo['roles'] as $r) {
            $role_value = is_string($r) ? trim($r) : (string)$r;
            $role_value_lower = strtolower($role_value);
            $role_analysis .= "   - Checking: '{$role_value}'\n";
            if ($role_value_lower === 'maintenance-director' || 
                $role_value_lower === 'maintenance_director' ||
                $role_value_lower === 'maintenance-head' ||
                $role_value_lower === 'maintenance_head') {
                $role_analysis .= "     ✓ Matches admin criteria (maintenance-director or maintenance-head)\n";
            } else {
                $role_analysis .= "     ✗ Does not match admin criteria\n";
            }
        }
    } else {
        $role_analysis .= "   - Not found or not an array\n";
    }
    $role_analysis .= "\nFinal Result:\n";
    $role_analysis .= "   - Is Admin: " . ($is_admin ? 'YES' : 'NO') . "\n";
    $role_analysis .= "   - Assigned Role: {$role}\n";
    
    // Store debug data in session for debug page
    if (defined('OAUTH_DEBUG') && OAUTH_DEBUG) {
        $_SESSION['oauth_debug_userinfo'] = $userinfo;
        $_SESSION['oauth_debug_token'] = $token_result;
        $_SESSION['oauth_debug_role_analysis'] = $role_analysis;
        $_SESSION['oauth_debug_determined_role'] = $role;
        
        // Also log to error log
        error_log('OAuth UserInfo Debug - Email: ' . $email);
        error_log('OAuth UserInfo Debug - Role field: ' . (isset($userinfo['role']) ? var_export($userinfo['role'], true) : 'not set'));
        error_log('OAuth UserInfo Debug - Roles array: ' . (isset($userinfo['roles']) ? var_export($userinfo['roles'], true) : 'not set'));
        error_log('OAuth UserInfo Debug - Determined role: ' . $role);
        error_log('OAuth UserInfo Debug - Is admin: ' . ($is_admin ? 'true' : 'false'));
    }

    // Check if user exists by email
    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // User exists - update role based on SSO role
        // If they have Maintenance Officer/Head, upgrade to admin
        // If they don't have it but are currently admin, downgrade to user
        if ($role !== $user['role']) {
            // Update user role in database
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$role, $user['id']]);
        }
        
        // Log them in with updated role
        $_SESSION['userid'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $role; // Use the determined role (may be updated)
        $_SESSION['sso_login'] = true; // Flag to indicate SSO login
        $_SESSION['department'] = $department; // Store department from userinfo
    } else {
        // Create new user
        // Generate a random password (won't be used since it's SSO)
        $random_password = bin2hex(random_bytes(16));
        $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);

        // Check if username already exists, if so append a number
        $original_username = $username;
        $counter = 1;
        while (true) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if (!$stmt->fetch()) {
                break; // Username is available
            }
            $username = $original_username . $counter;
            $counter++;
        }

        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $hashed_password, $email, $role]);
        
        $new_user_id = $pdo->lastInsertId();
        
        // Log in the new user
        $_SESSION['userid'] = $new_user_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        $_SESSION['sso_login'] = true;
        $_SESSION['department'] = $department; // Store department from userinfo
    }

    // If debug mode is enabled and flag is set, redirect to debug page first
    // Otherwise, redirect based on role
    if (defined('OAUTH_DEBUG') && OAUTH_DEBUG && isset($_SESSION['oauth_show_debug']) && $_SESSION['oauth_show_debug']) {
        // Clear the flag
        unset($_SESSION['oauth_show_debug']);
        // Redirect to debug page to view data
        header('Location: oauth_debug.php');
        exit;
    }
    
    // Normal redirect based on role
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;

} catch (Exception $e) {
    // Log error (in production, log to file instead of displaying)
    error_log('OAuth Error: ' . $e->getMessage());
    header('Location: index.php?error=' . urlencode('SSO authentication failed. Please try again.'));
    exit;
}

?>

