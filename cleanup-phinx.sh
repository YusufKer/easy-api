#!/bin/bash
# Cleanup script to remove old Phinx-related files

echo "========================================="
echo "Phinx Cleanup Script"
echo "========================================="
echo ""
echo "This script will remove old Phinx-related files that are no longer needed."
echo ""
echo "Files to be removed:"
echo "  - db/migrations/ (old Phinx PHP files)"
echo "  - phinx.php (Phinx configuration)"
echo "  - mark-migrations-complete.php (Phinx helper script)"
echo ""
read -p "Do you want to proceed? (y/N) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo ""
    echo "Removing old Phinx files..."
    
    if [ -d "db/migrations" ]; then
        rm -rf db/migrations
        echo "✓ Removed db/migrations/"
    fi
    
    if [ -f "phinx.php" ]; then
        rm phinx.php
        echo "✓ Removed phinx.php"
    fi
    
    if [ -f "mark-migrations-complete.php" ]; then
        rm mark-migrations-complete.php
        echo "✓ Removed mark-migrations-complete.php"
    fi
    
    echo ""
    echo "========================================="
    echo "Cleanup complete!"
    echo "========================================="
    echo ""
    echo "Your new SQL migrations are in: db/sql/"
    echo "To run them: ./setup-database.sh"
else
    echo ""
    echo "Cleanup cancelled. No files were removed."
fi
