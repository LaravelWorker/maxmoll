<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Dedoc\Scramble\Scramble;
use Illuminate\Routing\Route;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */

    public function boot(): void
    {
        // Явно просим Scramble обрабатывать все роуты, у которых URI начинается на "api/"
        Scramble::routes(function (Route $route) {
            return str_starts_with($route->uri(), 'api/');
        });

        Relation::enforceMorphMap([
            'order'    => \App\Models\Order::class,
            'supply'   => \App\Models\Supply::class,
            'transfer' => \App\Models\Transfer::class,
        ]);
}
}
