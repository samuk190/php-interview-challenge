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

    /**
     * @OA\Put(
     *     path="/api/v1/cat-facts/{id}",
     *     operationId="updateCatFact",
     *     tags={"Cat Facts"},
     *     summary="Update a cat fact",
     *     description="Updates an existing cat fact by its ID.",
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
     *     @OA\RequestBody(
     *         required=true,
     *         description="Updated cat fact payload",
     *
     *         @OA\JsonContent(ref="#/components/schemas/FactInput")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successfully updated cat fact",
     *
     *         @OA\JsonContent(ref="#/components/schemas/CatFact")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Cat fact not found",
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
        $catFact = $this->repository->getById($id);

        if (! $catFact) {
            abort(404);
        }

        try {
            $updated = $this->repository->update($catFact, $request->validated());
        } catch (Exception $exception) {
            report($exception);
            abort(500, 'Cat fact could not be updated');
        }

        if (! $updated) {
            abort(500, 'Cat fact could not be updated');
        }

        return response()->json($updated);
    }
}
