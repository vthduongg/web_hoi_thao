<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\ArraySessionStore;
use App\Core\Csrf;
use App\Core\Session;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    public function test_token_is_stable_within_a_session_and_validates(): void
    {
        $csrf = new Csrf(new Session(new ArraySessionStore()));

        $token = $csrf->token();

        self::assertSame(64, strlen($token));
        self::assertSame($token, $csrf->token());
        self::assertTrue($csrf->isValid($token));
    }

    public function test_rejects_missing_or_wrong_token(): void
    {
        $csrf = new Csrf(new Session(new ArraySessionStore()));
        $csrf->token();

        self::assertFalse($csrf->isValid(null));
        self::assertFalse($csrf->isValid(''));
        self::assertFalse($csrf->isValid(str_repeat('a', 64)));
    }

    public function test_token_from_another_session_is_rejected(): void
    {
        $tokenA = (new Csrf(new Session(new ArraySessionStore())))->token();
        $csrfB = new Csrf(new Session(new ArraySessionStore()));
        $csrfB->token();

        self::assertFalse($csrfB->isValid($tokenA));
    }
}
