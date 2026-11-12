<?php

declare(strict_types=1);

namespace Tests\Feature\API\V1\CatFacts\Rest;

use Tests\TestCase;

class IndexControllerTest extends TestCase
{
    public function test_success(): void
    {
        $response = $this->getJson('/api/v1/cat-facts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'current_page',
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);
    }

    public function test_success_with_page(): void
    {
        $response = $this->json('GET', '/api/v1/cat-facts', [
            'page' => 2,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'current_page' => 2,
        ]);
    }

    public function test_success_with_page_size(): void
    {
        $response = $this->json('GET', '/api/v1/cat-facts', [
            'pageSize' => 15,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'per_page' => 15,
        ]);
    }

    public function test_rejects_page_size_above_max(): void
    {
        $response = $this->getJson('/api/v1/cat-facts?pageSize=101');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pageSize']);
    }
}
