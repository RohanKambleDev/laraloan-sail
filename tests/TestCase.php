<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    public function responseKeys()
    {
        return [
            'status',
            'message',
            'data'
        ];
    }

    public function setUp(): void
    {
        // first include all the normal setUp operations
        parent::setUp();

        // $this->withoutExceptionHandling();

        // now re-register all the roles and permissions (clears cache and reloads relations)
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->registerPermissions();
        $this->seed();
    }
}
