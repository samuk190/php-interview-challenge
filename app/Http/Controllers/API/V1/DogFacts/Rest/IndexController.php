<?php

namespace App\Http\Controllers\API\V1\DogFacts\Rest;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\DogFacts\Rest\IndexRequest;
use App\Repositories\DogFactRepository;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends Controller
{
    protected DogFactRepository $repository;

    public function __construct(DogFactRepository $dogFactRepository)
    {
        $this->repository = $dogFactRepository;
    }

    public function __invoke(IndexRequest $request): Response
    {
        $dogFacts = $this->repository->getAllWithPagination($request->validated());

        return response()->json($dogFacts);
    }
}
