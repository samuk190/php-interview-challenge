<?php

namespace App\Http\Controllers\API\V1\DogFacts\Rest;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\DogFacts\Rest\UpdateRequest;
use App\Repositories\DogFactRepository;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class UpdateController extends Controller
{
    protected DogFactRepository $repository;

    public function __construct(DogFactRepository $dogFactRepository)
    {
        $this->repository = $dogFactRepository;
    }

    public function __invoke(int $id, UpdateRequest $request): Response
    {
        $dogFact = $this->repository->getById($id);

        if (!$dogFact) {
            abort(404);
        }

        try {
            $updated = $this->repository->update($dogFact, $request->validated());
        } catch (Exception $exception) {
            abort(500, $exception->getMessage());
        }

        if (!$updated) {
            abort(500, 'Dog fact could not be updated');
        }

        return response()->json($updated);
    }
}
