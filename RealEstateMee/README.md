# RealEstateMee

RealEstateMee is a comprehensive real estate web application built with PHP and MySQL, featuring multi-tenancy, user management, admin and super admin panels, property management, commission tracking, and company information management.

## Features

- User registration, login, and password management
- User dashboard with property and commission overview
- Admin panel for managing agents, owners, properties, and approvals
- Super admin panel for managing companies, admins, and overall control
- Multi-tenancy support for multiple real estate companies
- Secure password hashing and input validation
- Commission calculations and tracking
- Company information management with logo upload and color pattern
- Detailed documentation for installation, usage, and troubleshooting

## Folder Structure

- /config/ - Configuration files (database, constants)
- /public/ - Public assets (images, CSS, JS)
- /user_management/ - User registration, login, password change, dashboard
- /admin_panel/ - Admin login, dashboard, manage agents, owners, properties
- /super_admin_panel/ - Super admin login, dashboard, manage companies, admins
- /properties/ - Property CRUD and assignments
- /agents/ - Agent CRUD
- /owners/ - Owner CRUD
- /commissions/ - Commission calculations and display
- /company_info/ - Company info management
- /includes/ - Common includes (header, footer, auth checks)
- /docs/ - Documentation
- index.php - Landing page or redirect
- .htaccess - URL rewriting and security

## Installation

1. Import the database schema from `database/schema.sql` into your MySQL server.
2. Configure database connection in `/config/db.php`.
3. Upload the application files to your PHP-enabled web server.
4. Set proper permissions for the `/public/uploads/` directory for logo uploads.
5. Access the application via your web browser.

## Default Accounts

- Super Admin:
  - Username: superadmin
  - Password: password123 (changeable)
- Default User:
  - Username: user123
  - Password: password123 (changeable)

## Usage

- Use the super admin panel to create companies and assign admins.
- Admins manage agents, owners, and properties within their company.
- Users can register, login, and manage their properties and commissions.

## Troubleshooting

- Ensure PHP version 7.4 or higher is installed.
- Verify MySQL server is running and accessible.
- Check file permissions for uploads.
- Review PHP error logs for issues.
