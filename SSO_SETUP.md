# SSO Integration Setup Guide

This guide will help you configure Single Sign-On (SSO) integration with your HR System.

## Prerequisites

- PHP 7.4 or higher
- cURL extension enabled
- Access to HR System OAuth credentials

## Configuration Steps

### Step 1: Get OAuth Credentials

Contact your HR System administrator to obtain:
- **Client ID**: Your OAuth client identifier
- **Client Secret**: Your OAuth client secret
- **Redirect URI**: Must match exactly (see Step 2)

### Step 2: Configure OAuth Settings

Edit `oauth_config.php` and update the following constants:

```php
// Option 1: Direct configuration (for development)
define('OAUTH_PROVIDER_URL', 'https://hr-production-eaf1.up.railway.app');
define('OAUTH_CLIENT_ID', 'your-client-id-here');
define('OAUTH_CLIENT_SECRET', 'your-client-secret-here');
define('OAUTH_REDIRECT_URI', 'http://localhost/maintenance-management-system/oauth_callback.php');
```

**OR** use environment variables (recommended for production):

```php
// Option 2: Environment variables (for production)
// Set these in your server environment or .env file
define('OAUTH_PROVIDER_URL', getenv('OAUTH_PROVIDER_URL') ?: 'https://hr-production-eaf1.up.railway.app');
define('OAUTH_CLIENT_ID', getenv('OAUTH_CLIENT_ID') ?: '');
define('OAUTH_CLIENT_SECRET', getenv('OAUTH_CLIENT_SECRET') ?: '');
define('OAUTH_REDIRECT_URI', getenv('OAUTH_REDIRECT_URI') ?: 'http://localhost/maintenance-management-system/oauth_callback.php');
```

### Step 3: Set Redirect URI

**IMPORTANT**: The redirect URI in `oauth_config.php` must **exactly match** the redirect URI registered in your HR System's OAuth client settings.

For example:
- If your system is at `https://yourdomain.com/maintenance-management-system/`
- Then redirect URI should be: `https://yourdomain.com/maintenance-management-system/oauth_callback.php`

### Step 4: Test the Integration

1. Navigate to your login page (`index.php`)
2. You should see a "Sign in with HR System" button (if SSO is properly configured)
3. Click the button to test the OAuth flow
4. After successful authentication, you should be redirected to your dashboard

## How It Works

1. **User clicks "Sign in with HR System"** → Redirects to `oauth_redirect.php`
2. **oauth_redirect.php** → Generates a secure state token and redirects user to HR System
3. **User authenticates on HR System** → HR System redirects back to `oauth_callback.php` with authorization code
4. **oauth_callback.php** → 
   - Verifies the state token (CSRF protection)
   - Exchanges authorization code for access token
   - Retrieves user information from HR System
   - Creates or updates user in local database
   - Logs user into the system

## User Management

### Automatic User Creation

When a user logs in via SSO for the first time:
- A new user account is automatically created
- Username is derived from email (part before @)
- Email is used as the primary identifier
- Role is set to 'user' by default (or 'admin' if user has admin role in HR System)
- A random password is generated (not used since authentication is via SSO)

### Existing Users

If a user already exists in the system (matched by email):
- They are automatically logged in
- Their existing role and permissions are preserved
- No duplicate accounts are created

## Troubleshooting

### SSO Button Not Showing

- Check that `OAUTH_CLIENT_ID` and `OAUTH_CLIENT_SECRET` are set in `oauth_config.php`
- Verify `SSO_ENABLED` constant is `true`

### "Invalid redirect URI" Error

- Ensure the redirect URI in `oauth_config.php` exactly matches the one registered in HR System
- Check for trailing slashes, protocol (http vs https), and port numbers

### "Invalid state parameter" Error

- This usually indicates a session issue
- Clear browser cookies and try again
- Ensure sessions are properly configured in PHP

### "Failed to get access token" Error

- Verify your Client ID and Client Secret are correct
- Check that the authorization code hasn't expired (codes expire quickly)
- Ensure your server can make outbound HTTPS requests

### "Failed to get user info" Error

- Verify the access token is valid
- Check that the HR System's userinfo endpoint is accessible
- Ensure the token hasn't expired

## Security Best Practices

1. **Never commit secrets to version control**
   - Use environment variables for production
   - Add `oauth_config.php` to `.gitignore` if it contains secrets

2. **Use HTTPS in production**
   - OAuth requires HTTPS for security
   - Never use HTTP in production environments

3. **Validate redirect URIs**
   - Always verify the redirect URI matches exactly
   - This prevents redirect attacks

4. **Protect against CSRF**
   - The state parameter is used for CSRF protection
   - Never skip state validation

5. **Secure session storage**
   - Use secure session cookies
   - Set appropriate session timeouts

## Files Created

- `oauth_config.php` - OAuth configuration settings
- `oauth_redirect.php` - Initiates OAuth flow
- `oauth_callback.php` - Handles OAuth callback and user authentication
- `SSO_SETUP.md` - This setup guide

## Support

If you encounter issues:
1. Check this guide first
2. Verify your OAuth credentials are correct
3. Check PHP error logs for detailed error messages
4. Contact your HR System administrator for OAuth-related issues

