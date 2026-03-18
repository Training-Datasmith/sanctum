<?php

declare(strict_types=1);

namespace Laravel\Sanctum;

use Mockery;

/**
 * @template TToken of \Laravel\Sanctum\Contracts\HasAbilities = \Laravel\Sanctum\PersonalAccessToken
 */
class Sanctum
{
    /**
     * The personal access client model class name.
     *
     * @var class-string<TToken>
     */
    public static $personalAccessTokenModel = \Laravel\Sanctum\PersonalAccessToken::class;

    /**
     * A callback that can get the token from the request.
     *
     * @var callable|null
     */
    public static $accessTokenRetrievalCallback;

    /**
     * A callback that can add to the validation of the access token.
     *
     * @var callable|null
     */
    public static $accessTokenAuthenticationCallback;

    /**
     * A placeholder to instruct Sanctum to include the current request host in the list of stateful domains.
     *
     * @var string;
     */
    public static $currentRequestHostPlaceholder = '__SANCTUM_CURRENT_REQUEST_HOST__';

    /**
     * Get the current application URL from the "APP_URL" environment variable - with port.
     */
    public static function currentApplicationUrlWithPort(): string
    {
        $appUrl = config('app.url');

        return $appUrl ? ','.parse_url($appUrl, PHP_URL_HOST).(parse_url($appUrl, PHP_URL_PORT) ? ':'.parse_url($appUrl, PHP_URL_PORT) : '') : '';
    }

    /**
     * Get a fixed token instructing Sanctum to include the current request host in the list of stateful domains.
     */
    public static function currentRequestHost(): string
    {
        return ','.static::$currentRequestHostPlaceholder;
    }

    /**
     * Set the current user for the application with the given abilities.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|\Laravel\Sanctum\HasApiTokens  $user
     * @param  array  $abilities
     * @param  string  $guard
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public static function actingAs($user, $abilities = [], $guard = 'sanctum')
    {
        $token = Mockery::mock(self::personalAccessTokenModel())->shouldIgnoreMissing(false);

        if (in_array('*', $abilities)) {
            $token->shouldReceive('can')->withAnyArgs()->andReturn(true);
        } else {
            foreach ($abilities as $ability) {
                $token->shouldReceive('can')->with($ability)->andReturn(true);
            }
        }

        $user->withAccessToken($token);

        if (isset($user->wasRecentlyCreated) && $user->wasRecentlyCreated) {
            $user->wasRecentlyCreated = false;
        }

        app('auth')->guard($guard)->setUser($user);

        app('auth')->shouldUse($guard);

        return $user;
    }

    /**
     * Set the personal access token model name.
     *
     * @param  class-string<TToken>  $model
     */
    public static function usePersonalAccessTokenModel($model): void
    {
        static::$personalAccessTokenModel = $model;
    }

    /**
     * Specify a callback that should be used to fetch the access token from the request.
     */
    public static function getAccessTokenFromRequestUsing(?callable $callback): void
    {
        static::$accessTokenRetrievalCallback = $callback;
    }

    /**
     * Specify a callback that should be used to authenticate access tokens.
     */
    public static function authenticateAccessTokensUsing(callable $callback): void
    {
        static::$accessTokenAuthenticationCallback = $callback;
    }

    /**
     * Get the token model class name.
     *
     * @return class-string<TToken>
     */
    public static function personalAccessTokenModel()
    {
        return static::$personalAccessTokenModel;
    }
}
