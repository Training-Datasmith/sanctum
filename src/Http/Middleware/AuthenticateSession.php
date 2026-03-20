<?php

declare (strict_types=1);
namespace Laravel\Sanctum\Http\Middleware;

use Closure;
use Illuminate\Auth\Authentication_Exception;
use Illuminate\Auth\Session_Guard;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Symfony\Component\Http_Foundation\Response;
class Authenticate_Session
{
    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth  The authentication factory implementation.
     */
    public function __construct(protected Auth_Factory $auth)
    {
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->has_session() || !$request->user()) {
            return $next($request);
        }
        $guards = Collection::make(Arr::wrap(config('sanctum.guard')))->map_with_keys(fn($guard): array => [$guard => $this->auth->guard($guard)])->filter(fn($guard): bool => $guard instanceof Session_Guard);
        $should_logout = $guards->filter(fn($guard, $driver) => $request->session()->has('password_hash_' . $driver))->filter(fn(\Illuminate\Auth\Session_Guard $guard, $driver): bool => !$this->validate_password_hash($guard, $request->user()->get_auth_password(), $request->session()->get('password_hash_' . $driver)));
        if ($should_logout->is_not_empty()) {
            $should_logout->each->logout_current_device();
            $request->session()->flush();
            throw new Authentication_Exception('Unauthenticated.', [...$should_logout->keys()->all(), 'sanctum']);
        }
        return tap($next($request), function () use ($request, $guards): void {
            if (!is_null($guard = $this->get_first_guard_with_user($guards->keys()))) {
                $this->store_password_hash_in_session($request, $guard);
            }
        });
    }
    /**
     * Get the first authentication guard that has a user.
     *
     * @return string|null
     */
    protected function get_first_guard_with_user(Collection $guards)
    {
        return $guards->first(function ($guard): bool {
            $guard_instance = $this->auth->guard($guard);
            return method_exists($guard_instance, 'hasUser') && $guard_instance->has_user();
        });
    }
    /**
     * Store the user's current password hash in the session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function store_password_hash_in_session($request, string $guard)
    {
        $guard_instance = $this->auth->guard($guard);
        $request->session()->put(["password_hash_{$guard}" => method_exists($guard_instance, 'hashPasswordForCookie') ? $guard_instance->hash_password_for_cookie($guard_instance->user()->get_auth_password()) : $guard_instance->user()->get_auth_password()]);
    }
    /**
     * Validate the password hash against the stored value.
     */
    protected function validate_password_hash(Session_Guard $guard, ?string $password_hash, string $stored_value): bool
    {
        // Try new HMAC format first (Laravel 12.45.0+)...
        if (!method_exists($guard, 'hashPasswordForCookie')) {
            // Fall back to raw password hash format for backward compatibility...
            return hash_equals($password_hash ?? '', $stored_value);
        }
        if (hash_equals($guard->hash_password_for_cookie($password_hash), $stored_value)) {
            return true;
        }
        // Fall back to raw password hash format for backward compatibility...
        return hash_equals($password_hash ?? '', $stored_value);
    }
}