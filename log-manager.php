#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use App\Utils\LogManager;
use App\Utils\DebugLogger;

$command = $argv[1] ?? 'help';

switch ($command) {
    case 'stats':
        LogManager::getLogStats();
        break;
        
    case 'cleanup':
        $days = (int)($argv[2] ?? 7);
        LogManager::cleanupLogs($days);
        break;
        
    case 'rotate':
        echo "Running manual log rotation...\n";
        // Create a large dummy entry to trigger rotation
        DebugLogger::log('Manual rotation test - this should trigger rotation if file is large enough');
        echo "Log rotation completed.\n";
        break;
        
    case 'config':
        LogManager::setupLogrotate();
        break;
        
    case 'help':
    default:
        echo "Easy API Log Management Tool\n\n";
        echo "Usage: php log-manager.php [command] [options]\n\n";
        echo "Commands:\n";
        echo "  stats              Show log file statistics\n";
        echo "  cleanup [days]     Clean up logs older than X days (default: 7)\n";
        echo "  rotate             Manually trigger log rotation\n";
        echo "  config             Show logrotate configuration\n";
        echo "  help               Show this help message\n\n";
        echo "Examples:\n";
        echo "  php log-manager.php stats\n";
        echo "  php log-manager.php cleanup 14\n";
        echo "  ./rotate-logs.sh   # Run shell script for system-level rotation\n";
        break;
}