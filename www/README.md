# Manga Tracker - Setup Guide

## Database Setup

1. Start your local server (XAMPP, WAMP, etc.)
2. Open phpMyAdmin (typically http://localhost/phpmyadmin)
3. Create a new database named `manga_tracker`
4. Select the `manga_tracker` database
5. Click on the "Import" tab
6. Upload the `db_setup.sql` file and click "Go"

## Configuration

The `config.php` file is currently set up for local development with these defaults:
- Host: localhost
- Database: manga_tracker
- Username: root
- Password: (blank)

If your local setup uses different credentials, update the `config.php` file accordingly.

## Running the Application

1. Place all files in your web server's document root (or a subdirectory)
2. Access the site via http://localhost/your-directory
3. You can log in with the demo account:
   - Username: demo
   - Password: password123

## Features

- User registration and login
- Manga progress tracking
- Comments/Guestbook functionality
- Responsive design

## File Structure

- `index.php`: Home page
- `manga.php`: Manga tracking functionality
- `comment.php`: Guestbook/comments functionality
- `login.php` & `register.php`: User authentication
- `header.php` & `footer.php`: Shared UI components
- `assets/`: CSS and images

## Note on File Removal

You can safely remove these files as they're not essential:
- `log.php`: Insecure logging script
- `reads.log`: Text-based log file (use the database instead)
