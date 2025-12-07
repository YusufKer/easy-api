#!/bin/bash

# Smart SQL Migration Runner
# Tracks which migrations have been run and only runs new ones

set -e

# Load environment variables
if [ -f .env ]; then
    set -a
    source .env
    set +a
elif [ -f .env.development ]; then
    set -a
    source .env.development
    set +a
fi

# Database connection details
DB_HOST="${DB_HOST:-localhost}"
DB_NAME="${DB_NAME}"
DB_USER="${DB_USER}"
DB_PASS="${DB_PASSWORD}"

if [ -z "$DB_NAME" ]; then
    echo "Error: DB_NAME not set in environment"
    exit 1
fi

SQL_DIR="db/sql"

echo "========================================="
echo "SQL Migration Runner"
echo "========================================="
echo "Database: $DB_NAME"
echo "Host: $DB_HOST"
echo ""

# Create migrations tracking table if it doesn't exist
mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" << 'EOF'
CREATE TABLE IF NOT EXISTS schema_migrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration_name VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_migration_name (migration_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
EOF

if [ $? -ne 0 ]; then
    echo "✗ Failed to create migrations tracking table"
    exit 1
fi

# Run all SQL files in order
executed_count=0
skipped_count=0

for sql_file in $(ls -1 $SQL_DIR/*.sql 2>/dev/null | sort); do
    migration_name=$(basename "$sql_file")
    
    # Check if migration has already been run
    already_run=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -sN -e \
        "SELECT COUNT(*) FROM schema_migrations WHERE migration_name = '$migration_name'")
    
    if [ "$already_run" -gt 0 ]; then
        echo "⊘ Skipping: $migration_name (already executed)"
        skipped_count=$((skipped_count + 1))
    else
        echo "Running: $migration_name"
        mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$sql_file"
        
        if [ $? -eq 0 ]; then
            # Record the migration
            mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e \
                "INSERT INTO schema_migrations (migration_name) VALUES ('$migration_name')"
            echo "✓ Success"
            executed_count=$((executed_count + 1))
        else
            echo "✗ Failed"
            exit 1
        fi
    fi
    echo ""
done

echo "========================================="
echo "Migration Summary:"
echo "  Executed: $executed_count"
echo "  Skipped:  $skipped_count"
echo "========================================="
