#!/bin/bash
set -e

# Script khởi tạo dự án lần đầu

echo "=== Khởi tạo dự án Nhắc Lịch Giỗ ==="

# Tạo Laravel project nếu chưa có
if [ ! -f "artisan" ]; then
    echo ">>> Tạo Laravel project..."
    docker run --rm \
        -v "$(pwd):/app" \
        -w /app \
        composer:latest \
        composer create-project laravel/laravel . --prefer-dist
fi

# Copy .env
if [ ! -f ".env" ]; then
    echo ">>> Copy .env..."
    cp .env.example .env
fi

# Build và khởi động containers
echo ">>> Build Docker images..."
docker compose build

echo ">>> Khởi động containers..."
docker compose up -d

# Chờ MySQL sẵn sàng
echo ">>> Chờ MySQL..."
sleep 5

# Generate app key
echo ">>> Generate APP_KEY..."
docker compose exec app php artisan key:generate

# Cài dependencies PHP
echo ">>> Cài composer dependencies..."
docker compose exec app composer install

echo ""
echo "=== Hoàn tất! ==="
echo "App chạy tại: http://localhost:8000"
echo ""
echo "Lệnh tiếp theo:"
echo "  docker compose exec app php artisan migrate --seed"
echo "  docker compose exec app php artisan breeze:install blade"
