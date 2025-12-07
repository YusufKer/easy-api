#!/bin/bash

# Database Setup Script
# Creates the database schema from SQL migration files

set -e

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Load environment variables
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | sed 's/\r$//' | xargs)
elif [ -f .env.development ]; then
    export $(cat .env.development | grep -v '^#' | sed 's/\r$//' | xargs)
else
    echo -e "${RED}Error: No .env file found${NC}"
    exit 1
fi

# Database connection details
DB_HOST="${DB_HOST:-localhost}"
DB_NAME="${DB_NAME}"
DB_USER="${DB_USER}"
DB_PASS="${DB_PASSWORD}"
DB_PORT="${DB_PORT:-3306}"

if [ -z "$DB_NAME" ]; then
    echo -e "${RED}Error: DB_NAME not set in environment${NC}"
    exit 1
fi

SQL_DIR="db/sql"

echo "========================================="
echo "Database Migration Runner"
echo "========================================="
echo "Host: $DB_HOST:$DB_PORT"
echo "Database: $DB_NAME"
echo "User: $DB_USER"
echo ""

# Check if mysql client is available
if ! command -v mysql &> /dev/null; then
    echo -e "${RED}Error: mysql client not found. Please install MySQL client.${NC}"
    exit 1
fi

# Check if SQL directory exists
if [ ! -d "$SQL_DIR" ]; then
    echo -e "${RED}Error: SQL directory not found: $SQL_DIR${NC}"
    exit 1
fi

# Count SQL files
SQL_FILES=$(ls -1 $SQL_DIR/*.sql 2>/dev/null | wc -l | tr -d ' ')
if [ "$SQL_FILES" -eq 0 ]; then
    echo -e "${YELLOW}Warning: No SQL files found in $SQL_DIR${NC}"
    exit 0
fi

echo -e "Found ${GREEN}$SQL_FILES${NC} migration files"
echo ""

# Run all SQL files in order
SUCCESS=0
FAILED=0

for sql_file in $(ls -1 $SQL_DIR/*.sql 2>/dev/null | sort); do
    filename=$(basename "$sql_file")
    echo -n "Running: $filename ... "
    
    if mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$sql_file" 2>/dev/null; then
        echo -e "${GREEN}✓${NC}"
        ((SUCCESS++))
    else
        echo -e "${RED}✗${NC}"
        ((FAILED++))
        echo -e "${RED}Failed to execute: $filename${NC}"
        echo "Attempting to show error..."
        mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$sql_file"
        exit 1
    fi
done

echo ""
echo "========================================="
echo -e "${GREEN}Migration Summary${NC}"
echo "========================================="
echo -e "Successful: ${GREEN}$SUCCESS${NC}"
echo -e "Failed: ${RED}$FAILED${NC}"
echo "========================================="

if [ "$FAILED" -eq 0 ]; then
    echo -e "${GREEN}All migrations completed successfully!${NC}"
    exit 0
else
    echo -e "${RED}Some migrations failed!${NC}"
    exit 1
fi
