<?php

namespace App\Http\Controllers\API\V1\CatFacts\Rest;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\CatFacts\Rest\UpdateRequest;
use App\Repositories\CatFactRepository;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class UpdateController extends Controller
{
    protected CatFactRepository $repository;

    public function __construct(CatFactRepository $catFactRepository)
    {
        $this->repository = $catFactRepository;
    }

    public function __invoke(int $id, UpdateRequest $request): Response
    {
        $catFact = $this->repository->getById($id);

        if (!$catFact) {
            abort(404);
        }

        try {
            $updated = $this->repository->update($catFact, $request->validated());
        } catch (Exception $exception) {
            abort(500, $exception->getMessage());
        }

        if (!$updated) {
            abort(500, 'Cat fact could not be updated');
        }

        return response()->json($updated);
    }
}
