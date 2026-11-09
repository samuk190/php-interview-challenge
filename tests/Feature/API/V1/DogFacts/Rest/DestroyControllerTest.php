<?php
declare(strict_types=1);

namespace Tests\Feature\API\V1\DogFacts\Rest;

use App\Http\Controllers\API\V1\DogFacts\Rest\DestroyController;
use App\Models\DogFact;
use App\Repositories\DogFactRepository;
use Tests\TestCase;

class DestroyControllerTest extends TestCase
{
    protected ?DogFact $dogFact;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dogFact = new DogFact();
        $this->dogFact->fact = 'This is a dog fact that will be deleted';

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
        $response = $this->deleteJson('/api/v1/dog-facts/' . $this->dogFact->id);

        $response->assertStatus(204);

        $dogFact = DogFact::find($this->dogFact->id);
        $this->assertNull($dogFact);
    }

    public function testNotFound(): void
    {
        $response = $this->deleteJson('/api/v1/dog-facts/6942069');

        $response->assertStatus(404);
    }

    public function testHandlesExceptionDuringDelete(): void
    {
        $this->app->bind(DestroyController::class, function () {
            $mock = $this->getMockBuilder(DogFactRepository::class)
                ->onlyMethods(['destroy'])
                ->getMock();

            $mock->expects($this->once())
                ->method('destroy')
                ->willThrowException(new \Exception('Some DB error'));

            return new DestroyController($mock);
        });

        $response = $this->deleteJson('/api/v1/dog-facts/' . $this->dogFact->id);

        $response->assertStatus(500);
    }

    public function testHandlesFailureToDelete(): void
    {
        $this->app->bind(DestroyController::class, function ($app) {
            $mock = $this->getMockBuilder(DogFactRepository::class)
                ->onlyMethods(['destroy'])
                ->getMock();

            $mock->expects($this->once())
                ->method('destroy')
                ->willReturn(false);

            return new DestroyController($mock);
        });

        $response = $this->deleteJson('/api/v1/dog-facts/' . $this->dogFact->id);

        $response->assertStatus(500);
    }
}
