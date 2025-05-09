# GreenBlog

GreenBlog is a lightweight, secure, and fast blogging platform that serves as a minimalist alternative to WordPress. It uses PHP and SQLite with ADODB for database operations and generates static HTML files for optimal performance.

## Features

- **Lightweight**: Minimal dependencies and low resource usage
- **Secure**: Built with security best practices
- **Fast**: Generates static HTML files for optimal performance
- **Simple**: Easy to install and use
- **Minimal Configuration**: Works with standard Apache setups

## Requirements

- PHP 7.4 or higher
- SQLite 3 with PHP SQLite3 extension enabled
- Apache web server with mod_rewrite enabled
- Composer (for installation)

## Installation

1. **Download or clone the repository**

   ```
   git clone https://github.com/yourusername/greenblog.git
   cd greenblog
   ```

2. **Install dependencies using Composer**

   ```
   composer install
   ```

3. **Set file permissions**

   Make sure the following directories are writable by the web server:

   ```
   chmod -R 755 data
   chmod -R 755 public_html/static
   chmod -R 755 public_html/uploads
   ```

4. **Configure your web server**

   Make sure your Apache virtual host is configured with mod_rewrite enabled and AllowOverride All.

5. **Configure your web server document root**

   Set your web server's document root to point to the `public_html` directory.

6. **Run the setup script**

   Navigate to `http://yourdomain.com/setup.php` in your web browser and follow the instructions to complete the installation.

## Local Development

### Running PHP's Built-in Web Server

For local development, you can use PHP's built-in web server to serve the public_html directory:

#### Using the Provided Scripts (Windows)

GreenBlog comes with scripts to easily start the development server:

1. **Using Batch Script**:
   ```
   scripts\start-server.bat
   ```

2. **Using PowerShell Script**:
   ```
   scripts\start-server.ps1
   ```

These scripts will:
- Check if PHP is available in your PATH
- Verify if the SQLite3 extension is loaded
- Start the server on port 8001

#### Manual Method

You can also start the server manually:

```
cd /path/to/greenblog
php -S localhost:8000 -t public_html
```

If you encounter permission errors, make sure you're using the correct syntax with the `-t` flag:

```
# INCORRECT (causes permission errors):
php -S localhost:8000 public_html

# CORRECT:
php -S localhost:8000 -t public_html
```

This will start a local development server that serves files from the public_html directory.

Notes:
- This is for development purposes only and should not be used in production
- The server will run until you stop it (Ctrl+C)
- You can change the port number (8000/8001) to any available port on your system
- Access the admin interface at http://localhost:8001/admin/ (or whichever port you're using)

### Using XAMPP, WAMP, or Other Local Server Environments

If you're using XAMPP, WAMP, or a similar local server environment:

1. Configure your virtual host to point to the `public_html` directory
2. Access your site through the configured domain name or localhost path

## Usage

### Admin Interface

After installation, you can access the admin interface at `http://yourdomain.com/admin/`.

The admin interface allows you to:

- Create, edit, and delete posts
- Manage categories
- Upload and manage media
- Configure site settings
- Manually regenerate static files

### Creating Content

1. Log in to the admin interface
2. Click on "New Post" to create a new post
3. Fill in the title, content, and other details
4. Select categories for the post
5. Upload a featured image if desired
6. Save as draft or publish immediately

### Static File Generation

GreenBlog automatically generates static HTML files when:

- A post is published or updated
- A category is created or updated
- Site settings are changed

You can also manually regenerate all static files from the admin interface by clicking on "Regenerate Static Files".

## Directory Structure

- `public_html/`: Web-accessible files
  - `admin/`: Admin interface files
  - `static/`: Generated static files
  - `uploads/`: Media uploads
  - `assets/`: CSS, JS, and images
- `includes/`: Core functionality
- `templates/`: HTML templates
- `data/`: SQLite database

## Security Considerations

### General Security
- Keep your PHP and SQLite up to date
- Use strong passwords for the admin account
- Regularly back up your database
- Consider using HTTPS for your site

### Version Control & Deployment
When using version control (like Git) or deploying to a public repository:

- The `.gitignore` file is configured to exclude sensitive data
- Never commit the database file (`data/greenblog.db`) to a public repository
- Consider excluding `includes/config.php` after setup (uncomment the line in `.gitignore`)
- Be careful with user uploads in `public_html/uploads/` - they are excluded by default
- Generated static files in `public_html/static/` are also excluded

### Production Deployment
For production environments:

- Set appropriate file permissions (typically 755 for directories, 644 for files)
- Configure your web server to deny direct access to the `includes` and `data` directories
- Regularly backup your database and uploaded content
- Consider using a more robust database like MySQL for high-traffic sites

## Troubleshooting

### SQLite Issues

If you encounter errors related to SQLite functions like `sqlite_query()`, it means your PHP installation is using SQLite3 but the code is trying to use the older SQLite2 driver. Make sure:

1. The PHP SQLite3 extension is enabled in your php.ini
2. You're using the 'sqlite3' driver in your database connection code:
   ```php
   $conn = ADONewConnection('sqlite3');
   ```

#### Enabling SQLite3 Extension in PHP (Windows)

If you see the error "SQLite3 extension is not loaded", follow these steps to enable it:

1. Locate your php.ini file:
   ```
   php --ini
   ```

2. Open the php.ini file in a text editor (run as administrator)

3. Find the line with `;extension=sqlite3` (it has a semicolon at the beginning)

4. Remove the semicolon to uncomment the line:
   ```
   extension=sqlite3
   ```

5. Save the file and restart your web server or PHP process

6. Verify the extension is loaded:
   ```
   php -m | findstr sqlite
   ```
   You should see "sqlite3" in the output

### PHP Built-in Server Issues

If you get permission errors when using PHP's built-in server, make sure you're using the correct syntax with the `-t` flag:

```
# CORRECT:
php -S localhost:8000 -t public_html
```

## License

GreenBlog is released under the MIT License. See the LICENSE file for details.
