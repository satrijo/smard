#!/bin/bash

echo "🚀 Starting SMARD deployment..."

# Check if Docker and Docker Compose are installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file..."
    cp env.example .env
    echo "✅ .env file created"
else
    echo "✅ .env file already exists"
fi

# Set proper permissions
echo "🔧 Setting permissions..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Build and start containers
echo "🐳 Building and starting containers..."
docker compose down
docker compose build --no-cache
docker compose up -d

# Wait for PostgreSQL to be ready
echo "⏳ Waiting for PostgreSQL to be ready..."
sleep 10

# Run Laravel setup commands
echo "🔧 Running Laravel setup..."
docker compose exec app php artisan key:generate --no-interaction
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache

# Run migrations
echo "🗄️ Running database migrations..."
docker compose exec app php artisan migrate --force

# Run seeders if needed
echo "🌱 Running database seeders..."
docker compose exec app php artisan db:seed --force

echo "✅ Deployment completed!"
echo "🌐 Your application is now available at: http://192.168.10.54"
echo "📊 PostgreSQL is available at: localhost:5432"
echo ""
echo "📋 Useful commands:"
echo "  - View logs: docker-compose logs -f"
echo "  - Stop services: docker-compose down"
echo "  - Restart services: docker-compose restart"
echo "  - Access app container: docker-compose exec app bash"
