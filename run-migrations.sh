#!/bin/bash

# Simple SQL Migration Runner
# Runs all .sql files in db/sql directory in alphabetical order

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

# Run all SQL files in order
for sql_file in $(ls -1 $SQL_DIR/*.sql 2>/dev/null | sort); do
    echo "Running: $(basename $sql_file)"
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$sql_file"
    if [ $? -eq 0 ]; then
        echo "✓ Success"
    else
        echo "✗ Failed"
        exit 1
    fi
    echo ""
done

echo "========================================="
echo "All migrations completed successfully!"
echo "========================================="
