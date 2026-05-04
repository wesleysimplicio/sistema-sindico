<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Storage path lockdown.
 *
 * All user-provided file paths (Document/Notice/Maintenance attachments) must be
 * relative paths under `storage/uploads/`. This class is the single point of
 * validation + resolution so attackers cannot traverse to /etc/passwd, env
 * files, or arbitrary project sources via crafted file_path inputs.
 */
final class StoragePath
{
    public const ROOT_REL = 'storage/uploads';

    /**
     * Project-root absolute path to the upload root, with realpath when available.
     */
    public static function uploadRoot(): string
    {
        $root = dirname(__DIR__, 2) . '/' . self::ROOT_REL;
        $real = realpath($root);
        return $real !== false ? $real : $root;
    }

    /**
     * Validate a user-provided relative path. Returns true only if the path is
     * safely confined to the upload root (no traversal, no absolute paths,
     * no schemes, no NUL byte, no Windows drive letter, no symlink escape).
     */
    public static function isSafeRelative(string $relative): bool
    {
        $rel = trim($relative);
        if ($rel === '' || strlen($rel) > 1024) {
            return false;
        }
        if (strpos($rel, "\0") !== false) {
            return false;
        }
        // Reject absolute, scheme, drive letter, traversal segments, backslashes.
        if (preg_match('#^([a-zA-Z]:|/|\\\\)#', $rel)) {
            return false;
        }
        if (strpos($rel, '://') !== false) {
            return false;
        }
        if (strpos($rel, '\\') !== false) {
            return false;
        }
        $segments = explode('/', $rel);
        foreach ($segments as $seg) {
            if ($seg === '' || $seg === '.' || $seg === '..') {
                return false;
            }
            if (!preg_match('/^[A-Za-z0-9._\- ]+$/', $seg)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Resolve a relative path to its absolute path under uploadRoot, or null if
     * unsafe / nonexistent / escapes the upload root via symlink.
     */
    public static function resolve(string $relative): ?string
    {
        if (!self::isSafeRelative($relative)) {
            return null;
        }
        $root = self::uploadRoot();
        $candidate = $root . '/' . ltrim($relative, '/');
        $real = realpath($candidate);
        if ($real === false) {
            return null;
        }
        if (strncmp($real, $root, strlen($root)) !== 0) {
            return null;
        }
        return $real;
    }
}
