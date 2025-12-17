<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    private AuthService $authService;
    private Logger $logger;

    public function __construct(AuthService $authService, Logger $logger)
    {
        $this->authService = $authService;
        $this->logger = $logger;
    }

    /**
     * Register a new user
     * POST /auth/register
     * Body: {"email": "user@example.com", "password": "password123", "role": "user"}
     */
    public function register(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'user';

        if (empty($email) || empty($password)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Validation Error',
                'message' => 'Email and password are required',
                'timestamp' => date('Y-m-d H:i:s')
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $result = $this->authService->register($email, $password, $role);

        if ($result['success']) {
            $this->logger->security('User registered successfully', [
                'email' => $email,
                'role' => $role,
                'user_id' => $result['user']['id'] ?? null
            ]);
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => $result['user']
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        }

        $this->logger->warning('User registration failed', [
            'email' => $email,
            'error' => $result['error']
        ]);

        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => 'Registration Failed',
            'message' => $result['error'],
            'timestamp' => date('Y-m-d H:i:s')
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    /**
     * Login user
     * POST /auth/login
     * Body: {"email": "user@example.com", "password": "password123"}
     */
    public function login(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Validation Error',
                'message' => 'Email and password are required',
                'timestamp' => date('Y-m-d H:i:s')
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $result = $this->authService->login($email, $password);

        if ($result['success']) {
            $this->logger->security('User login successful', [
                'email' => $email,
                'user_id' => $result['user']['id'] ?? null
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'accessToken' => $result['accessToken'],
                    'refreshToken' => $result['refreshToken'],
                    'user' => $result['user'],
                    'expiresIn' => 1800 // 30 minutes
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        }

        $this->logger->warning('Login failed', [
            'email' => $email,
            'error' => $result['error']
        ]);

        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => 'Authentication Failed',
            'message' => $result['error'],
            'timestamp' => date('Y-m-d H:i:s')
        ]));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    /**
     * Refresh access token
     * POST /auth/refresh
     * Body: {"refreshToken": "token"}
     */
    public function refresh(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $refreshToken = $data['refreshToken'] ?? '';

        if (empty($refreshToken)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Validation Error',
                'message' => 'Refresh token is required',
                'timestamp' => date('Y-m-d H:i:s')
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $result = $this->authService->refresh($refreshToken);

        if ($result['success']) {
            $this->logger->security('Token refreshed successfully', [
                'user_id' => $result['userId'] ?? null
            ]);
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'accessToken' => $result['accessToken'],
                    'expiresIn' => 1800
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        }

        $this->logger->warning('Token refresh failed', [
            'error' => $result['error']
        ]);

        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => 'Refresh Failed',
            'message' => $result['error'],
            'timestamp' => date('Y-m-d H:i:s')
        ]));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    /**
     * Logout user (revoke refresh token)
     * POST /auth/logout
     * Body: {"refreshToken": "token"}
     */
    public function logout(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $refreshToken = $data['refreshToken'] ?? '';

        if (empty($refreshToken)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Validation Error',
                'message' => 'Refresh token is required',
                'timestamp' => date('Y-m-d H:i:s')
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $result = $this->authService->logout($refreshToken);

        if ($result['success']) {
            $this->logger->security('User logged out successfully', [
                'user_id' => $result['userId'] ?? null
            ]);
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Logged out successfully',
                'timestamp' => date('Y-m-d H:i:s')
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        }

        $this->logger->warning('Logout failed', [
            'error' => $result['error']
        ]);

        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => 'Logout Failed',
            'message' => $result['error'],
            'timestamp' => date('Y-m-d H:i:s')
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    /**
     * Get current authenticated user info
     * GET /auth/me
     * Requires: Authorization header with JWT
     */
    public function me(Request $request, Response $response): Response
    {
        // User is attached by AuthMiddleware
        $user = $request->getAttribute('user');

        if (!$user) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'User not authenticated',
                'timestamp' => date('Y-m-d H:i:s')
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'User data retrieved successfully',
            'data' => [
                'user' => $user
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }

    /**
     * Generate API key for authenticated user
     * POST /auth/api-key
     * Requires: Authorization header with JWT
     */
    public function generateApiKey(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');

        if (!$user) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'User not authenticated',
                'timestamp' => date('Y-m-d H:i:s')
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $result = $this->authService->generateApiKey($user['id']);

        if ($result['success']) {
            $this->logger->security('API key generated', [
                'user_id' => $user['id'],
                'email' => $user['email'] ?? null
            ]);
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'API key generated successfully',
                'data' => [
                    'apiKey' => $result['apiKey']
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => 'Generation Failed',
            'message' => $result['error'],
            'timestamp' => date('Y-m-d H:i:s')
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
}
