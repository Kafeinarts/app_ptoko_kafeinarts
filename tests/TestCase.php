<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use LazilyRefreshDatabase;

    protected function seededUser(string $email = 'admin@kafeinarts.test'): User
    {
        $this->seed();

        return User::query()->where('email', $email)->firstOrFail();
    }

    protected function actingAsSeeded(string $email = 'admin@kafeinarts.test'): User
    {
        $user = $this->seededUser($email);
        $this->actingAs($user);

        return $user;
    }
}
