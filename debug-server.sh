#!/bin/bash

# Start PHP development server with debugging enabled
echo "Starting PHP development server with debugging..."
echo "Server will be available at http://localhost:8080"
echo "Xdebug logs at /tmp/xdebug.log"

# Set debugging environment
export XDEBUG_MODE=debug,develop
export XDEBUG_CONFIG="client_host=127.0.0.1 client_port=9003 start_with_request=yes"

# Start server
php -S localhost:8080 -t . index.php