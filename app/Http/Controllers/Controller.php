<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Animal Facts API",
 *     description="REST API built with Laravel 9 / PHP 8.1 that serves curated facts about animals (Cat Facts & Dog Facts).",
 *
 *     @OA\Contact(
 *         name="API Support",
 *         email="support@example.com"
 *     ),
 *
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Animal Facts API Server"
 * )
 *
 * @OA\Tag(
 *     name="Discovery",
 *     description="API root discovery and status"
 * )
 * @OA\Tag(
 *     name="Cat Facts",
 *     description="Operations on Cat Facts"
 * )
 * @OA\Tag(
 *     name="Dog Facts",
 *     description="Operations on Dog Facts"
 * )
 *
 * @OA\Schema(
 *     schema="FactInput",
 *     type="object",
 *     required={"fact"},
 *
 *     @OA\Property(
 *         property="fact",
 *         type="string",
 *         maxLength=255,
 *         description="The animal fact text",
 *         example="A group of cats is called a clowder."
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="CatFact",
 *     type="object",
 *     required={"id", "fact", "created_at", "updated_at"},
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="fact", type="string", example="A group of cats is called a clowder."),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-11-10T05:18:42.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-11-10T05:18:42.000000Z")
 * )
 *
 * @OA\Schema(
 *     schema="DogFact",
 *     type="object",
 *     required={"id", "fact", "created_at", "updated_at"},
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="fact", type="string", example="Dogs have a sense of time and predict future events."),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-11-10T05:18:42.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-11-10T05:18:42.000000Z")
 * )
 *
 * @OA\Schema(
 *     schema="PaginationLink",
 *     type="object",
 *
 *     @OA\Property(property="url", type="string", nullable=true, example="http://localhost:8042/api/v1/cat-facts?page=1"),
 *     @OA\Property(property="label", type="string", example="1"),
 *     @OA\Property(property="active", type="boolean", example=true)
 * )
 *
 * @OA\Schema(
 *     schema="PaginatedCatFacts",
 *     type="object",
 *
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CatFact")),
 *     @OA\Property(property="first_page_url", type="string", example="http://localhost:8042/api/v1/cat-facts?page=1"),
 *     @OA\Property(property="from", type="integer", nullable=true, example=1),
 *     @OA\Property(property="last_page", type="integer", example=5),
 *     @OA\Property(property="last_page_url", type="string", example="http://localhost:8042/api/v1/cat-facts?page=5"),
 *     @OA\Property(property="links", type="array", @OA\Items(ref="#/components/schemas/PaginationLink")),
 *     @OA\Property(property="next_page_url", type="string", nullable=true, example="http://localhost:8042/api/v1/cat-facts?page=2"),
 *     @OA\Property(property="path", type="string", example="http://localhost:8042/api/v1/cat-facts"),
 *     @OA\Property(property="per_page", type="integer", example=25),
 *     @OA\Property(property="prev_page_url", type="string", nullable=true, example=null),
 *     @OA\Property(property="to", type="integer", nullable=true, example=25),
 *     @OA\Property(property="total", type="integer", example=120)
 * )
 *
 * @OA\Schema(
 *     schema="PaginatedDogFacts",
 *     type="object",
 *
 *     @OA\Property(property="current_page", type="integer", example=1),
 *     @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/DogFact")),
 *     @OA\Property(property="first_page_url", type="string", example="http://localhost:8042/api/v1/dog-facts?page=1"),
 *     @OA\Property(property="from", type="integer", nullable=true, example=1),
 *     @OA\Property(property="last_page", type="integer", example=5),
 *     @OA\Property(property="last_page_url", type="string", example="http://localhost:8042/api/v1/dog-facts?page=5"),
 *     @OA\Property(property="links", type="array", @OA\Items(ref="#/components/schemas/PaginationLink")),
 *     @OA\Property(property="next_page_url", type="string", nullable=true, example="http://localhost:8042/api/v1/dog-facts?page=2"),
 *     @OA\Property(property="path", type="string", example="http://localhost:8042/api/v1/dog-facts"),
 *     @OA\Property(property="per_page", type="integer", example=25),
 *     @OA\Property(property="prev_page_url", type="string", nullable=true, example=null),
 *     @OA\Property(property="to", type="integer", nullable=true, example=25),
 *     @OA\Property(property="total", type="integer", example=120)
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *
 *     @OA\Property(property="message", type="string", example="Resource not found")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     type="object",
 *
 *     @OA\Property(property="message", type="string", example="The fact field is required."),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *
 *         @OA\AdditionalProperties(
 *             type="array",
 *
 *             @OA\Items(type="string")
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/v1",
 *     operationId="getApiDiscovery",
 *     tags={"Discovery"},
 *     summary="API Root Discovery",
 *     description="Returns API service name, version, and exposed resource URLs.",
 *
 *     @OA\Response(
 *         response=200,
 *         description="Successful discovery response",
 *
 *         @OA\JsonContent(
 *             type="object",
 *
 *             @OA\Property(property="name", type="string", example="Jobsity PHP Human Interview challenge API"),
 *             @OA\Property(property="version", type="integer", example=1),
 *             @OA\Property(
 *                 property="resources",
 *                 type="object",
 *                 @OA\Property(property="cat-facts", type="string", example="http://localhost:8042/api/v1/cat-facts"),
 *                 @OA\Property(property="dog-facts", type="string", example="http://localhost:8042/api/v1/dog-facts"),
 *                 @OA\Property(property="documentation", type="string", example="http://localhost:8042/api/documentation")
 *             )
 *         )
 *     )
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
