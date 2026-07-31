# Setup script for EsteliPOS on Windows
# Usage: Open PowerShell as Administrator and run: .\setup-windows.ps1

function Check-Command {
    param(
        [string]$cmd
    )
    $proc = Get-Command $cmd -ErrorAction SilentlyContinue
    return $null -ne $proc
}

Write-Host "Checking PHP..."
if (-not (Check-Command php)) {
    Write-Host "PHP not found. Please install PHP 8.2+ on Windows or use WSL. See: https://www.php.net/downloads.php" -ForegroundColor Yellow
} else {
    php -v
}

Write-Host "Checking Composer..."
if (-not (Check-Command composer)) {
    Write-Host "Composer not found. Please install Composer: https://getcomposer.org/download/" -ForegroundColor Yellow
} else {
    composer --version
}

# If composer available, install PHP deps
if (Check-Command composer) {
    Write-Host "Installing Composer dependencies..."
    composer install
}

# Ensure .env exists
if (-not (Test-Path .env)) {
    if (Test-Path .env.example) {
        Copy-Item .env.example .env -Force
        Write-Host "Copied .env.example to .env"

        # Configure SQLite for easy local setup
        (Get-Content .env) -replace 'DB_CONNECTION=.*', 'DB_CONNECTION=sqlite' | Set-Content .env
        (Get-Content .env) -replace 'DB_DATABASE=.*', 'DB_DATABASE=database/database.sqlite' | Set-Content .env
        Write-Host "Configured .env to use SQLite (database/database.sqlite)"

        # Create SQLite file
        if (-not (Test-Path database)) { New-Item -ItemType Directory -Path database | Out-Null }
        if (-not (Test-Path database\database.sqlite)) { New-Item -ItemType File -Path database\database.sqlite | Out-Null; Write-Host "Created database/database.sqlite" }
    } else {
        Write-Host ".env.example not found. Please create a .env file manually." -ForegroundColor Red
    }
} else {
    Write-Host ".env already exists. Skipping copy." -ForegroundColor Green
}

# Generate app key
if (Check-Command php) {
    Write-Host "Generating app key..."
    php artisan key:generate
}

# Run migrations (optional prompt)
if (Check-Command php) {
    $runMigrate = Read-Host "Run migrations now? (y/N)"
    if ($runMigrate -match '^[yY]') {
        php artisan migrate
    } else {
        Write-Host "Skipping migrations. You can run 'php artisan migrate' later." -ForegroundColor Yellow
    }
}

Write-Host "Setup complete. To start the dev server run: php artisan serve" -ForegroundColor Cyan
