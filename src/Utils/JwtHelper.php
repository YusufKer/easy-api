<?php

namespace Utils;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JwtHelper
{
	private static function getSecret(): string
	{
		$secret = getenv('JWT_SECRET');
		if (!$secret) {
			throw new Exception('JWT_SECRET not set in environment');
		}
		return $secret;
	}

	private static function getExpiry(): int
	{
		$exp = getenv('JWT_EXPIRY');
		return $exp ? (int)$exp : 1800;
	}

	private static function getIssuer(): string
	{
		return $_SERVER['HTTP_HOST'] ?? 'easy-api';
	}

	/**
	 * Generate a short-lived JWT access token
	 */
	public static function generateAccessToken($userId, $email, $role): string
	{
		$issuedAt = time();
		$expire = $issuedAt + self::getExpiry();
		$payload = [
			'iss' => self::getIssuer(),
			'aud' => self::getIssuer(),
			'iat' => $issuedAt,
			'exp' => $expire,
			'sub' => $userId,
			'email' => $email,
			'role' => $role
		];
		return JWT::encode($payload, self::getSecret(), 'HS256');
	}

	/**
	 * Generate a secure random refresh token
	 */
	public static function generateRefreshToken(): string
	{
		return bin2hex(random_bytes(32));
	}

	/**
	 * Validate and decode a JWT
	 * @throws Exception if invalid
	 */
	public static function validateToken($token)
	{
		try {
			$decoded = JWT::decode($token, new Key(self::getSecret(), 'HS256'));
			return $decoded;
		} catch (\Firebase\JWT\ExpiredException $e) {
			throw new Exception('Token expired');
		} catch (Exception $e) {
			throw new Exception('Invalid token');
		}
	}

	/**
	 * Check if a JWT is expired
	 */
	public static function isTokenExpired($token): bool
	{
		try {
			$decoded = JWT::decode($token, new Key(self::getSecret(), 'HS256'));
			if (isset($decoded->exp) && $decoded->exp < time()) {
				return true;
			}
			return false;
		} catch (\Firebase\JWT\ExpiredException $e) {
			return true;
		} catch (Exception $e) {
			return true;
		}
	}
}
