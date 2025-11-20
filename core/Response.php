<?php

class Response {
    
    public static function success($message, $data = null, $statusCode = 200) {
        http_response_code($statusCode);
        
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        exit;
    }
    
    public static function created($message, $data = null) {
        self::success($message, $data, 201);
    }
    
    public static function error($message, $details = null, $statusCode = 500) {
        http_response_code($statusCode);
        
        $response = [
            'success' => false,
            'error' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Only include details in development
        if ($details !== null && self::isDevelopment()) {
            $response['details'] = $details;
        }
        
        echo json_encode($response);
        exit;
    }
    
    public static function notFound($message = 'Resource not found', $details = null) {
        self::error($message, $details, 404);
    }
    
    public static function badRequest($message = 'Bad request', $details = null) {
        self::error($message, $details, 400);
    }
    
    public static function conflict($message = 'Conflict', $details = null) {
        self::error($message, $details, 409);
    }
    
    public static function serverError($message = 'Internal server error', $details = null) {
        self::error($message, $details, 500);
    }
    
    private static function isDevelopment() {
        return !isset($_ENV['APP_ENV']) || $_ENV['APP_ENV'] === 'development';
    }
}
