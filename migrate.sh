#!/bin/bash
# Migration helper script for easy-api
# This script provides common Phinx migration commands

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHINX="$SCRIPT_DIR/vendor/bin/phinx"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Function to print colored output
print_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if Phinx is installed
if [ ! -f "$PHINX" ]; then
    print_error "Phinx not found. Please run 'composer install' first."
    exit 1
fi

# Display help
show_help() {
    cat << EOF
Migration Helper Script
=======================

Usage: ./migrate.sh [command] [options]

Commands:
    status              Show migration status
    migrate             Run all pending migrations
    rollback            Rollback the last migration
    rollback-all        Rollback all migrations
    create <name>       Create a new migration file
    seed                Run database seeders
    help                Show this help message

Options:
    -e, --env <env>     Specify environment (development, testing, production)
                        Default: development

Examples:
    ./migrate.sh status
    ./migrate.sh migrate
    ./migrate.sh migrate -e production
    ./migrate.sh rollback
    ./migrate.sh create AddEmailVerifiedToUsers
    ./migrate.sh seed

EOF
}

# Parse command and environment
COMMAND=${1:-help}
ENVIRONMENT="development"

# Parse options
shift
while [[ $# -gt 0 ]]; do
    case $1 in
        -e|--env)
            ENVIRONMENT="$2"
            shift 2
            ;;
        *)
            MIGRATION_NAME="$1"
            shift
            ;;
    esac
done

# Execute commands
case $COMMAND in
    status)
        print_info "Checking migration status for environment: $ENVIRONMENT"
        $PHINX status -e $ENVIRONMENT
        ;;
    migrate)
        print_info "Running migrations for environment: $ENVIRONMENT"
        $PHINX migrate -e $ENVIRONMENT
        if [ $? -eq 0 ]; then
            print_info "Migrations completed successfully"
        else
            print_error "Migration failed"
            exit 1
        fi
        ;;
    rollback)
        print_warning "Rolling back last migration for environment: $ENVIRONMENT"
        $PHINX rollback -e $ENVIRONMENT
        if [ $? -eq 0 ]; then
            print_info "Rollback completed successfully"
        else
            print_error "Rollback failed"
            exit 1
        fi
        ;;
    rollback-all)
        print_warning "Rolling back ALL migrations for environment: $ENVIRONMENT"
        read -p "Are you sure? This will drop all tables! (yes/no): " CONFIRM
        if [ "$CONFIRM" == "yes" ]; then
            $PHINX rollback -e $ENVIRONMENT -t 0
            if [ $? -eq 0 ]; then
                print_info "All migrations rolled back successfully"
            else
                print_error "Rollback failed"
                exit 1
            fi
        else
            print_info "Rollback cancelled"
        fi
        ;;
    create)
        if [ -z "$MIGRATION_NAME" ]; then
            print_error "Migration name is required"
            echo "Usage: ./migrate.sh create <MigrationName>"
            exit 1
        fi
        print_info "Creating new migration: $MIGRATION_NAME"
        $PHINX create $MIGRATION_NAME
        ;;
    seed)
        print_info "Running seeders for environment: $ENVIRONMENT"
        $PHINX seed:run -e $ENVIRONMENT
        ;;
    help)
        show_help
        ;;
    *)
        print_error "Unknown command: $COMMAND"
        show_help
        exit 1
        ;;
esac
