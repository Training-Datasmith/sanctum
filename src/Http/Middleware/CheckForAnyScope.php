<?php

declare (strict_types=1);
namespace Laravel\Sanctum\Http\Middleware;

use Laravel\Sanctum\Exceptions\Missing_Scope_Exception;
/**
 * @deprecated
 * @see \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility
 */
class Check_For_Any_Scope
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$scopes
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\AuthenticationException|\Laravel\Sanctum\Exceptions\MissingScopeException
     */
    public function handle($request, $next, ...$scopes)
    {
        try {
            return (new Check_For_Any_Ability())->handle($request, $next, ...$scopes);
        } catch (\Laravel\Sanctum\Exceptions\Missing_Ability_Exception $e) {
            throw new Missing_Scope_Exception($e->abilities());
        }
    }
}