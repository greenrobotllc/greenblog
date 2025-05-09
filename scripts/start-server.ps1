# GreenBlog Development Server Starter
# This script starts the PHP built-in web server for GreenBlog

$host.UI.RawUI.WindowTitle = "GreenBlog Development Server"
$port = 8001
$docRoot = "public_html"

Write-Host "Starting GreenBlog development server..." -ForegroundColor Green
Write-Host ""
Write-Host "Server will be available at http://localhost:$port" -ForegroundColor Cyan
Write-Host "Press Ctrl+C to stop the server"
Write-Host ""

# Check if PHP is available
try {
    $phpVersion = (php -v) | Select-Object -First 1
    Write-Host "Using $phpVersion" -ForegroundColor Green

    # Get PHP configuration file
    $phpIni = (php --ini | Select-String "Loaded Configuration File").ToString()
    Write-Host "PHP Configuration: $phpIni" -ForegroundColor Cyan
} catch {
    Write-Host "Error: PHP not found in PATH" -ForegroundColor Red
    Write-Host "Please make sure PHP is installed and added to your PATH environment variable" -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit 1
}

# Check PHP extensions
Write-Host ""
Write-Host "Checking PHP Extensions:" -ForegroundColor Cyan

# Check if SQLite3 extension is enabled
$sqliteEnabled = php -r "echo extension_loaded('sqlite3') ? 'true' : 'false';"
if ($sqliteEnabled -eq "true") {
    Write-Host "✓ SQLite3 extension is loaded" -ForegroundColor Green
} else {
    Write-Host "✗ SQLite3 extension is NOT loaded" -ForegroundColor Red
    Write-Host "  GreenBlog requires the SQLite3 extension to be enabled in your PHP configuration." -ForegroundColor Yellow
    Write-Host "  Please check the README.md for instructions on how to enable it." -ForegroundColor Yellow

    # Show the exact line to uncomment in php.ini
    Write-Host ""
    Write-Host "To enable SQLite3, edit your php.ini file and uncomment this line:" -ForegroundColor Yellow
    Write-Host "extension=sqlite3" -ForegroundColor Cyan
    Write-Host ""
    $continue = Read-Host "Do you want to continue anyway? (y/n)"
    if ($continue -ne "y") {
        exit 1
    }
}

# Check if PDO SQLite extension is enabled (as an alternative)
$pdoSqliteEnabled = php -r "echo extension_loaded('pdo_sqlite') ? 'true' : 'false';"
if ($pdoSqliteEnabled -eq "true") {
    Write-Host "✓ PDO SQLite extension is loaded" -ForegroundColor Green
} else {
    Write-Host "✗ PDO SQLite extension is NOT loaded" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "For detailed PHP configuration, visit: http://localhost:$port/phpinfo.php" -ForegroundColor Cyan

Write-Host ""
Write-Host "Starting server with command: php -S localhost:$port -t $docRoot" -ForegroundColor Cyan
Write-Host ""

# Start the PHP development server
try {
    php -S localhost:$port -t $docRoot
} catch {
    Write-Host "Error starting server: $_" -ForegroundColor Red
}

# This part will only execute if the server is stopped
Write-Host ""
Write-Host "Server stopped" -ForegroundColor Yellow
Read-Host "Press Enter to exit"
