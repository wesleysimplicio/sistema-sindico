<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\StoragePath;
use PHPUnit\Framework\TestCase;

final class StoragePathTest extends TestCase
{
    public function test_is_safe_relative_rejects_traversal(): void
    {
        self::assertFalse(StoragePath::isSafeRelative('../.env'));
        self::assertFalse(StoragePath::isSafeRelative('/etc/passwd'));
        self::assertFalse(StoragePath::isSafeRelative('http://evil.test/a'));
    }

    public function test_resolve_accepts_existing_file_under_upload_root(): void
    {
        $relative = 'phpunit/safe.txt';
        $absolute = StoragePath::uploadRoot() . '/' . $relative;
        $dir = dirname($absolute);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($absolute, 'ok');

        try {
            self::assertSame(realpath($absolute), StoragePath::resolve($relative));
        } finally {
            @unlink($absolute);
            @rmdir($dir);
        }
    }
}
