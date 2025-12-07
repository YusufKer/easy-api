#!/bin/bash
# Database migration script - now using plain SQL files
# This is a compatibility wrapper that redirects to the new setup-database.sh

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Note: This project now uses plain SQL migrations instead of Phinx.${NC}"
echo ""

# If migrate or no command, run the SQL migrations
if [ "$1" == "migrate" ] || [ -z "$1" ]; then
    echo "Running SQL migrations..."
    exec "$SCRIPT_DIR/setup-database.sh"
elif [ "$1" == "status" ]; then
    echo "To check database status, connect to MySQL and run: SHOW TABLES;"
    echo "SQL files are located in: db/sql/"
    ls -1 db/sql/*.sql 2>/dev/null | sort
elif [ "$1" == "help" ] || [ "$1" == "--help" ] || [ "$1" == "-h" ]; then
    cat << 'HELP'
Database Migration Script
=========================

This project uses plain SQL files for migrations.
Migration files are located in: db/sql/

Commands:
    migrate             Run all SQL migrations (default)
    status              List available migration files
    help                Show this help message

To run migrations:
    ./migrate.sh
    # or
    ./setup-database.sh

To add a new migration:
    1. Create a new .sql file in db/sql/ with sequential numbering
       Example: db/sql/011_add_new_table.sql
    2. Run ./migrate.sh to apply it

For more details, see: db/sql/README.md
HELP
else
    echo -e "${RED}Unknown command: $1${NC}"
    echo "Run './migrate.sh help' for usage information"
    exit 1
fi
