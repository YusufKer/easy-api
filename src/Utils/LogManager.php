<?php

namespace App\Utils;

class LogManager {
    
    public static function setupLogrotate() {
        $projectPath = dirname(__DIR__, 2);
        $logsPath = $projectPath . '/logs';
        
        $logrotateConfig = "
# Logrotate configuration for easy-api
$logsPath/*.log {
    daily
    rotate 7
    compress
    delaycompress
    missingok
    notifempty
    create 644 www-data www-data
    size 10M
    postrotate
        # Optional: restart your application if needed
        # systemctl reload php-fpm
    endscript
}
";
        
        echo "Add this to your logrotate configuration:\n";
        echo $logrotateConfig;
        echo "\nTo add to system logrotate:\n";
        echo "sudo nano /etc/logrotate.d/easy-api\n";
        echo "Then paste the above configuration.\n";
    }
    
    public static function cleanupLogs($daysToKeep = 7) {
        DebugLogger::cleanOldLogs($daysToKeep);
        echo "Cleaned up logs older than {$daysToKeep} days\n";
    }
    
    public static function getLogStats() {
        $projectPath = dirname(__DIR__, 2);
        $logsPath = $projectPath . '/logs';
        
        if (!is_dir($logsPath)) {
            echo "Logs directory not found: $logsPath\n";
            return;
        }
        
        $files = glob($logsPath . '/*.log*');
        $totalSize = 0;
        
        echo "Log File Statistics:\n";
        echo str_repeat("-", 60) . "\n";
        echo sprintf("%-30s %10s %15s\n", "File", "Size", "Last Modified");
        echo str_repeat("-", 60) . "\n";
        
        foreach ($files as $file) {
            $size = filesize($file);
            $totalSize += $size;
            $modified = date('Y-m-d H:i:s', filemtime($file));
            
            echo sprintf("%-30s %10s %15s\n", 
                basename($file), 
                self::formatBytes($size), 
                $modified
            );
        }
        
        echo str_repeat("-", 60) . "\n";
        echo sprintf("%-30s %10s\n", "TOTAL", self::formatBytes($totalSize));
        echo "\n";
    }
    
    private static function formatBytes($size, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}