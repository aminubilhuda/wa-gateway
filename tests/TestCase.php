<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(User::class)) {
            try {
                $user = User::factory()->create();
                $this->actingAs($user);
            } catch (\Exception $e) {
                // Silently catch if DB is not ready or migrated
            }
        }
    }
}
