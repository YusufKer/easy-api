<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\Logger;

class LogsController
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get logs from a specific log file
     * GET /api/logs?type=access&lines=100&date=2025-12-05
     */
    public function getLogs(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $logType = $params['type'] ?? 'app';
        $lines = (int)($params['lines'] ?? 100);
        $date = $params['date'] ?? date('Y-m-d');
        
        // Validate log type
        $allowedTypes = ['app', 'access', 'error', 'security'];
        if (!in_array($logType, $allowedTypes)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Invalid log type. Allowed: ' . implode(', ', $allowedTypes)
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Validate lines
        if ($lines < 1 || $lines > 1000) {
            $lines = 100;
        }

        $logsDir = __DIR__ . '/../../logs';
        $logFile = "{$logsDir}/{$logType}-{$date}.log";

        // Check if log file exists
        if (!file_exists($logFile)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => "Log file not found for type '{$logType}' on date '{$date}'"
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        try {
            $logs = $this->readLogFile($logFile, $lines);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => [
                    'type' => $logType,
                    'date' => $date,
                    'lines_returned' => count($logs),
                    'logs' => $logs
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->logger->error('Failed to read log file', [
                'file' => $logFile,
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to read log file'
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Get available log files and their stats
     * GET /api/logs/files
     */
    public function getLogFiles(Request $request, Response $response): Response
    {
        $logsDir = __DIR__ . '/../../logs';
        
        if (!is_dir($logsDir)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Logs directory not found'
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $files = glob($logsDir . '/*.log');
        $logFiles = [];

        foreach ($files as $file) {
            $filename = basename($file);
            $fileInfo = [
                'name' => $filename,
                'size' => filesize($file),
                'size_formatted' => $this->formatBytes(filesize($file)),
                'modified' => date('Y-m-d H:i:s', filemtime($file)),
                'line_count' => $this->countLines($file)
            ];

            // Parse type and date from filename (e.g., app-2025-12-05.log)
            if (preg_match('/^(app|access|error|security)-(\d{4}-\d{2}-\d{2})\.log$/', $filename, $matches)) {
                $fileInfo['type'] = $matches[1];
                $fileInfo['date'] = $matches[2];
            }

            $logFiles[] = $fileInfo;
        }

        // Sort by modified date, newest first
        usort($logFiles, function($a, $b) {
            return strcmp($b['modified'], $a['modified']);
        });

        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => [
                'total_files' => count($logFiles),
                'files' => $logFiles
            ]
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Read last N lines from a log file
     */
    private function readLogFile(string $filePath, int $lines): array
    {
        $file = new \SplFileObject($filePath, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();
        
        $startLine = max(0, $lastLine - $lines);
        $logEntries = [];

        $file->seek($startLine);
        while (!$file->eof()) {
            $line = trim($file->fgets());
            if (!empty($line)) {
                // Try to parse as JSON
                $decoded = json_decode($line, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $logEntries[] = $decoded;
                } else {
                    // Plain text log
                    $logEntries[] = ['raw' => $line];
                }
            }
        }

        return array_reverse($logEntries);
    }

    /**
     * Count lines in a file efficiently
     */
    private function countLines(string $filePath): int
    {
        $file = new \SplFileObject($filePath, 'r');
        $file->seek(PHP_INT_MAX);
        return $file->key() + 1;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
