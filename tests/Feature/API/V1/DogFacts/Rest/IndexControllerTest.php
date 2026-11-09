<?php
declare(strict_types=1);

namespace Tests\Feature\API\V1\DogFacts\Rest;

use App\Models\DogFact;
use Tests\TestCase;

class IndexControllerTest extends TestCase
{
    public function testSuccess(): void
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
            'total'
        ]);
    }

    public function testSuccessWithPage(): void
    {
        $response = $this->json('GET', '/api/v1/dog-facts', [
            'page' => 2,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'current_page' => 2,
        ]);
    }

    public function testSuccessWithPageSize(): void
    {
        $response = $this->json('GET', '/api/v1/dog-facts', [
            'pageSize' => 15,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'per_page' => 15,
        ]);
    }

    public function testDefaultsTo25PerPage(): void
    {
        $response = $this->getJson('/api/v1/dog-facts');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'per_page' => 25,
        ]);
    }

    public function testReturnsRequestedPageSizeAndPage(): void
    {
        $facts = [];

        for ($i = 1; $i <= 10; $i++) {
            $fact = new DogFact();
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

    public function testRejectsInvalidPage(): void
    {
        $response = $this->getJson('/api/v1/dog-facts?page=0');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['page']);
    }

    public function testRejectsNonNumericPageSize(): void
    {
        $response = $this->getJson('/api/v1/dog-facts?pageSize=abc');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pageSize']);
    }

    public function testRejectsNegativePageSize(): void
    {
        $response = $this->getJson('/api/v1/dog-facts?pageSize=-1');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pageSize']);
    }

    public function testReturnsJsonOnErrors(): void
    {
        $response = $this->get('/api/v1/dog-facts?pageSize=abc');

        $response->assertStatus(422);
        $response->assertHeader('Content-Type', 'application/json');
    }
}
