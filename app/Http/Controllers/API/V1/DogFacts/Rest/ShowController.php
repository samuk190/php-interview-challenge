<?php

namespace App\Http\Controllers\API\V1\DogFacts\Rest;

use App\Http\Controllers\Controller;
use App\Repositories\DogFactRepository;
use Symfony\Component\HttpFoundation\Response;

class ShowController extends Controller
{
    protected DogFactRepository $repository;

    public function __construct(DogFactRepository $dogFactRepository)
    {
        $this->repository = $dogFactRepository;
    }

    public function __invoke(int $id): Response
    {
        $dogFact = $this->repository->getById($id);

        if (!$dogFact) {
            abort(404);
        }

        return response()->json($dogFact);
    }
}
