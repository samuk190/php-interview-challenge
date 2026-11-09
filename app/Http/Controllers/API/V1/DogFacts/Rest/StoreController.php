<?php

namespace App\Http\Controllers\API\V1\DogFacts\Rest;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\DogFacts\Rest\StoreRequest;
use App\Repositories\DogFactRepository;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class StoreController extends Controller
{
    protected DogFactRepository $repository;

    public function __construct(DogFactRepository $dogFactRepository)
    {
        $this->repository = $dogFactRepository;
    }

    public function __invoke(StoreRequest $request): Response
    {
        try {
            $dogFact = $this->repository->store($request->validated());
        } catch (Exception $exception) {
            abort(500, $exception->getMessage());
        }

        if (!$dogFact) {
            abort(500, 'Dog fact could not be saved');
        }

        return response()->json($dogFact);
    }
}
