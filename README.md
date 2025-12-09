# Maintenance Management System

A comprehensive web-based maintenance management system with OAuth SSO integration, department tracking, and administrative features.

## Features

- **User Authentication**: Login via username/password or SSO (Single Sign-On)
- **OAuth 2.0 SSO Integration**: Seamless authentication with HR System
- **Maintenance Request Management**: Submit, track, and manage maintenance requests
- **Department Tracking**: Automatic department assignment from OAuth userinfo
- **Role-Based Access Control**: Admin and user roles with different permissions
- **Admin Dashboard**: Comprehensive dashboard for managing all requests
- **Worker Assignment**: Assign workers to maintenance requests
- **File Attachments**: Upload and manage files with requests
- **Comments & History**: Track comments and request history
- **Statistics Dashboard**: View request statistics and status

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB database
- Web server (Apache/Nginx)
- OAuth 2.0 compatible authentication provider (for SSO)

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/Vinzor11/Maintenance-System.git
   cd Maintenance-System
   ```

2. **Configure Database**
   - Copy `db.php.example` to `db.php`
   - Edit `db.php` and fill in your database credentials:
     ```php
     $host = 'localhost';
     $db   = 'maintenance';
     $user = 'your_username';
     $pass = 'your_password';
     ```

3. **Import Database Schema**
   - Import the `if0_40633514_mms.sql` file into your MySQL database
   - This will create all necessary tables and structure

4. **Configure OAuth SSO** (Optional)
   - Copy `oauth_config.php.example` to `oauth_config.php`
   - Edit `oauth_config.php` and fill in your OAuth provider details:
     ```php
     define('OAUTH_PROVIDER_URL', 'https://your-oauth-provider.com');
     define('OAUTH_CLIENT_ID', 'your-client-id');
     define('OAUTH_CLIENT_SECRET', 'your-client-secret');
     define('OAUTH_REDIRECT_URI', 'http://your-domain.com/oauth_callback.php');
     ```
   - Make sure the redirect URI is registered with your OAuth provider

5. **Set Up File Uploads**
   - Create an `uploads/` directory in the project root
   - Set appropriate permissions (chmod 755 or 775)

6. **Configure Web Server**
   - Point your web server document root to the project directory
   - Ensure PHP has proper permissions

## Configuration

### Role Assignment

Users are assigned admin role automatically if they have one of these roles in the OAuth provider:
- `maintenance-director`
- `maintenance-head`

### Department Tracking

The system automatically captures the department from OAuth userinfo when users log in via SSO. This department is automatically included when users submit maintenance requests.

## Usage

### For Users
1. Login via username/password or click "Sign in with HR System" for SSO
2. Submit maintenance requests with details, system type, and attachments
3. View request status and updates on the dashboard

### For Administrators
1. Access the admin dashboard
2. View all maintenance requests
3. Assign workers to requests
4. Update request status (Submitted, In Progress, Completed, Rejected)
5. Add comments and manage request details
6. View statistics and reports

## Project Structure

```
Maintenance-System/
├── admin_dashboard.php       # Admin main dashboard
├── admin_update_requests.php # Admin request update page
├── dashboard.php             # User dashboard
├── index.php                 # Login page
├── submit_request.php        # Request submission handler
├── request_form.php          # Request form page
├── manage_request.php        # Request management page
├── oauth_callback.php        # OAuth callback handler
├── oauth_redirect.php        # OAuth redirect handler
├── db.php                    # Database configuration (not in repo)
├── db.php.example            # Database config template
├── oauth_config.php          # OAuth configuration (not in repo)
├── oauth_config.php.example  # OAuth config template
├── if0_40633514_mms.sql      # Database schema
└── README.md                 # This file
```

## Security Notes

- **Never commit sensitive files**: `db.php` and `oauth_config.php` are excluded from version control
- Use environment variables for production deployments
- Keep OAuth client secrets secure
- Regularly update dependencies
- Use HTTPS in production

## Support

For issues or questions, please open an issue on the GitHub repository.

## License

This project is open source and available for use.

