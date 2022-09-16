<?php

namespace Tests\Unit\API;

// use PHPUnit\Framework\TestCase;
use Tests\TestCase;

class LoanTest extends TestCase
{

    public function test_register()
    {
        // prepare
        // perform
        $response = $this->post('/api/v1/auth/login');
        // predict
        $response->assertStatus(200);
    }
}
