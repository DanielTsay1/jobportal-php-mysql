# Admin System Setup Guide

## Overview
The admin system allows administrators to approve or reject job postings before they become visible to job seekers.

## Database Setup

1. **Run the admin table SQL:**
   ```sql
   -- Execute the contents of sql/admin_table.sql
   -- This creates the admin table and adds a default admin user
   ```

2. **Default Admin Credentials:**
   - Username: `admin`
   - Password: `admin123`
   - Email: `admin@jobportal.com`

## Files Created

### Database
- `sql/admin_table.sql` - SQL to create admin table and default user

### Admin Pages
- `main/admin-login.php` - Admin login page with modern design
- `main/admin-dashboard.php` - Admin dashboard to manage job postings
- `php/admin-login.php` - Admin authentication handler
- `php/approve-job.php` - Job approval/rejection handler

### Updated Files
- `php/postjob.php` - Updated to set new jobs as 'Pending' by default
- `main/index.php` - Added admin link to navigation

## How It Works

### Job Posting Flow
1. **Recruiter posts a job** → Status automatically set to 'Pending'
2. **Admin reviews the job** → Can approve or reject
3. **If approved** → Status changes to 'Active' and job appears in listings
4. **If rejected** → Status changes to 'Rejected' and job is hidden

### Admin Dashboard Features
- **Statistics Cards**: Shows total, pending, active, and rejected job counts
- **Filter Tabs**: Filter jobs by status (All, Pending, Active, Rejected)
- **Job Cards**: Display job details with approve/reject buttons for pending jobs
- **Status Indicators**: Color-coded borders and badges for different statuses

## Accessing the Admin Panel

1. **Via Navigation**: Click "Admin" link in the main navigation
2. **Direct URL**: Navigate to `/main/admin-login.php`
3. **Login**: Use the default credentials (admin/admin123)

## Security Features

- **Session-based authentication**: Only logged-in admins can access the dashboard
- **Password hashing**: Admin passwords are securely hashed
- **Input validation**: All inputs are sanitized and validated
- **SQL injection protection**: Prepared statements used throughout

## Customization

### Changing Default Admin Password
1. Generate a new hashed password:
   ```php
   echo password_hash('your_new_password', PASSWORD_DEFAULT);
   ```
2. Update the admin table:
   ```sql
   UPDATE admin SET password = 'new_hashed_password' WHERE username = 'admin';
   ```

### Adding More Admin Users
```sql
INSERT INTO admin (username, password, email) VALUES 
('newadmin', 'hashed_password', 'newadmin@example.com');
```

## Troubleshooting

### Common Issues
1. **"No admin found" error**: Ensure the admin table exists and has the default user
2. **Jobs not showing as pending**: Check that `postjob.php` includes the status field in the INSERT query
3. **Approval not working**: Verify that `approve-job.php` has proper permissions and database access

### Database Checks
```sql
-- Check if admin table exists
SHOW TABLES LIKE 'admin';

-- Check admin users
SELECT * FROM admin;

-- Check job statuses
SELECT status, COUNT(*) FROM `job-post` GROUP BY status;
```

## Future Enhancements

- Email notifications for new pending jobs
- Bulk approve/reject functionality
- Job editing capabilities for admins
- Admin activity logging
- Multiple admin roles and permissions 