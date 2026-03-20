<?php

declare (strict_types=1);
namespace Laravel\Sanctum\Http\Middleware;

use Illuminate\Routing\Pipeline;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
class Ensure_Frontend_Requests_Are_Stateful
{
    /**
     * Handle the incoming requests.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  callable  $next
     * @return \Illuminate\Http\Response
     */
    public function handle($request, $next)
    {
        $this->configure_secure_cookie_sessions();
        return (new Pipeline(app()))->send($request)->through(static::from_frontend($request) ? $this->frontend_middleware() : [])->then(fn($request) => $next($request));
    }
    /**
     * Configure secure cookie sessions.
     *
     * @return void
     */
    protected function configure_secure_cookie_sessions()
    {
        config(['session.http_only' => true, 'session.same_site' => 'lax']);
    }
    /**
     * Get the middleware that should be applied to requests from the "frontend".
     */
    protected function frontend_middleware(): array
    {
        $middleware = array_values(array_filter(array_unique([config('sanctum.middleware.encrypt_cookies', \Illuminate\Cookie\Middleware\Encrypt_Cookies::class), \Illuminate\Cookie\Middleware\Add_Queued_Cookies_To_Response::class, \Illuminate\Session\Middleware\Start_Session::class, config('sanctum.middleware.validate_csrf_token', config('sanctum.middleware.verify_csrf_token', \Illuminate\Foundation\Http\Middleware\Verify_Csrf_Token::class)), config('sanctum.middleware.authenticate_session')])));
        array_unshift($middleware, function ($request, $next) {
            $request->attributes->set('sanctum', true);
            return $next($request);
        });
        return $middleware;
    }
    /**
     * Determine if the given request is from the first-party application frontend.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public static function from_frontend($request)
    {
        $domain = $request->headers->get('referer') ?: $request->headers->get('origin');
        if (is_null($domain)) {
            return false;
        }
        $domain = Str::replace_first('https://', '', $domain);
        $domain = Str::replace_first('http://', '', $domain);
        $domain = Str::ends_with($domain, '/') ? $domain : "{$domain}/";
        $stateful = array_filter(config('sanctum.stateful', []));
        return Str::is(Collection::make($stateful)->map(function ($uri) use ($request): string {
            $uri = $uri === Sanctum::$current_request_host_placeholder ? $request->get_http_host() : $uri;
            return trim($uri) . '/*';
        })->all(), $domain);
    }
}