<?php

namespace App\Http\Controllers\API\V1\CatFacts\Rest;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\CatFacts\Rest\IndexRequest;
use App\Repositories\CatFactRepository;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends Controller
{
    protected CatFactRepository $repository;

    public function __construct(CatFactRepository $catFactRepository)
    {
        $this->repository = $catFactRepository;
    }

    public function __invoke(IndexRequest $request): Response
    {
        $catFacts = $this->repository->getAllWithPagination($request->validated());

        return response()->json($catFacts);
    }
}
