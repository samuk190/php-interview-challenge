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

    /**
     * @OA\Post(
     *     path="/api/v1/cat-facts",
     *     operationId="storeCatFact",
     *     tags={"Cat Facts"},
     *     summary="Create a cat fact",
     *     description="Stores a new cat fact in the database.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Cat fact payload",
     *
     *         @OA\JsonContent(ref="#/components/schemas/FactInput")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successfully created cat fact",
     *
     *         @OA\JsonContent(ref="#/components/schemas/CatFact")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error (missing or invalid fact)",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function __invoke(StoreRequest $request): Response
    {
        try {
            $catFact = $this->repository->store($request->validated());
        } catch (Exception $exception) {
            report($exception);
            abort(500, 'Cat fact could not be saved');
        }

        if (! $catFact) {
            abort(500, 'Cat fact could not be saved');
        }

        return response()->json($catFact);
    }
}
