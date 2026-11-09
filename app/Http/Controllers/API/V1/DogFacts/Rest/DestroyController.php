<?php

namespace App\Http\Controllers\API\V1\DogFacts\Rest;

use App\Http\Controllers\Controller;
use App\Repositories\DogFactRepository;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class DestroyController extends Controller
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

        try {
            $deleted = $this->repository->destroy($dogFact);
        } catch (Exception $exception) {
            abort(500, $exception->getMessage());
        }

        if (!$deleted) {
            abort(500, 'Dog fact could not be deleted');
        }

        return response()->json(null, 204);
    }
}
