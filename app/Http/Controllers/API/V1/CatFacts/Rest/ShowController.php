<?php

namespace App\Http\Controllers\API\V1\CatFacts\Rest;

use App\Http\Controllers\Controller;
use App\Repositories\CatFactRepository;
use Symfony\Component\HttpFoundation\Response;

class ShowController extends Controller
{
    protected CatFactRepository $repository;

    public function __construct(CatFactRepository $catFactRepository)
    {
        $this->repository = $catFactRepository;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/cat-facts/{id}",
     *     operationId="getCatFactById",
     *     tags={"Cat Facts"},
     *     summary="Get a single cat fact",
     *     description="Retrieves a cat fact by its integer ID.",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Cat fact ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Cat fact found",
     *
     *         @OA\JsonContent(ref="#/components/schemas/CatFact")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Cat fact not found",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function __invoke(int $id): Response
    {
        $catFact = $this->repository->getById($id);

        if (! $catFact) {
            abort(404);
        }

        return response()->json($catFact);
    }
}
