# SMARD Update Script for Windows PowerShell
# Run as Administrator if needed

Write-Host "🔄 Starting SMARD update process..." -ForegroundColor Green

# Check if we're in the right directory
if (-not (Test-Path "docker-compose.yml")) {
    Write-Host "❌ Error: docker-compose.yml not found. Please run this script from the project root directory." -ForegroundColor Red
    exit 1
}

# Check if Docker is installed
try {
    docker --version | Out-Null
    Write-Host "✅ Docker is installed" -ForegroundColor Green
} catch {
    Write-Host "❌ Docker is not installed. Please install Docker Desktop first." -ForegroundColor Red
    exit 1
}

# Check if Docker Compose is available
try {
    docker-compose --version | Out-Null
    Write-Host "✅ Docker Compose is available" -ForegroundColor Green
} catch {
    Write-Host "❌ Docker Compose is not available. Please install Docker Compose." -ForegroundColor Red
    exit 1
}

# Backup current .env if it exists
if (Test-Path ".env") {
    Write-Host "💾 Backing up current .env file..." -ForegroundColor Yellow
    $timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
    Copy-Item ".env" ".env.backup.$timestamp"
    Write-Host "✅ .env backed up" -ForegroundColor Green
}

# Stash any local changes (optional - uncomment if needed)
# Write-Host "📦 Stashing local changes..." -ForegroundColor Yellow
# git stash

# Pull latest changes from repository
Write-Host "📥 Pulling latest changes from repository..." -ForegroundColor Yellow
try {
    git pull origin main
    Write-Host "✅ Successfully pulled latest changes" -ForegroundColor Green
} catch {
    Write-Host "❌ Failed to pull changes. Please check your git configuration." -ForegroundColor Red
    exit 1
}

# Check if there are any new environment variables
if ((Test-Path "env.example") -and (Test-Path ".env")) {
    Write-Host "🔍 Checking for new environment variables..." -ForegroundColor Yellow
    # This is a simplified check - you might want to implement more sophisticated comparison
    $envExample = Get-Content "env.example"
    $currentEnv = Get-Content ".env"
    
    foreach ($line in $envExample) {
        if ($line -match "^[A-Z_]+=" -and $line -notin $currentEnv) {
            Write-Host "⚠️  New env var found: $line" -ForegroundColor Yellow
        }
    }
}

# Stop containers gracefully
Write-Host "🛑 Stopping containers..." -ForegroundColor Yellow
docker-compose down

# Build and start containers with new code
Write-Host "🐳 Building and starting containers with updated code..." -ForegroundColor Yellow
docker-compose up -d --build

# Wait for PostgreSQL to be ready
Write-Host "⏳ Waiting for PostgreSQL to be ready..." -ForegroundColor Yellow
Start-Sleep -Seconds 15

# Run Laravel maintenance commands
Write-Host "🔧 Running Laravel maintenance commands..." -ForegroundColor Yellow
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan cache:clear

# Run migrations if needed
Write-Host "🗄️ Checking for database migrations..." -ForegroundColor Yellow
$migrationStatus = docker-compose exec app php artisan migrate:status
if ($migrationStatus -match "No migrations") {
    Write-Host "✅ No pending migrations" -ForegroundColor Green
} else {
    Write-Host "🔄 Running database migrations..." -ForegroundColor Yellow
    docker-compose exec app php artisan migrate --force
}

# Run seeders if needed (optional - uncomment if needed)
# Write-Host "🌱 Running database seeders..." -ForegroundColor Yellow
# docker-compose exec app php artisan db:seed --force

# Optimize for production
Write-Host "⚡ Optimizing for production..." -ForegroundColor Yellow
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Check application health
Write-Host "🏥 Checking application health..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://192.168.10.54" -UseBasicParsing -TimeoutSec 10
    if ($response.StatusCode -eq 200) {
        Write-Host "✅ Application is running successfully" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Application responded with status: $($response.StatusCode)" -ForegroundColor Yellow
    }
} catch {
    Write-Host "⚠️  Application might not be fully ready yet. Please check manually." -ForegroundColor Yellow
}

# Show container status
Write-Host "📊 Container status:" -ForegroundColor Yellow
docker-compose ps

Write-Host "✅ Update completed successfully!" -ForegroundColor Green
Write-Host "🌐 Your application is available at: http://192.168.10.54" -ForegroundColor Cyan
Write-Host ""
Write-Host "📋 Useful commands:" -ForegroundColor Yellow
Write-Host "  - View logs: docker-compose logs -f" -ForegroundColor White
Write-Host "  - Check specific service: docker-compose logs -f app" -ForegroundColor White
Write-Host "  - Restart services: docker-compose restart" -ForegroundColor White
Write-Host "  - Access app container: docker-compose exec app bash" -ForegroundColor White
Write-Host ""
Write-Host "🔄 If you need to rollback:" -ForegroundColor Yellow
Write-Host "  - git log --oneline -5" -ForegroundColor White
Write-Host "  - git checkout <previous-commit>" -ForegroundColor White
Write-Host "  - .\update.ps1" -ForegroundColor White
