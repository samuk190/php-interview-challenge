<?php

namespace App\Http\Controllers\API\V1\CatFacts\Rest;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\CatFacts\Rest\StoreRequest;
use App\Repositories\CatFactRepository;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class StoreController extends Controller
{
    protected CatFactRepository $repository;

    public function __construct(CatFactRepository $catFactRepository)
    {
        $this->repository = $catFactRepository;
    }

    public function __invoke(StoreRequest $request): Response
    {
        try {
            $catFact = $this->repository->store($request->validated());
        } catch (Exception $exception) {
            abort(500, $exception->getMessage());
        }

        if (!$catFact) {
            abort(500, 'Cat fact could not be saved');
        }

        return response()->json($catFact);
    }
}
