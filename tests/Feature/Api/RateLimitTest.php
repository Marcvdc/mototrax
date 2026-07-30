<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_responses_expose_rate_limit_headers(): void
    {
        $this->getJson('/api/v1/routes')
            ->assertOk()
            ->assertHeader('X-RateLimit-Limit', 60);
    }

    public function test_exceeding_the_read_limit_returns_429(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/routes')->assertOk();
        }

        $this->getJson('/api/v1/routes')->assertStatus(429);
    }
}
