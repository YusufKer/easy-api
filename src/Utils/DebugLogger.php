<?php

namespace App\Utils;

class DebugLogger {
    private static $logFile = __DIR__ . '/logs/debug.log';
    private static $maxFileSize = 10 * 1024 * 1024; // 10MB
    private static $maxFiles = 5; // Keep 5 rotated files
    
    public static function log($message, $context = []) {
        // Rotate logs if needed before writing
        self::rotateLogsIfNeeded();
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logEntry = "[{$timestamp}] {$message}{$contextStr}" . PHP_EOL;
        
        // Ensure logs directory exists
        $logsDir = dirname(self::$logFile);
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }
        
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also output to error log in development
        if (getenv('APP_ENV') !== 'production') {
            error_log($logEntry);
        }
    }
    
    public static function logRequest($request) {
        self::log('HTTP Request', [
            'method' => $request->getMethod(),
            'uri' => $request->getUri()->getPath(),
            'query' => $request->getQueryParams(),
            'headers' => $request->getHeaders(),
            'body' => $request->getParsedBody()
        ]);
    }
    
    public static function logResponse($response, $statusCode) {
        self::log('HTTP Response', [
            'status' => $statusCode,
            'headers' => $response->getHeaders()
        ]);
    }
    
    public static function logError($error, $context = []) {
        self::log('ERROR: ' . $error, $context);
    }
    
    public static function logSQL($query, $params = []) {
        self::log('SQL Query', [
            'query' => $query,
            'params' => $params
        ]);
    }
    
    private static function rotateLogsIfNeeded() {
        if (!file_exists(self::$logFile)) {
            return;
        }
        
        // Check if log file exceeds max size
        if (filesize(self::$logFile) > self::$maxFileSize) {
            self::rotateLogs();
        }
    }
    
    private static function rotateLogs() {
        $logsDir = dirname(self::$logFile);
        $baseName = basename(self::$logFile, '.log');
        
        // Remove oldest log file if it exists
        $oldestFile = $logsDir . '/' . $baseName . '.' . self::$maxFiles . '.log';
        if (file_exists($oldestFile)) {
            unlink($oldestFile);
        }
        
        // Rotate existing files
        for ($i = self::$maxFiles - 1; $i >= 1; $i--) {
            $oldFile = $logsDir . '/' . $baseName . '.' . $i . '.log';
            $newFile = $logsDir . '/' . $baseName . '.' . ($i + 1) . '.log';
            
            if (file_exists($oldFile)) {
                rename($oldFile, $newFile);
            }
        }
        
        // Move current log to .1
        $firstRotatedFile = $logsDir . '/' . $baseName . '.1.log';
        rename(self::$logFile, $firstRotatedFile);
    }
    
    public static function cleanOldLogs($daysToKeep = 7) {
        $logsDir = dirname(self::$logFile);
        $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);
        
        if (is_dir($logsDir)) {
            $files = scandir($logsDir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                
                $filePath = $logsDir . '/' . $file;
                if (is_file($filePath) && filemtime($filePath) < $cutoffTime) {
                    unlink($filePath);
                }
            }
        }
    }
    
    public static function setMaxFileSize($sizeInBytes) {
        self::$maxFileSize = $sizeInBytes;
    }
    
    public static function setMaxFiles($maxFiles) {
        self::$maxFiles = $maxFiles;
    }
}