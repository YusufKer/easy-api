<?php

namespace App\Services;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\IntrospectionProcessor;

class Logger
{
    private MonologLogger $accessLogger;
    private MonologLogger $errorLogger;
    private MonologLogger $securityLogger;
    private MonologLogger $generalLogger;

    public function __construct()
    {
        $this->initializeLoggers();
    }

    private function initializeLoggers(): void
    {
        $logsDir = __DIR__ . '/../../logs';
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }

        // General Application Logger
        $this->generalLogger = new MonologLogger('app');
        $this->setupLogger($this->generalLogger, $logsDir . '/app.log');

        // Access Logger (for API requests)
        $this->accessLogger = new MonologLogger('access');
        $this->setupLogger($this->accessLogger, $logsDir . '/access.log', MonologLogger::INFO);

        // Error Logger (for errors and exceptions)
        $this->errorLogger = new MonologLogger('error');
        $this->setupLogger($this->errorLogger, $logsDir . '/error.log', MonologLogger::ERROR);

        // Security Logger (for authentication, authorization events)
        $this->securityLogger = new MonologLogger('security');
        $this->setupLogger($this->securityLogger, $logsDir . '/security.log', MonologLogger::WARNING);
    }

    private function setupLogger(MonologLogger $logger, string $filePath, int $level = MonologLogger::DEBUG): void
    {
        // Rotating file handler (daily rotation, keep 14 days)
        $handler = new RotatingFileHandler($filePath, 14, $level);
        
        // JSON formatter for structured logging
        $formatter = new JsonFormatter();
        $handler->setFormatter($formatter);
        
        $logger->pushHandler($handler);
        
        // Add processors for additional context
        $logger->pushProcessor(new UidProcessor());           // Adds unique ID to each log entry
        $logger->pushProcessor(new WebProcessor());           // Adds web request info (IP, URL, method)
        $logger->pushProcessor(new IntrospectionProcessor()); // Adds file, line, class, function
    }

    /**
     * Log a debug message
     */
    public function debug(string $message, array $context = []): void
    {
        $this->generalLogger->debug($message, $context);
    }

    /**
     * Log an info message
     */
    public function info(string $message, array $context = []): void
    {
        $this->generalLogger->info($message, $context);
    }

    /**
     * Log a warning message
     */
    public function warning(string $message, array $context = []): void
    {
        $this->generalLogger->warning($message, $context);
    }

    /**
     * Log an error message
     */
    public function error(string $message, array $context = []): void
    {
        $this->errorLogger->error($message, $context);
        $this->generalLogger->error($message, $context);
    }

    /**
     * Log a critical message
     */
    public function critical(string $message, array $context = []): void
    {
        $this->errorLogger->critical($message, $context);
        $this->generalLogger->critical($message, $context);
    }

    /**
     * Log an access/request event
     */
    public function access(string $message, array $context = []): void
    {
        $this->accessLogger->info($message, $context);
    }

    /**
     * Log a security event (login, logout, failed auth, etc.)
     */
    public function security(string $message, array $context = []): void
    {
        $this->securityLogger->warning($message, $context);
    }

    /**
     * Log an exception
     */
    public function exception(\Throwable $exception, array $context = []): void
    {
        $context['exception'] = [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];

        $this->error('Exception occurred: ' . $exception->getMessage(), $context);
    }

    /**
     * Get the general logger instance for advanced usage
     */
    public function getGeneralLogger(): MonologLogger
    {
        return $this->generalLogger;
    }

    /**
     * Get the access logger instance for advanced usage
     */
    public function getAccessLogger(): MonologLogger
    {
        return $this->accessLogger;
    }

    /**
     * Get the error logger instance for advanced usage
     */
    public function getErrorLogger(): MonologLogger
    {
        return $this->errorLogger;
    }

    /**
     * Get the security logger instance for advanced usage
     */
    public function getSecurityLogger(): MonologLogger
    {
        return $this->securityLogger;
    }
}
