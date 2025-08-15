# SMARD Deployment Script for Windows PowerShell
# Run as Administrator if needed

Write-Host "🚀 Starting SMARD deployment..." -ForegroundColor Green

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

# Create .env file if it doesn't exist
if (-not (Test-Path ".env")) {
    Write-Host "📝 Creating .env file..." -ForegroundColor Yellow
    Copy-Item "env.example" ".env"
    Write-Host "✅ .env file created" -ForegroundColor Green
} else {
    Write-Host "✅ .env file already exists" -ForegroundColor Green
}

# Stop and remove existing containers
Write-Host "🔄 Stopping existing containers..." -ForegroundColor Yellow
docker-compose down

# Build and start containers
Write-Host "🐳 Building and starting containers..." -ForegroundColor Yellow
docker-compose up -d --build

# Wait for PostgreSQL to be ready
Write-Host "⏳ Waiting for PostgreSQL to be ready..." -ForegroundColor Yellow
Start-Sleep -Seconds 15

# Run Laravel setup commands
Write-Host "🔧 Running Laravel setup..." -ForegroundColor Yellow
docker-compose exec app php artisan key:generate --no-interaction
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Run migrations
Write-Host "🗄️ Running database migrations..." -ForegroundColor Yellow
docker-compose exec app php artisan migrate --force

# Run seeders
Write-Host "🌱 Running database seeders..." -ForegroundColor Yellow
docker-compose exec app php artisan db:seed --force

Write-Host "✅ Deployment completed!" -ForegroundColor Green
Write-Host "🌐 Your application is now available at: http://192.168.10.54" -ForegroundColor Cyan
Write-Host "📊 PostgreSQL is available at: localhost:5432" -ForegroundColor Cyan
Write-Host ""
Write-Host "📋 Useful commands:" -ForegroundColor Yellow
Write-Host "  - View logs: docker-compose logs -f" -ForegroundColor White
Write-Host "  - Stop services: docker-compose down" -ForegroundColor White
Write-Host "  - Restart services: docker-compose restart" -ForegroundColor White
Write-Host "  - Access app container: docker-compose exec app bash" -ForegroundColor White
