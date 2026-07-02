<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function test_user_exists(): void
    {
        $this->assertTrue(class_exists(User::class));
    }
}
