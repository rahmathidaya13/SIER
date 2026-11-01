<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Models\ChannelModel;
use App\Models\CustomerModel;
use App\Models\LeadModel;
use App\Models\ProductModel;
use App\Models\SalesModel;
use App\Models\User;
use App\Policies\ChannelPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\LeadPolicy;
use App\Policies\ProductPolicy;
use App\Policies\SalesPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        ProductModel::class => ProductPolicy::class,
        LeadModel::class => LeadPolicy::class,
        SalesModel::class => SalesPolicy::class,
        ChannelModel::class => ChannelPolicy::class,
        CustomerModel::class => CustomerPolicy::class
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
