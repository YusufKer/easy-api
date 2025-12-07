#!/bin/bash
# Seed Database Script
# Populates the database with initial data

set -e

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Load environment variables
if [ -f .env ]; then
    set -a
    source .env
    set +a
elif [ -f .env.development ]; then
    set -a
    source .env.development
    set +a
else
    echo -e "${RED}Error: No .env file found!${NC}"
    exit 1
fi

# Check required environment variables
if [ -z "$DB_HOST" ] || [ -z "$DB_NAME" ] || [ -z "$DB_USER" ]; then
    echo -e "${RED}Error: Missing required database environment variables!${NC}"
    echo "Required: DB_HOST, DB_NAME, DB_USER, DB_PASSWORD"
    exit 1
fi

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Database Seeding Script${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "Host: ${YELLOW}$DB_HOST${NC}"
echo -e "Database: ${YELLOW}$DB_NAME${NC}"
echo -e "User: ${YELLOW}$DB_USER${NC}"
echo ""

# Seed directory
SEED_DIR="db/seeds"

if [ ! -d "$SEED_DIR" ]; then
    echo -e "${RED}Error: Seed directory not found: $SEED_DIR${NC}"
    exit 1
fi

# Count seed files
SEED_COUNT=$(find "$SEED_DIR" -name "*.sql" -type f | wc -l | tr -d ' ')

if [ "$SEED_COUNT" -eq 0 ]; then
    echo -e "${YELLOW}No seed files found in $SEED_DIR${NC}"
    exit 0
fi

echo -e "${GREEN}Found $SEED_COUNT seed file(s)${NC}"
echo ""

# Run each seed file in order
SUCCESS_COUNT=0
FAIL_COUNT=0

for sql_file in $(find "$SEED_DIR" -name "*.sql" -type f | sort); do
    filename=$(basename "$sql_file")
    echo -e "${BLUE}Running:${NC} $filename"
    
    if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < "$sql_file" 2>&1; then
        echo -e "${GREEN}✓ Success${NC}"
        ((SUCCESS_COUNT++))
    else
        echo -e "${RED}✗ Failed${NC}"
        ((FAIL_COUNT++))
    fi
    echo ""
done

# Summary
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Seeding Summary${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "Total: $SEED_COUNT"
echo -e "${GREEN}Success: $SUCCESS_COUNT${NC}"
if [ "$FAIL_COUNT" -gt 0 ]; then
    echo -e "${RED}Failed: $FAIL_COUNT${NC}"
    exit 1
else
    echo -e "${GREEN}All seeds executed successfully!${NC}"
fi
