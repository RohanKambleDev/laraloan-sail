<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Policies\LoanPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('view-loan-list', [LoanPolicy::class, 'viewAny']);
        Gate::define('create-loan', [LoanPolicy::class, 'create']);
        Gate::define('view-loan', [LoanPolicy::class, 'view']);
        Gate::define('repay-loan', [LoanPolicy::class, 'repay']);
        Gate::define('approve-loan', [LoanPolicy::class, 'approve']);
    }
}
