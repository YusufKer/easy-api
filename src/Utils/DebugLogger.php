<?php

namespace App\Utils;

class DebugLogger {
    private static $logFile = __DIR__ . '/logs/debug.log';
    
    public static function log($message, $context = []) {
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
}