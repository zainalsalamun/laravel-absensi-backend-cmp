#!/bin/bash

# ==========================================
# Face Recognition API - Quick Start Script
# ==========================================

echo "🚀 Laravel Absensi - Face Recognition Setup"
echo "==========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if Docker is running
if ! docker compose ps > /dev/null 2>&1; then
    echo -e "${YELLOW}⚠️  Docker containers not running${NC}"
    echo ""
    echo "Starting Docker containers..."
    docker compose up -d
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✅ Docker containers started${NC}"
    else
        echo -e "${RED}❌ Failed to start Docker containers${NC}"
        echo "Please check your Docker installation"
        exit 1
    fi
else
    echo -e "${GREEN}✅ Docker containers already running${NC}"
fi

echo ""
echo "📦 Running migrations..."
docker compose exec app php artisan migrate --force

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Migration failed${NC}"
    exit 1
fi

echo ""
echo "🌱 Running database seeder..."
docker compose exec app php artisan db:seed --class=DatabaseSeeder

echo ""
echo "🔗 Creating storage link..."
docker compose exec app php artisan storage:link 2>/dev/null || echo "Storage link already exists"

echo ""
echo "🧹 Clearing cache..."
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear

echo ""
echo -e "${GREEN}==========================================${NC}"
echo -e "${GREEN}✅ Setup Complete!${NC}"
echo -e "${GREEN}==========================================${NC}"
echo ""
echo "📝 Next Steps:"
echo "1. Open Postman and import: POSTMAN_Face_Recognition_Collection.json"
echo "2. Start testing with the collection"
echo ""
echo "🔐 Test Credentials:"
echo "   Email: testuser@example.com"
echo "   Password: password"
echo ""
echo "🌐 API Base URL: http://localhost:8000"
echo ""
echo "📖 Documentation:"
echo "   - TESTING_GUIDE.md (step-by-step testing guide)"
echo "   - FACE_RECOGNITION_README.md (implementation details)"
echo "   - API_DOCUMENTATION.md (full API docs)"
echo ""
echo "🧪 Quick Test with cURL:"
echo '   curl http://localhost:8000/api/login \'
echo '     -H "Content-Type: application/json" \'
echo '     -d '\''{"email":"testuser@example.com","password":"password"}'\'''
echo ""
