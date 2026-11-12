<?php

declare(strict_types=1);

namespace Tests\Feature\API\V1\DogFacts\Rest;

use App\Http\Controllers\API\V1\DogFacts\Rest\StoreController;
use App\Models\DogFact;
use App\Repositories\DogFactRepository;
use Tests\TestCase;

class StoreControllerTest extends TestCase
{
    protected string $fact = 'This is a test fact from the StoreControllerTest';

    public function test_success(): void
    {
        $response = $this->postJson('/api/v1/dog-facts', [
            'fact' => $this->fact,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'fact' => $this->fact,
        ]);

        $this->assertDatabaseHas('dog_facts', [
            'id' => $response->json('id'),
            'fact' => $this->fact,
        ]);

        DogFact::destroy($response->json('id'));
    }

    public function test_validation_failure(): void
    {
        $response = $this->postJson('/api/v1/dog-facts', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['fact']);

        $response2 = $this->postJson('/api/v1/dog-facts', [
            'fact' => null,
        ]);

        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors(['fact']);

        $response3 = $this->postJson('/api/v1/dog-facts', [
            'fact' => 123,
        ]);

        $response3->assertStatus(422);
        $response3->assertJsonValidationErrors(['fact']);

        $response4 = $this->postJson('/api/v1/dog-facts', [
            'fact' => str_repeat('a', 256),
        ]);

        $response4->assertStatus(422);
        $response4->assertJsonValidationErrors(['fact']);
    }

    public function test_accepts_only_json_requests(): void
    {
        $payload = json_encode(['fact' => $this->fact]);

        $response = $this->call(
            'POST',
            '/api/v1/dog-facts',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            $payload
        );

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'fact' => $this->fact,
        ]);

        DogFact::destroy($response->json('id'));
    }

    public function test_handles_exception_during_save(): void
    {
        $this->app->bind(StoreController::class, function () {
            $mock = $this->getMockBuilder(DogFactRepository::class)
                ->onlyMethods(['store'])
                ->getMock();

            $mock->expects($this->once())
                ->method('store')
                ->willThrowException(new \Exception('Some DB error'));

            return new StoreController($mock);
        });

        $response = $this->postJson('/api/v1/dog-facts', [
            'fact' => $this->fact,
        ]);

        $response->assertStatus(500);
    }

    public function test_handles_failure_to_save(): void
    {
        $this->app->bind(StoreController::class, function () {
            $mock = $this->getMockBuilder(DogFactRepository::class)
                ->onlyMethods(['store'])
                ->getMock();

            $mock->expects($this->once())
                ->method('store')
                ->willReturn(null);

            return new StoreController($mock);
        });

        $response = $this->postJson('/api/v1/dog-facts', [
            'fact' => $this->fact,
        ]);

        $response->assertStatus(500);
    }
}
