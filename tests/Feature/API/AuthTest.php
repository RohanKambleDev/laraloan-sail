<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    /**
     * test_a_user_can_register
     * vendor/bin/phpunit --filter test_a_user_can_login
     * php artisan test --filter=test_a_user_can_login
     *
     * @return void
     */
    public function test_a_user_can_register()
    {
        // prepare
        $input = [
            'name' => 'Rohan',
            'email' => 'rohu2187@gmail.com',
            'password' => 'rohan',
            'password_confirmation' => 'rohan',
        ];

        // perform
        $response = $this->postJson(route('user-register'), $input)->assertStatus(201);

        // predict
        $this->assertDatabaseHas('users', ['email' => 'rohu2187@gmail.com']);

        // collect the reponse, in array
        $reponseArr = $response->json();

        // first assert base common keys expected in response
        $responseKeys = $this->responseKeys();
        foreach ($responseKeys as $responseKey) {
            $this->assertArrayHasKey($responseKey, $reponseArr);
        }

        // later assert any other keys required
        $this->assertArrayHasKey('token', $reponseArr['data']);
    }

    public function test_a_user_can_login()
    {
        // prepare
        // first register a new user
        $input = [
            'name' => 'Rohan',
            'email' => 'rohu2187@gmail.com',
            'password' => 'rohan',
            'password_confirmation' => 'rohan',
        ];
        // check is user registered
        $this->postJson(route('user-register'), $input)->assertStatus(201);

        //  perform
        $input = [
            'email' => 'rohu2187@gmail.com',
            'password' => 'rohan',
        ];
        $response = $this->postJson(route('user-login'), $input)->assertStatus(200);

        //Write the response in laravel-testing.log
        // Log::channel('testing')->info(1, [$response->getContent()]);

        // predict
        $response->assertStatus(200);

        // collect the reponse, in array
        $reponseArr = $response->json();

        // first assert base common keys expected in response
        $responseKeys = $this->responseKeys();
        foreach ($responseKeys as $responseKey) {
            $this->assertArrayHasKey($responseKey, $reponseArr);
        }

        // later assert any other keys required
        $this->assertArrayHasKey('token', $reponseArr['data']);
    }
}
