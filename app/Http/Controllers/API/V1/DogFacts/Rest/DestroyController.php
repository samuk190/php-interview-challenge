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

    /**
     * @OA\Delete(
     *     path="/api/v1/dog-facts/{id}",
     *     operationId="deleteDogFact",
     *     tags={"Dog Facts"},
     *     summary="Delete a dog fact",
     *     description="Deletes a dog fact by its ID.",
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
     *     @OA\Response(
     *         response=204,
     *         description="Dog fact successfully deleted"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Dog fact not found",
     *
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
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
    public function __invoke(int $id): Response
    {
        $dogFact = $this->repository->getById($id);

        if (! $dogFact) {
            abort(404);
        }

        try {
            $deleted = $this->repository->destroy($dogFact);
        } catch (Exception $exception) {
            report($exception);
            abort(500, 'Dog fact could not be deleted');
        }

        if (! $deleted) {
            abort(500, 'Dog fact could not be deleted');
        }

        return response()->json(null, 204);
    }
}
