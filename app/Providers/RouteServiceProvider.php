<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            // Роут для SPA (web.php)
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // Кастомные роуты сущностей
            Route::middleware('api')
                ->prefix('api/products')
                ->group(base_path('routes/api/products.php'));

            Route::middleware('api')
                ->prefix('api/customers')
                ->group(base_path('routes/api/customers.php'));

            Route::middleware('api')
                ->prefix('api/warehouses')
                ->group(base_path('routes/api/warehouses.php'));

            Route::middleware('api')
                ->prefix('api/stocks')
                ->group(base_path('routes/api/stock.php'));

            Route::middleware('api')
                ->prefix('api/orders')
                ->group(base_path('routes/api/orders.php'));

            Route::middleware('api')
                ->prefix('api/supplies')
                ->group(base_path('routes/api/supplies.php'));

            Route::middleware('api')
                ->prefix('api/stock-movements')
                ->group(base_path('routes/api/stock-movements.php'));

            Route::middleware('api')
                ->prefix('api/transfers')
                ->group(base_path('routes/api/transfers.php'));
        });
    }
}