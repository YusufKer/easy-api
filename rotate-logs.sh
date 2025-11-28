#!/bin/bash

# Log rotation and cleanup script for easy-api
# This script should be run via cron job

LOG_DIR="/Users/yusuf/Desktop/Projects/easy-api/logs"
MAX_SIZE="10M"  # Maximum size before rotation
DAYS_TO_KEEP=7  # Keep logs for 7 days

echo "Starting log rotation and cleanup..."

# Function to rotate a single log file
rotate_log() {
    local logfile="$1"
    local basename=$(basename "$logfile" .log)
    local dirname=$(dirname "$logfile")
    
    if [ -f "$logfile" ] && [ $(stat -f%z "$logfile" 2>/dev/null || echo 0) -gt $(( 10 * 1024 * 1024 )) ]; then
        echo "Rotating $logfile..."
        
        # Remove oldest backup if it exists
        [ -f "$dirname/${basename}.5.log" ] && rm "$dirname/${basename}.5.log"
        
        # Rotate existing backups
        for i in 4 3 2 1; do
            [ -f "$dirname/${basename}.$i.log" ] && mv "$dirname/${basename}.$i.log" "$dirname/${basename}.$((i+1)).log"
        done
        
        # Move current log to .1
        mv "$logfile" "$dirname/${basename}.1.log"
        
        echo "Rotated $logfile"
    fi
}

# Create logs directory if it doesn't exist
mkdir -p "$LOG_DIR"

# Rotate main log files
rotate_log "$LOG_DIR/debug.log"
rotate_log "$LOG_DIR/error.log"
rotate_log "$LOG_DIR/access.log"

# Clean up old log files (older than specified days)
echo "Cleaning up logs older than $DAYS_TO_KEEP days..."
find "$LOG_DIR" -name "*.log*" -type f -mtime +$DAYS_TO_KEEP -delete

echo "Log rotation and cleanup completed."