#!/bin/bash

# Script to copy database schema from production DB to test DB
# Usage: ./copy-schema-to-test.sh

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}📋 Database Schema Copy Script${NC}"
echo "================================="

# Prompt for database names and password
read -p "Enter your PRODUCTION database name: " PROD_DB
read -p "Enter your TEST database name: " TEST_DB
read -sp "Enter MySQL root password: " MYSQL_PASSWORD
echo ""

# Confirm before proceeding
echo ""
echo -e "${YELLOW}⚠️  This will:${NC}"
echo "   1. Export schema from: $PROD_DB"
echo "   2. Import schema into: $TEST_DB"
echo ""
read -p "Continue? (y/n): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${RED}❌ Cancelled${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}⏳ Exporting schema from $PROD_DB...${NC}"

# Export schema (structure only, no data)
docker exec -i mysql mysqldump -uroot -p"$MYSQL_PASSWORD" --no-data "$PROD_DB" > /tmp/schema.sql

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Schema exported successfully${NC}"
else
    echo -e "${RED}❌ Failed to export schema${NC}"
    exit 1
fi

echo -e "${YELLOW}⏳ Importing schema into $TEST_DB...${NC}"

# Import schema into test database
docker exec -i mysql mysql -uroot -p"$MYSQL_PASSWORD" "$TEST_DB" < /tmp/schema.sql

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Schema imported successfully${NC}"
    rm /tmp/schema.sql
    echo -e "${GREEN}🎉 Done! Test database '$TEST_DB' now has the same structure as '$PROD_DB'${NC}"
else
    echo -e "${RED}❌ Failed to import schema${NC}"
    rm /tmp/schema.sql
    exit 1
fi
