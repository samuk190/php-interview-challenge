<?php

declare(strict_types=1);

namespace Tests\Feature\API\V1\DogFacts\Rest;

use App\Http\Controllers\API\V1\DogFacts\Rest\UpdateController;
use App\Models\DogFact;
use App\Repositories\DogFactRepository;
use Tests\TestCase;

class UpdateControllerTest extends TestCase
{
    protected string $updatedFact = 'This is the updated dog fact';

    protected ?DogFact $dogFact;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dogFact = new DogFact;
        $this->dogFact->fact = 'This is the original dog fact';

        $this->dogFact->save();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->dogFact) {
            $this->cleanUpModels([$this->dogFact]);
        }
    }

    public function test_success(): void
    {
        $response = $this->putJson("/api/v1/dog-facts/{$this->dogFact->id}", [
            'fact' => $this->updatedFact,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $this->dogFact->id,
            'fact' => $this->updatedFact,
        ]);

        $this->assertDatabaseHas('dog_facts', [
            'id' => $this->dogFact->id,
            'fact' => $this->updatedFact,
        ]);
    }

    public function test_not_found()
    {
        $response = $this->putJson('/api/v1/dog-facts/6942069', [
            'fact' => $this->updatedFact,
        ]);

        $response->assertStatus(404);
    }

    public function test_validation_failure(): void
    {
        $response = $this->putJson("/api/v1/dog-facts/{$this->dogFact->id}", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['fact']);

        $response2 = $this->putJson("/api/v1/dog-facts/{$this->dogFact->id}", [
            'fact' => null,
        ]);

        $response2->assertStatus(422);

        $response3 = $this->putJson("/api/v1/dog-facts/{$this->dogFact->id}", [
            'fact' => 123,
        ]);

        $response3->assertStatus(422);
    }

    public function test_handles_exception_during_save(): void
    {
        $this->app->bind(UpdateController::class, function () {
            $mock = $this->getMockBuilder(DogFactRepository::class)
                ->onlyMethods(['update'])
                ->getMock();

            $mock->expects($this->once())
                ->method('update')
                ->willThrowException(new \Exception('Some DB error'));

            return new UpdateController($mock);
        });

        $response = $this->putJson("/api/v1/dog-facts/{$this->dogFact->id}", [
            'fact' => $this->updatedFact,
        ]);

        $response->assertStatus(500);
    }

    public function test_handles_failure_to_save(): void
    {
        $this->app->bind(UpdateController::class, function () {
            $mock = $this->getMockBuilder(DogFactRepository::class)
                ->onlyMethods(['update'])
                ->getMock();

            $mock->expects($this->once())
                ->method('update')
                ->willReturn(null);

            return new UpdateController($mock);
        });

        $response = $this->putJson("/api/v1/dog-facts/{$this->dogFact->id}", [
            'fact' => $this->updatedFact,
        ]);

        $response->assertStatus(500);
    }
}
