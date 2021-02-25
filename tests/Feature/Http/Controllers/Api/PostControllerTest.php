<?php

namespace Tests\Feature\Http\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use App\User;
use App\Post;

class PostTest extends TestCase
{
    use RefreshDatabase;

    /** 
     * Los usuarios no autenticados no pueden acceder al api de post
     * 
     * @test
    */
    public function unauthenticated_users_cannot_access_the_post_api()
    {
        //$this->withoutExceptionHandling();

        $this->json('GET',     '/api/posts')->assertStatus(401);
        $this->json('POST',    '/api/posts')->assertStatus(401);
        $this->json('GET',     '/api/posts/1000')->assertStatus(401);
        $this->json('PUT',     '/api/posts/1000')->assertStatus(401);
        $this->json('DELETE',  '/api/posts/1000')->assertStatus(401);
    }

    /**
     * Los usuarios autenticados pueden acceder al listado de posts
     * 
     * @test
     */
    public function can_see_paginated_post_list()
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        factory(Post::class, 5)->create();

        $response = $this->actingAs($user, 'api')->json('GET', '/api/posts');

        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'created']
            ],
            'links' => ['first', 'last', 'prev', 'next'],
        ])->assertStatus(200);
    }

    /**
     * los usuarios ayenticados pueden crear un post
     * 
     * @test
     */
    public function a_user_can_create_a_post()
    {
        //$this->withoutExceptionHandling();

        $user = factory(User::class)->create();

        $response = $this->actingAs($user, 'api')->json('POST', '/api/posts', [
            'title' => 'Post de prueba'
        ]);

        $response->assertJsonStructure(['id', 'title', 'created'])
        ->assertJson(['tilte' => 'Post de prueba'])
        ->assertStatus(201);

        $this->assertDatabaseHas('posts', [
            'title' => 'Post de prueba'
        ]);
    }

    /**
     * validadndo si crea un post con el titulo vacio
     * 
     * @test
     */
    public function validation_if_you_create_a_post_with_the_empty_title()
    {
        //$this->withoutExceptionHandling();

        $user = factory(User::class)->create();

        $response = $this->actingAs($user, 'api')->json('POST', '/api/posts', [
            'title' => ''
        ]);

        $response->assertStatus(422)
        //->assertExactValidationErrors('title')
        ->assertExactJson([
            'message' => 'The given data was invalid',
            'errors' => [
                'title' => ['The title field is required']
            ]
        ]);
    }

    /**
     * Puede acceder y ver un post
     * 
     * @test
     */
    public function can_get_a_post()
    {
        //$this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $post = factory(Post::class)->create();

        $response = $this->actingAs($user, 'api')->json('GET', '/api/posts/$post->id');

        $response->assertJsonStructure(['id', 'title', 'created'])
        ->assertJson(['title' => $post->title])
        ->assertStatus(200);
    }

    /**
     * si el post no existe recibira un 404
     * 
     * @test
     */

    public function test_store()
    {
        //$this->withoutExceptionHandling();
        $response = $this->json('POST', '/api/posts', [
            'title' => 'El post de prueba'
        ]);

        $response->assertJsonStructure(['id', 'title', 'created_at', 'updated_at'])
        ->assertJson(['title' => 'El post de prueba'])
        ->assertStatus(201);//peticion en forma ok y genara un recurso

        $this->assertDatabaseHas('posts', ['title' => 'El post de prueba']);
    }

    public function test_validate_title()
    {
        $response = $this->json('POST', '/api/posts', [
            'title' => ''
        ]);

        $response->assertStatus(422)//estatus htto 422
        ->assertJsonValidationErrors('title');
    }
}
