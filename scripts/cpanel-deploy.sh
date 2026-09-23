#!/bin/bash
# ==============================================================================
# Krushi Baandhava — Linux cPanel Production Deployment Script
# Target: cPanel Shared Hosting (PHP 8.2+, MySQL 8.0, Apache)
# ==============================================================================

set -e

COLOR_GREEN='\033[0;32m'
COLOR_AMBER='\033[0;33m'
COLOR_RED='\033[0;31m'
COLOR_BLUE='\033[0;34m'
COLOR_RESET='\033[0m'

echo -e "${COLOR_BLUE}======================================================================${COLOR_RESET}"
echo -e "${COLOR_BLUE}    Krushi Baandhava — Automated cPanel Deployment Pipeline           ${COLOR_RESET}"
echo -e "${COLOR_BLUE}======================================================================${COLOR_RESET}"

# 1. Resolve Project Root
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_DIR"
echo -e "${COLOR_GREEN}✔ Project Directory:${COLOR_RESET} $PROJECT_DIR"

# 2. Detect PHP Binary (Prioritize cPanel MultiPHP / ea-php82 / ea-php83)
PHP_BIN="php"
if [ -f "/usr/local/bin/ea-php82" ]; then
    PHP_BIN="/usr/local/bin/ea-php82"
elif [ -f "/usr/local/bin/ea-php83" ]; then
    PHP_BIN="/usr/local/bin/ea-php83"
elif [ -f "/usr/bin/php8.2" ]; then
    PHP_BIN="/usr/bin/php8.2"
fi
echo -e "${COLOR_GREEN}✔ Using PHP binary:${COLOR_RESET} $PHP_BIN ($($PHP_BIN -v | head -n 1))"

# 3. Verify .env file exists
if [ ! -f "$PROJECT_DIR/.env" ]; then
    echo -e "${COLOR_RED}✘ Error: .env file missing in $PROJECT_DIR! Please create it before deploying.${COLOR_RESET}"
    exit 1
fi

# 4. Set Proper Directory Permissions
echo -e "${COLOR_AMBER}➜ Setting directory permissions for storage and bootstrap/cache...${COLOR_RESET}"
chmod -R 775 "$PROJECT_DIR/storage" "$PROJECT_DIR/bootstrap/cache"
echo -e "${COLOR_GREEN}✔ Permissions updated (775).${COLOR_RESET}"

# 5. Composer Dependencies (Optimized for Production)
if command -v composer &> /dev/null; then
    echo -e "${COLOR_AMBER}➜ Installing optimized Composer production dependencies...${COLOR_RESET}"
    composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
    echo -e "${COLOR_GREEN}✔ Composer autoloader optimized.${COLOR_RESET}"
else
    echo -e "${COLOR_AMBER}⚠ Notice: Composer CLI not detected in PATH. Skipping dependency install.${COLOR_RESET}"
fi

# 6. Storage Symlink Verification
echo -e "${COLOR_AMBER}➜ Linking storage directory to public disk...${COLOR_RESET}"
$PHP_BIN artisan storage:link || true

# 7. Execute Database Migrations
echo -e "${COLOR_AMBER}➜ Running database migrations (--force)...${COLOR_RESET}"
$PHP_BIN artisan migrate --force

# 8. Clear and Precompute Production Caches
echo -e "${COLOR_AMBER}➜ Optimizing Laravel route, config, and view caches...${COLOR_RESET}"
$PHP_BIN artisan optimize:clear
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan event:cache
echo -e "${COLOR_GREEN}✔ Application caches compiled.${COLOR_RESET}"

# 9. Run Production Health Check
echo -e "${COLOR_AMBER}➜ Running Krushi Baandhava Production Health Diagnostics...${COLOR_RESET}"
$PHP_BIN artisan app:health-check

echo -e "${COLOR_BLUE}======================================================================${COLOR_RESET}"
echo -e "${COLOR_GREEN}✔ Deployment Complete! Krushi Baandhava is live and operational.${COLOR_RESET}"
echo -e "${COLOR_BLUE}======================================================================${COLOR_RESET}"
echo -e "${COLOR_AMBER}Reminder — Ensure these cPanel Cron Jobs are configured:${COLOR_RESET}"
echo -e " 1) Laravel Scheduler (Every min):   * * * * * $PHP_BIN $PROJECT_DIR/artisan schedule:run >> /dev/null 2>&1"
echo -e " 2) Queue Worker (Every 5 mins):    */5 * * * * $PHP_BIN $PROJECT_DIR/artisan queue:work --stop-when-empty --tries=3 --timeout=120 >> /dev/null 2>&1"
echo ""
