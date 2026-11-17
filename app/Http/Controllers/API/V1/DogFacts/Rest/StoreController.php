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

    /**
     * @OA\Post(
     *     path="/api/v1/dog-facts",
     *     operationId="storeDogFact",
     *     tags={"Dog Facts"},
     *     summary="Create a dog fact",
     *     description="Stores a new dog fact in the database.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Dog fact payload",
     *
     *         @OA\JsonContent(ref="#/components/schemas/FactInput")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successfully created dog fact",
     *
     *         @OA\JsonContent(ref="#/components/schemas/DogFact")
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
            $dogFact = $this->repository->store($request->validated());
        } catch (Exception $exception) {
            report($exception);
            abort(500, 'Dog fact could not be saved');
        }

        if (! $dogFact) {
            abort(500, 'Dog fact could not be saved');
        }

        return response()->json($dogFact);
    }
}
