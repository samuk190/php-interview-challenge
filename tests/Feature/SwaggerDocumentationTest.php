<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class SwaggerDocumentationTest extends TestCase
{
    public function test_swagger_ui_is_accessible(): void
    {
        $response = $this->get('/api/documentation');

        $response->assertStatus(200);
        $response->assertSee('swagger-ui');
    }

    public function test_swagger_json_docs_are_accessible_and_valid(): void
    {
        $response = $this->get('/docs');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'openapi',
            'info' => ['title', 'version', 'description'],
            'paths',
            'components' => ['schemas'],
        ]);
        $response->assertJsonPath('info.title', 'Animal Facts API');
        $response->assertJsonPath('paths./api/v1/cat-facts.get.summary', 'List cat facts (paginated)');
        $response->assertJsonPath('paths./api/v1/dog-facts.get.summary', 'List dog facts (paginated)');
    }
}
