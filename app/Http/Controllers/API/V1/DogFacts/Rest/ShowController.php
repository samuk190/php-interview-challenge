<?php

namespace App\Http\Controllers\API\V1\DogFacts\Rest;

use App\Http\Controllers\Controller;
use App\Repositories\DogFactRepository;
use Symfony\Component\HttpFoundation\Response;

class ShowController extends Controller
{
    protected DogFactRepository $repository;

    public function __construct(DogFactRepository $dogFactRepository)
    {
        $this->repository = $dogFactRepository;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dog-facts/{id}",
     *     operationId="getDogFactById",
     *     tags={"Dog Facts"},
     *     summary="Get a single dog fact",
     *     description="Retrieves a dog fact by its integer ID.",
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
     *         response=200,
     *         description="Dog fact found",
     *
     *         @OA\JsonContent(ref="#/components/schemas/DogFact")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Dog fact not found",
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

        return response()->json($dogFact);
    }
}
