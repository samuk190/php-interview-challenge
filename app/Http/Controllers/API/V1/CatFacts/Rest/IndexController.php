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

    /**
     * @OA\Get(
     *     path="/api/v1/cat-facts",
     *     operationId="getCatFactsList",
     *     tags={"Cat Facts"},
     *     summary="List cat facts (paginated)",
     *     description="Returns a paginated list of cat facts with configurable page size.",
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number to retrieve (integer >= 1)",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="Number of facts per page (integer >= 1 and <= 100, default 25)",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=25, minimum=1, maximum=100)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of cat facts",
     *
     *         @OA\JsonContent(ref="#/components/schemas/PaginatedCatFacts")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failure (e.g. invalid page or pageSize)",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function __invoke(IndexRequest $request): Response
    {
        $catFacts = $this->repository->getAllWithPagination($request->validated());

        return response()->json($catFacts);
    }
}
