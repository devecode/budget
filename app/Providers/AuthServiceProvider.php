<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
      $this->registerPolicies();

      // Register Passport routes
      Passport::routes();

      Passport::useTokenModel(CustomTokenModel::class);
      Passport::useClientModel(CustomClientModel::class);

      Passport::personalAccessClientId(config('passport.personal_access_client.id'));

    }
}
