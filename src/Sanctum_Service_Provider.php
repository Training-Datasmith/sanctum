<?php

declare (strict_types=1);
namespace Laravel\Sanctum;

use Illuminate\Auth\Request_Guard;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Service_Provider;
use Laravel\Sanctum\Console\Commands\Prune_Expired;
use Laravel\Sanctum\Http\Controllers\Csrf_Cookie_Controller;
use Laravel\Sanctum\Http\Middleware\Ensure_Frontend_Requests_Are_Stateful;
class Sanctum_Service_Provider extends Service_Provider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        config(['auth.guards.sanctum' => array_merge(['driver' => 'sanctum', 'provider' => null], config('auth.guards.sanctum', []))]);
        if (!app()->configuration_is_cached()) {
            $this->merge_config_from(__DIR__ . '/../config/sanctum.php', 'sanctum');
        }
    }
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->running_in_console()) {
            $this->publishes_migrations([__DIR__ . '/../database/migrations' => database_path('migrations')], 'sanctum-migrations');
            $this->publishes([__DIR__ . '/../config/sanctum.php' => config_path('sanctum.php')], 'sanctum-config');
            $this->commands([Prune_Expired::class]);
        }
        $this->define_routes();
        $this->configure_guard();
        $this->configure_middleware();
    }
    /**
     * Define the Sanctum routes.
     *
     * @return void
     */
    protected function define_routes()
    {
        if (app()->routes_are_cached() || config('sanctum.routes') === false) {
            return;
        }
        Route::group(['prefix' => config('sanctum.prefix', 'sanctum')], function (): void {
            Route::get('/csrf-cookie', Csrf_Cookie_Controller::class . '@show')->middleware('web')->name('sanctum.csrf-cookie');
        });
    }
    /**
     * Configure the Sanctum authentication guard.
     *
     * @return void
     */
    protected function configure_guard()
    {
        Auth::resolved(function ($auth): void {
            $request_guard_creator = fn($config) => $this->create_guard($auth, $config);
            $auth->extend('sanctum', fn($app, $name, array $config) => tap($request_guard_creator($config), function ($guard): void {
                app()->refresh('request', $guard, 'setRequest');
            }));
        });
    }
    /**
     * Register the guard.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @param  array  $config
     * @return RequestGuard
     */
    protected function create_guard($auth, $config)
    {
        return new Request_Guard(new Guard($auth, config('sanctum.expiration'), $config['provider'], config('sanctum.last_used_at', true)), request(), $auth->create_user_provider($config['provider'] ?? null));
    }
    /**
     * Configure the Sanctum middleware and priority.
     *
     * @return void
     */
    protected function configure_middleware()
    {
        $kernel = app()->make(Kernel::class);
        $kernel->prepend_to_middleware_priority(Ensure_Frontend_Requests_Are_Stateful::class);
    }
}