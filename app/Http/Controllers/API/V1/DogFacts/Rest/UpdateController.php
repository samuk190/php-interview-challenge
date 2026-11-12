<?php

namespace App\Http\Controllers\API\V1\DogFacts\Rest;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\DogFacts\Rest\UpdateRequest;
use App\Repositories\DogFactRepository;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class UpdateController extends Controller
{
    protected DogFactRepository $repository;

    public function __construct(DogFactRepository $dogFactRepository)
    {
        $this->repository = $dogFactRepository;
    }

    /**
     * @OA\Put(
     *     path="/api/v1/dog-facts/{id}",
     *     operationId="updateDogFact",
     *     tags={"Dog Facts"},
     *     summary="Update a dog fact",
     *     description="Updates an existing dog fact by its ID.",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Dog fact ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Updated dog fact payload",
     *
     *         @OA\JsonContent(ref="#/components/schemas/FactInput")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successfully updated dog fact",
     *
     *         @OA\JsonContent(ref="#/components/schemas/DogFact")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Dog fact not found",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
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
    public function __invoke(int $id, UpdateRequest $request): Response
    {
        $dogFact = $this->repository->getById($id);

        if (! $dogFact) {
            abort(404);
        }

        try {
            $updated = $this->repository->update($dogFact, $request->validated());
        } catch (Exception $exception) {
            report($exception);
            abort(500, 'Dog fact could not be updated');
        }

        if (! $updated) {
            abort(500, 'Dog fact could not be updated');
        }

        return response()->json($updated);
    }
}
