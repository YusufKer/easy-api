<?php

namespace App\Models;

use PDO;
use PDOException;

class RefreshToken
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new refresh token
     * 
     * @param int $userId User ID
     * @param string $token The refresh token string
     * @param string $expiresAt Expiration timestamp (YYYY-MM-DD HH:MM:SS)
     * @return int The ID of the created refresh token
     */
    public function create(int $userId, string $token, string $expiresAt): int
    {
        $query = 'INSERT INTO refresh_token (user_id, token, expires_at) VALUES (?, ?, ?)';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId, $token, $expiresAt]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Find a refresh token by token string
     * Returns null if not found, expired, or revoked
     * 
     * @param string $token The refresh token to find
     * @return array|null Token data with user_id, or null if invalid
     */
    public function findByToken(string $token): ?array
    {
        $query = 'SELECT id, user_id, token, expires_at, is_revoked, created_at 
                  FROM refresh_token 
                  WHERE token = ? 
                  AND is_revoked = FALSE 
                  AND expires_at > NOW()';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$token]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Revoke a specific refresh token
     * 
     * @param string $token The token to revoke
     * @return bool True if token was revoked, false if not found
     */
    public function revokeToken(string $token): bool
    {
        $query = 'UPDATE refresh_token SET is_revoked = TRUE WHERE token = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$token]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Revoke all refresh tokens for a user (logout from all devices)
     * 
     * @param int $userId The user ID
     * @return int Number of tokens revoked
     */
    public function revokeByUserId(int $userId): int
    {
        $query = 'UPDATE refresh_token SET is_revoked = TRUE WHERE user_id = ? AND is_revoked = FALSE';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->rowCount();
    }

    /**
     * Cleanup expired tokens (maintenance task)
     * Deletes tokens that have expired
     * 
     * @return int Number of tokens deleted
     */
    public function cleanupExpired(): int
    {
        $query = 'DELETE FROM refresh_token WHERE expires_at < NOW()';
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Get all active refresh tokens for a user
     * 
     * @param int $userId The user ID
     * @return array Array of active tokens
     */
    public function getActiveTokensByUserId(int $userId): array
    {
        $query = 'SELECT id, token, expires_at, created_at 
                  FROM refresh_token 
                  WHERE user_id = ? 
                  AND is_revoked = FALSE 
                  AND expires_at > NOW()
                  ORDER BY created_at DESC';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Count active tokens for a user
     * Useful for limiting the number of concurrent sessions
     * 
     * @param int $userId The user ID
     * @return int Number of active tokens
     */
    public function countActiveTokensByUserId(int $userId): int
    {
        $query = 'SELECT COUNT(*) as count 
                  FROM refresh_token 
                  WHERE user_id = ? 
                  AND is_revoked = FALSE 
                  AND expires_at > NOW()';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return (int) $result['count'];
    }
}
