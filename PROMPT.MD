# GreenBlog Development Prompt

## Project Overview

Create a lightweight, secure, and fast blogging platform called "GreenBlog" that serves as a minimalist alternative to WordPress. The solution should:

1. Use PHP and SQLite with ADODB for database operations
2. Generate static HTML files for performance
3. Require minimal server configuration
4. Be easily installable in an Apache vhost
5. Include a simple admin interface for creating and managing posts
6. Prioritize security, simplicity, and speed

## Technical Requirements

### Core Technologies
- PHP 7.4+ (compatible with PHP 8.x)
- SQLite 3 database
- ADODB library for database abstraction
- Apache web server compatibility
- No JavaScript frameworks required (vanilla JS only where necessary)
- Minimal external dependencies

### Installation & Setup
- Single setup script that:
  - Creates necessary database structure
  - Configures blog name, admin credentials, and basic settings
  - Sets appropriate file permissions
  - Generates initial static files
  - Provides clear success/error messages
- Should work with standard Apache configurations without special modules
- No command-line operations required for end users

### Architecture
- File-based SQLite database for content storage
- ADODB for secure database operations
- Static file generation system that creates HTML files for:
  - Homepage/index (with pagination)
  - Individual blog posts
  - Category/tag pages
  - Archive pages
- Simple template system with minimal, clean design
- Caching mechanism for optimal performance

### Admin Interface
- Secure login system with password hashing
- Basic WYSIWYG or Markdown editor for post creation
- Post management (create, edit, delete)
- Category/tag management
- Media upload capability (images only)
- Option to manually regenerate static files
- Basic site settings configuration

### Security Features
- Input validation and sanitization
- Protection against SQL injection (via ADODB)
- XSS prevention
- CSRF protection
- Secure password handling
- File upload validation
- Rate limiting for login attempts

### Performance Optimization
- Static file generation for all public-facing pages
- Minimal database queries
- Efficient caching system
- Optimized image handling
- Low memory footprint

## Functional Requirements

### Setup Process
1. User uploads files to server
2. Navigates to setup.php
3. Enters blog name, admin username, password, and email
4. Setup script creates database, tables, and initial configuration
5. Static files are generated
6. User is redirected to admin login

### Content Management
1. Admin can create, edit, and delete posts
2. Posts support:
   - Title
   - Content (formatted text)
   - Categories/tags
   - Featured image
   - Publication date
   - Draft/published status
3. When a post is published or updated, static files are regenerated

### Front-end Features
1. Clean, responsive design
2. Blog homepage with recent posts
3. Individual post pages
4. Category/tag filtering
5. Simple search functionality
6. Pagination
7. RSS feed

## Project Structure

```
greenblog/
├── admin/                  # Admin interface files
│   ├── index.php           # Admin dashboard
│   ├── login.php           # Login page
│   ├── posts.php           # Post management
│   ├── settings.php        # Site settings
│   └── media.php           # Media management
├── includes/               # Core functionality
│   ├── config.php          # Configuration file (generated during setup)
│   ├── db.php              # Database connection using ADODB
│   ├── functions.php       # Helper functions
│   ├── auth.php            # Authentication functions
│   └── static-generator.php # Static file generation logic
├── templates/              # HTML templates
│   ├── index.template.php  # Homepage template
│   ├── post.template.php   # Single post template
│   ├── category.template.php # Category page template
│   └── admin/              # Admin templates
├── static/                 # Generated static files (created by the system)
├── uploads/                # Media uploads
├── assets/                 # CSS, JS, and images
│   ├── css/
│   ├── js/
│   └── images/
├── setup.php               # Installation script
├── index.php               # Front controller (redirects to static files)
└── .htaccess               # Apache configuration
```

## Development Guidelines

1. Prioritize simplicity and readability over complex features
2. Follow secure coding practices throughout
3. Minimize external dependencies
4. Ensure compatibility with standard shared hosting environments
5. Document code thoroughly
6. Implement proper error handling and logging
7. Create a system that's easy to maintain and extend

## Deliverables

1. Complete source code for the GreenBlog system
2. Installation and usage documentation
3. Security considerations and best practices
4. Sample blog with demonstration content

## Implementation Approach

1. Set up the basic project structure
2. Implement the database schema and ADODB integration
3. Create the setup script
4. Develop the admin interface
5. Implement the static file generation system
6. Create front-end templates
7. Add security features
8. Optimize performance
9. Test thoroughly in various environments

This lightweight blogging platform should provide a simple, secure alternative to WordPress for users who need a basic blog without the complexity and resource requirements of larger CMS systems.
