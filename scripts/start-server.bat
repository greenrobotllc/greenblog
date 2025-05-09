@echo off
REM GreenBlog Development Server Starter
REM This script starts the PHP built-in web server for GreenBlog

echo Starting GreenBlog development server...
echo.
echo Server will be available at http://localhost:8001
echo Press Ctrl+C to stop the server
echo.

REM Check if PHP is in the PATH
where php >nul 2>nul
if %ERRORLEVEL% neq 0 (
    echo Error: PHP not found in PATH
    echo Please make sure PHP is installed and added to your PATH environment variable
    echo.
    pause
    exit /b 1
)

REM Check PHP version
echo PHP Version:
php -r "echo phpversion();"
echo.

REM Check if SQLite3 extension is enabled
echo SQLite3 Extension Status:
php -r "echo extension_loaded('sqlite3') ? 'SQLite3 extension is loaded' : 'WARNING: SQLite3 extension is not loaded';"
echo.

REM Check loaded PHP configuration file
echo PHP Configuration File:
php --ini | findstr "Loaded Configuration File"
echo.

REM Provide info about phpinfo page
echo For detailed PHP configuration, visit: http://localhost:8001/phpinfo.php
echo.

REM Start the PHP development server
echo Starting server with command: php -S localhost:8001 -t public_html
echo.
php -S localhost:8001 -t public_html

REM This part will only execute if the server is stopped
echo.
echo Server stopped
pause
