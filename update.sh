#!/bin/bash

echo "🔄 Starting SMARD update process..."

# Check if we're in the right directory
if [ ! -f "docker-compose.yml" ]; then
    echo "❌ Error: docker-compose.yml not found. Please run this script from the project root directory."
    exit 1
fi

# Check if Docker and Docker Compose are installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

if ! command -v docker compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Backup current .env if it exists
if [ -f .env ]; then
    echo "💾 Backing up current .env file..."
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
    echo "✅ .env backed up"
fi

# Stash any local changes (optional - uncomment if needed)
# echo "📦 Stashing local changes..."
# git stash

# Pull latest changes from repository
echo "📥 Pulling latest changes from repository..."
if git pull origin main; then
    echo "✅ Successfully pulled latest changes"
else
    echo "❌ Failed to pull changes. Please check your git configuration."
    exit 1
fi

# Check if there are any new environment variables
if [ -f env.example ] && [ -f .env ]; then
    echo "🔍 Checking for new environment variables..."
    # Compare env.example with .env and show differences
    diff env.example .env | grep "^<" | sed 's/^< //' | while read line; do
        if [[ $line =~ ^[A-Z_]+= ]]; then
            echo "⚠️  New env var found: $line"
        fi
    done
fi

# Stop containers gracefully
echo "🛑 Stopping containers..."
docker compose down

# Build and start containers with new code
echo "🐳 Building and starting containers with updated code..."
docker compose up -d --build

# Wait for PostgreSQL to be ready
echo "⏳ Waiting for PostgreSQL to be ready..."
sleep 10

# Run Laravel maintenance commands
echo "🔧 Running Laravel maintenance commands..."
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear
docker compose exec app php artisan cache:clear

# Run migrations if needed
echo "🗄️ Checking for database migrations..."
if docker compose exec app php artisan migrate:status | grep -q "No migrations"; then
    echo "✅ No pending migrations"
else
    echo "🔄 Running database migrations..."
    docker compose exec app php artisan migrate --force
fi

# Run seeders if needed (optional - uncomment if needed)
# echo "🌱 Running database seeders..."
# docker compose exec app php artisan db:seed --force

# Optimize for production
echo "⚡ Optimizing for production..."
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache

# Check application health
echo "🏥 Checking application health..."
if curl -f http://192.168.10.54 > /dev/null 2>&1; then
    echo "✅ Application is running successfully"
else
    echo "⚠️  Application might not be fully ready yet. Please check manually."
fi

# Show container status
echo "📊 Container status:"
docker compose ps

echo "✅ Update completed successfully!"
echo "🌐 Your application is available at: http://192.168.10.54"
echo ""
echo "📋 Useful commands:"
echo "  - View logs: docker compose logs -f"
echo "  - Check specific service: docker compose logs -f app"
echo "  - Restart services: docker compose restart"
echo "  - Access app container: docker compose exec app bash"
echo ""
echo "🔄 If you need to rollback:"
echo "  - git log --oneline -5"
echo "  - git checkout <previous-commit>"
echo "  - ./update.sh"
