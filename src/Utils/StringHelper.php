<?php

namespace App\Utils;

class StringHelper {
    /**
     * Sanitize a protein name by trimming whitespace and converting to title case
     */
    public static function sanitizeProteinName(string $name): string {
        $trimmed = trim($name);
        return ucwords(strtolower($trimmed));
    }

    /**
     * Check if a string is a valid protein name (alphanumeric, spaces, hyphens only)
     */
    public static function isValidProteinName(string $name): bool {
        if (empty(trim($name))) {
            return false;
        }
        
        // Only allow letters, spaces, and hyphens
        return preg_match('/^[a-zA-Z\s\-]+$/', $name) === 1;
    }
}
