<?php

namespace App\Services;

use App\Models\User;
use App\Models\RefreshToken;
use App\Utils\JwtHelper;
use PDO;

class AuthService
{
    private User $userModel;
    private RefreshToken $refreshTokenModel;

    public function __construct(PDO $db)
    {
        $this->userModel = new User($db);
        $this->refreshTokenModel = new RefreshToken($db);
    }

    /**
     * Register a new user
     * 
     * @param string $email
     * @param string $password
     * @param string $role
     * @return array ['success' => bool, 'user' => array|null, 'error' => string|null]
     */
    public function register(string $email, string $password, string $role = 'user'): array
    {
        // Check if user already exists
        if ($this->userModel->findByEmail($email)) {
            return [
                'success' => false,
                'user' => null,
                'error' => 'User with this email already exists'
            ];
        }

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'user' => null,
                'error' => 'Invalid email format'
            ];
        }

        // Validate password length
        if (strlen($password) < 8) {
            return [
                'success' => false,
                'user' => null,
                'error' => 'Password must be at least 8 characters'
            ];
        }

        // Create user
        $userId = $this->userModel->create($email, $password, $role);
        
        if ($userId) {
            $user = $this->userModel->findById($userId);
            return [
                'success' => true,
                'user' => $this->userModel->getSafeUser($user),
                'error' => null
            ];
        }

        return [
            'success' => false,
            'user' => null,
            'error' => 'Failed to create user'
        ];
    }

    /**
     * Login user and generate tokens
     * 
     * @param string $email
     * @param string $password
     * @return array ['success' => bool, 'accessToken' => string|null, 'refreshToken' => string|null, 'user' => array|null, 'error' => string|null]
     */
    public function login(string $email, string $password): array
    {
        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            return [
                'success' => false,
                'accessToken' => null,
                'refreshToken' => null,
                'user' => null,
                'error' => 'Invalid email or password'
            ];
        }

        // Verify password
        if (!$this->userModel->verifyPassword($password, $user['password_hash'])) {
            return [
                'success' => false,
                'accessToken' => null,
                'refreshToken' => null,
                'user' => null,
                'error' => 'Invalid email or password'
            ];
        }

        // Generate tokens
        $accessToken = JwtHelper::generateAccessToken($user['id'], $user['email'], $user['role']);
        $refreshTokenString = JwtHelper::generateRefreshToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
        $this->refreshTokenModel->create($user['id'], $refreshTokenString, $expiresAt);

        return [
            'success' => true,
            'accessToken' => $accessToken,
            'refreshToken' => $refreshTokenString,
            'user' => $this->userModel->getSafeUser($user),
            'error' => null
        ];
    }

    /**
     * Refresh access token using refresh token
     * 
     * @param string $refreshToken
     * @return array ['success' => bool, 'accessToken' => string|null, 'error' => string|null]
     */
    public function refresh(string $refreshToken): array
    {
        $token = $this->refreshTokenModel->findByToken($refreshToken);

        if (!$token) {
            return [
                'success' => false,
                'accessToken' => null,
                'error' => 'Invalid refresh token'
            ];
        }

        // Check if token is expired
        if (strtotime($token['expires_at']) < time()) {
            return [
                'success' => false,
                'accessToken' => null,
                'error' => 'Refresh token expired'
            ];
        }

        // Get user
        $user = $this->userModel->findById($token['user_id']);
        
        if (!$user) {
            return [
                'success' => false,
                'accessToken' => null,
                'error' => 'User not found'
            ];
        }

        // Generate new access token
        $accessToken = JwtHelper::generateAccessToken($user['id'], $user['email'], $user['role']);

        return [
            'success' => true,
            'accessToken' => $accessToken,
            'error' => null
        ];
    }

    /**
     * Logout user by revoking refresh token
     * 
     * @param string $refreshToken
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function logout(string $refreshToken): array
    {
        $revoked = $this->refreshTokenModel->revokeToken($refreshToken);

        if ($revoked) {
            return [
                'success' => true,
                'error' => null
            ];
        }

        return [
            'success' => false,
            'error' => 'Invalid or already revoked token'
        ];
    }

    /**
     * Logout user from all devices by revoking all refresh tokens
     * 
     * @param int $userId
     * @return array ['success' => bool, 'revokedCount' => int, 'error' => string|null]
     */
    public function logoutAll(int $userId): array
    {
        $count = $this->refreshTokenModel->revokeByUserId($userId);

        return [
            'success' => true,
            'revokedCount' => $count,
            'error' => null
        ];
    }

    /**
     * Generate API key for user
     * 
     * @param int $userId
     * @return array ['success' => bool, 'apiKey' => string|null, 'error' => string|null]
     */
    public function generateApiKey(int $userId): array
    {
        $apiKey = $this->userModel->generateApiKey($userId);

        if ($apiKey) {
            return [
                'success' => true,
                'apiKey' => $apiKey,
                'error' => null
            ];
        }

        return [
            'success' => false,
            'apiKey' => null,
            'error' => 'Failed to generate API key'
        ];
    }

    /**
     * Validate API key and return user
     * 
     * @param string $apiKey
     * @return array ['success' => bool, 'user' => array|null, 'error' => string|null]
     */
    public function validateApiKey(string $apiKey): array
    {
        $user = $this->userModel->findByApiKey($apiKey);

        if ($user) {
            return [
                'success' => true,
                'user' => $this->userModel->getSafeUser($user),
                'error' => null
            ];
        }

        return [
            'success' => false,
            'user' => null,
            'error' => 'Invalid API key'
        ];
    }
}
