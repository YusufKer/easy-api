<?php

namespace App\Models;

use PDO;
use PDOException;

class User
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new user
     * 
     * @param string $email User's email address
     * @param string $password Plain text password (will be hashed)
     * @param string $role User role (admin or user)
     * @return int User ID
     */
    public function create(string $email, string $password, string $role = 'user'): int
    {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $query = 'INSERT INTO user (email, password_hash, role) VALUES (?, ?, ?)';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$email, $passwordHash, $role]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Find user by email
     * 
     * @param string $email Email address
     * @return array|null User data or null if not found
     */
    public function findByEmail(string $email): ?array
    {
        $query = 'SELECT id, email, password_hash, role, api_key, is_active, created_at, updated_at 
                  FROM user 
                  WHERE email = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Find user by ID
     * 
     * @param int $id User ID
     * @return array|null User data or null if not found
     */
    public function findById(int $id): ?array
    {
        $query = 'SELECT id, email, password_hash, role, api_key, is_active, created_at, updated_at 
                  FROM user 
                  WHERE id = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Find user by API key
     * 
     * @param string $apiKey API key
     * @return array|null User data or null if not found
     */
    public function findByApiKey(string $apiKey): ?array
    {
        $query = 'SELECT id, email, password_hash, role, api_key, is_active, created_at, updated_at 
                  FROM user 
                  WHERE api_key = ? AND is_active = TRUE';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$apiKey]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Verify password against hash
     * 
     * @param string $password Plain text password
     * @param string $hash Password hash from database
     * @return bool True if password matches
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Generate and store API key for user
     * 
     * @param int $userId User ID
     * @return string Generated API key
     */
    public function generateApiKey(int $userId): string
    {
        $apiKey = bin2hex(random_bytes(32));
        $query = 'UPDATE user SET api_key = ? WHERE id = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$apiKey, $userId]);
        return $apiKey;
    }

    /**
     * Check if user exists by email
     * 
     * @param string $email Email address
     * @return bool True if user exists
     */
    public function existsByEmail(string $email): bool
    {
        $query = 'SELECT id FROM user WHERE email = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }

    /**
     * Update user's last login timestamp
     * 
     * @param int $userId User ID
     * @return void
     */
    public function updateLastLogin(int $userId): void
    {
        $query = 'UPDATE user SET updated_at = NOW() WHERE id = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
    }

    /**
     * Deactivate user account
     * 
     * @param int $userId User ID
     * @return bool True if successful
     */
    public function deactivate(int $userId): bool
    {
        $query = 'UPDATE user SET is_active = FALSE WHERE id = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Activate user account
     * 
     * @param int $userId User ID
     * @return bool True if successful
     */
    public function activate(int $userId): bool
    {
        $query = 'UPDATE user SET is_active = TRUE WHERE id = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get user data without sensitive information (password)
     * 
     * @param int|array $user User ID or user array
     * @return array|null User data without password_hash
     */
    public function getSafeUser($user): ?array
    {
        // If already an array with password field, strip it
        if (is_array($user)) {
            return [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'] ?? 'user',
                'is_active' => $user['is_active'] ?? true,
                'created_at' => $user['created_at'] ?? null,
                'updated_at' => $user['updated_at'] ?? null
            ];
        }
        
        // Otherwise treat as user ID and fetch from DB
        $query = 'SELECT id, email, role, is_active, created_at, updated_at 
                  FROM user 
                  WHERE id = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$user]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
