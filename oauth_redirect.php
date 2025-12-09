<?php
// oauth_redirect.php
// Redirects user to OAuth provider for authentication

session_start();
require 'oauth_config.php';

// Check if SSO is enabled
if (!SSO_ENABLED) {
    header('Location: index.php?error=sso_not_configured');
    exit;
}

// Generate a random state parameter for CSRF protection
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// Build authorization URL
$params = [
    'client_id' => OAUTH_CLIENT_ID,
    'redirect_uri' => OAUTH_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => OAUTH_SCOPES,
    'state' => $state,
];

$authorize_url = OAUTH_AUTHORIZE_URL . '?' . http_build_query($params);

// Redirect to OAuth provider
header('Location: ' . $authorize_url);
exit;

?>

