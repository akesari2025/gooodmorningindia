# Goood Morning India

A PHP application with an admin panel to manage Instagram follower IDs displayed as badges on the main page.

## Setup

1. **Configure MySQL**
   - Copy `config.local.php.example` to `config.local.php`
   - Update database name, username, and password

2. **Run Install**
   - Visit `http://your-site/install.php` in your browser
   - This creates tables and a default admin user

3. **Default Admin Login**
   - Username: `admin`
   - Password: `admin123`
   - **Change this password** after first login

## Admin Panel

- **Login**: `/login.php`
- **Dashboard**: After login, add Instagram follower IDs one by one via the textbox
- Data is stored in the `followers` table

## Structure

- `index.php` - Main page (displays badges from database)
- `login.php` - Admin login
- `admin/dashboard.php` - Add Instagram IDs (protected)
- `install.php` - One-time database setup
