<?php

declare(strict_types=1);

namespace Tests\Feature\API\V1\CatFacts\Rest;

use App\Http\Controllers\API\V1\CatFacts\Rest\StoreController;
use App\Models\CatFact;
use App\Repositories\CatFactRepository;
use Tests\TestCase;

class StoreControllerTest extends TestCase
{
    protected string $fact = 'This is a test fact from the StoreControllerTest';

    public function test_success(): void
    {
        $response = $this->postJson('/api/v1/cat-facts', [
            'fact' => $this->fact,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'fact' => $this->fact,
        ]);

        CatFact::destroy($response->json('id'));
    }

    public function test_validation_failure(): void
    {
        $response = $this->postJson('/api/v1/cat-facts', []);

        $response->assertStatus(422);

        $response2 = $this->postJson('/api/v1/cat-facts', [
            'fact' => null,
        ]);

        $response2->assertStatus(422);

        $response3 = $this->postJson('/api/v1/cat-facts', [
            'fact' => 123,
        ]);

        $response3->assertStatus(422);

        $response4 = $this->postJson('/api/v1/cat-facts', [
            'fact' => str_repeat('a', 256),
        ]);

        $response4->assertStatus(422);
    }

    public function test_handles_exception_during_save(): void
    {
        $this->app->bind(StoreController::class, function () {
            $mock = $this->getMockBuilder(CatFactRepository::class)
                ->onlyMethods(['store'])
                ->getMock();

            $mock->expects($this->once())
                ->method('store')
                ->willThrowException(new \Exception('Some DB error'));

            return new StoreController($mock);
        });

        $response = $this->postJson('/api/v1/cat-facts', [
            'fact' => $this->fact,
        ]);

        $response->assertStatus(500);
    }

    public function test_handles_failure_to_save(): void
    {
        $this->app->bind(StoreController::class, function () {
            $mock = $this->getMockBuilder(CatFactRepository::class)
                ->onlyMethods(['store'])
                ->getMock();

            $mock->expects($this->once())
                ->method('store')
                ->willReturn(null);

            return new StoreController($mock);
        });

        $response = $this->postJson('/api/v1/cat-facts', [
            'fact' => $this->fact,
        ]);

        $response->assertStatus(500);
    }
}
