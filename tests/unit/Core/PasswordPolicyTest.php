<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\PasswordPolicy;
use PHPUnit\Framework\TestCase;

final class PasswordPolicyTest extends TestCase
{
    public function test_valid_password_passes(): void
    {
        self::assertTrue(PasswordPolicy::isValid('SenhaForte123'));
    }

    public function test_violations_report_missing_rules(): void
    {
        $violations = PasswordPolicy::violations('fraca');

        self::assertContains('min_length', $violations);
        self::assertContains('uppercase', $violations);
        self::assertContains('digit', $violations);
    }
}
