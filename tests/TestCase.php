<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set JWT_SECRET for testing if not already set
        if (!env('JWT_SECRET')) {
            putenv('JWT_SECRET=test-secret-key-for-testing-purposes-only-min-32-chars');
            config(['jwt.secret' => 'test-secret-key-for-testing-purposes-only-min-32-chars']);
        }
    }
}
