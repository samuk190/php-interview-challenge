<?php

namespace App\Http\Controllers\API\V1\CatFacts\Rest;

use App\Http\Controllers\Controller;
use App\Repositories\CatFactRepository;
use Exception;
use Symfony\Component\HttpFoundation\Response;

class DestroyController extends Controller
{
    protected CatFactRepository $repository;

    public function __construct(CatFactRepository $catFactRepository)
    {
        $this->repository = $catFactRepository;
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/cat-facts/{id}",
     *     operationId="deleteCatFact",
     *     tags={"Cat Facts"},
     *     summary="Delete a cat fact",
     *     description="Deletes a cat fact by its ID.",
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
     *         response=204,
     *         description="Cat fact successfully deleted"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cat fact not found",
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
        $catFact = $this->repository->getById($id);

        if (! $catFact) {
            abort(404);
        }

        try {
            $deleted = $this->repository->destroy($catFact);
        } catch (Exception $exception) {
            report($exception);
            abort(500, 'Cat fact could not be deleted');
        }

        if (! $deleted) {
            abort(500, 'Cat fact could not be deleted');
        }

        return response()->json(null, 204);
    }
}
