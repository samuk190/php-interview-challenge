<?php
declare(strict_types=1);

namespace Tests\Feature\API\V1\DogFacts\Rest;

use App\Models\DogFact;
use Tests\TestCase;

class ShowControllerTest extends TestCase
{
    protected string $fact = 'This is a dog fact';

    protected ?DogFact $dogFact;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dogFact = new DogFact();
        $this->dogFact->fact = $this->fact;

        $this->dogFact->save();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->dogFact) {
            $this->cleanUpModels([$this->dogFact]);
        }
    }

    public function testSuccess(): void
    {
        $response = $this->getJson("/api/v1/dog-facts/{$this->dogFact->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $this->dogFact->id,
            'fact' => $this->fact,
        ]);
    }

    public function testNotFound(): void
    {
        $response = $this->getJson('/api/v1/dog-facts/6942069');

        $response->assertStatus(404);
    }

    public function testNotFoundReturnsJson(): void
    {
        $response = $this->get('/api/v1/dog-facts/6942069');

        $response->assertStatus(404);
        $response->assertHeader('Content-Type', 'application/json');
    }
}
