<?php

declare (strict_types=1);
namespace Laravel\Sanctum\Http\Middleware;

use Illuminate\Auth\Authentication_Exception;
use Laravel\Sanctum\Exceptions\Missing_Ability_Exception;
class Check_For_Any_Ability
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$abilities
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\AuthenticationException|\Laravel\Sanctum\Exceptions\MissingAbilityException
     */
    public function handle($request, $next, ...$abilities)
    {
        if (!$request->user() || !$request->user()->current_access_token()) {
            throw new Authentication_Exception();
        }
        foreach ($abilities as $ability) {
            if ($request->user()->token_can($ability)) {
                return $next($request);
            }
        }
        throw new Missing_Ability_Exception($abilities);
    }
}