#!/bin/bash
set -e

echo "=== Starting entrypoint script ==="
echo "Current user: $(whoami)"
echo "User ID: $(id -u)"
echo "Group ID: $(id -g)"

# Function to log messages
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

# Set environment variables
export PHP_INI_DIR="/usr/local/etc/php"

log "Creating required directories..."
# Create necessary directories if they don't exist
mkdir -p /app/log /app/uploads /app/includes/cache

log "Setting up permissions..."
# Set ownership (nobody user, nogroup group)
chown -R nobody:nogroup /app/log /app/uploads /app/includes

# Fix the error_log file in /app/ if it exists
if [ -f /app/error_log ]; then
    log "Fixing error_log permissions..."
    chown nobody:nogroup /app/error_log
    chmod 666 /app/error_log
fi

# Set safe permissions for directories
chmod 755 /app/includes /app/uploads /app/log /app/includes/cache

log "Setting up log file..."
# Ensure log file exists and has correct permissions
touch /app/log/payment_errors.log
chown nobody:nogroup /app/log/payment_errors.log
chmod 666 /app/log/payment_errors.log

log "Setting up dbconnect.php..."
# Make dbconnect.php writable for installation (if it exists)
if [ -f /app/includes/dbconnect.php ]; then
    chown nobody:nogroup /app/includes/dbconnect.php
    chmod 666 /app/includes/dbconnect.php
    log "dbconnect.php permissions set"
else
    log "WARNING: dbconnect.php not found"
fi

# Fix all PHP files in includes directory
if [ -d /app/includes ]; then
    log "Fixing PHP files permissions..."
    find /app/includes -type f -name "*.php" -exec chown nobody:nogroup {} \;
    find /app/includes -type f -name "*.php" -exec chmod 644 {} \;
    log "All PHP files in includes directory fixed"
fi

# Verify permissions
log "=== Current permissions ==="
ls -la /app/ | grep -E 'includes|uploads|log|error_log'
ls -la /app/log/payment_errors.log 2>/dev/null || echo "payment_errors.log not found"
ls -la /app/includes/dbconnect.php 2>/dev/null || echo "dbconnect.php not found"

log "=== Entrypoint script completed ==="

# Execute the main command
log "Starting command: $@"
exec "$@"
