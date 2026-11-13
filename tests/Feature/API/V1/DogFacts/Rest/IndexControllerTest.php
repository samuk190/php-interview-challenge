<?php

declare(strict_types=1);

namespace Tests\Feature\API\V1\DogFacts\Rest;

use App\Models\DogFact;
use Tests\TestCase;

class IndexControllerTest extends TestCase
{
    public function test_success(): void
    {
        $response = $this->getJson('/api/v1/dog-facts');

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
        $response = $this->json('GET', '/api/v1/dog-facts', [
            'page' => 2,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'current_page' => 2,
        ]);
    }

    public function test_success_with_page_size(): void
    {
        $response = $this->json('GET', '/api/v1/dog-facts', [
            'pageSize' => 15,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'per_page' => 15,
        ]);
    }

    public function test_defaults_to25_per_page(): void
    {
        $response = $this->getJson('/api/v1/dog-facts');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'per_page' => 25,
        ]);
    }

    public function test_returns_requested_page_size_and_page(): void
    {
        $facts = [];

        for ($i = 1; $i <= 10; $i++) {
            $fact = new DogFact;
            $fact->fact = "Pagination test fact number $i";
            $fact->save();

            $facts[] = $fact;
        }

        $response = $this->getJson('/api/v1/dog-facts?page=2&pageSize=5');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'current_page' => 2,
            'per_page' => 5,
        ]);

        $this->assertCount(5, $response->json('data'));

        $this->cleanUpModels($facts);
    }

    public function test_rejects_invalid_page(): void
    {
        $response = $this->getJson('/api/v1/dog-facts?page=0');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['page']);
    }

    public function test_rejects_non_numeric_page_size(): void
    {
        $response = $this->getJson('/api/v1/dog-facts?pageSize=abc');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pageSize']);
    }

    public function test_rejects_negative_page_size(): void
    {
        $response = $this->getJson('/api/v1/dog-facts?pageSize=-1');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pageSize']);
    }

    public function test_rejects_page_size_above_max(): void
    {
        $response = $this->getJson('/api/v1/dog-facts?pageSize=101');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pageSize']);
    }

    public function test_returns_json_on_errors(): void
    {
        $response = $this->get('/api/v1/dog-facts?pageSize=abc');

        $response->assertStatus(422);
        $response->assertHeader('Content-Type', 'application/json');
    }
}
