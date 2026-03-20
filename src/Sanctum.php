<?php

declare (strict_types=1);
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
    public static $personal_access_token_model = \Laravel\Sanctum\Personal_Access_Token::class;
    /**
     * A callback that can get the token from the request.
     *
     * @var callable|null
     */
    public static $access_token_retrieval_callback;
    /**
     * A callback that can add to the validation of the access token.
     *
     * @var callable|null
     */
    public static $access_token_authentication_callback;
    /**
     * A placeholder to instruct Sanctum to include the current request host in the list of stateful domains.
     *
     * @var string;
     */
    public static $current_request_host_placeholder = '__SANCTUM_CURRENT_REQUEST_HOST__';
    /**
     * Get the current application URL from the "APP_URL" environment variable - with port.
     */
    public static function current_application_url_with_port(): string
    {
        $app_url = config('app.url');
        return $app_url ? ',' . parse_url($app_url, PHP_URL_HOST) . (parse_url($app_url, PHP_URL_PORT) ? ':' . parse_url($app_url, PHP_URL_PORT) : '') : '';
    }
    /**
     * Get a fixed token instructing Sanctum to include the current request host in the list of stateful domains.
     */
    public static function current_request_host(): string
    {
        return ',' . static::$current_request_host_placeholder;
    }
    /**
     * Set the current user for the application with the given abilities.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|\Laravel\Sanctum\HasApiTokens  $user
     * @param  array  $abilities
     * @param  string  $guard
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public static function acting_as($user, $abilities = [], $guard = 'sanctum')
    {
        $token = Mockery::mock(self::personal_access_token_model())->should_ignore_missing(false);
        if (in_array('*', $abilities)) {
            $token->should_receive('can')->with_any_args()->and_return(true);
        } else {
            foreach ($abilities as $ability) {
                $token->should_receive('can')->with($ability)->and_return(true);
            }
        }
        $user->with_access_token($token);
        if (isset($user->was_recently_created) && $user->was_recently_created) {
            $user->was_recently_created = false;
        }
        app('auth')->guard($guard)->set_user($user);
        app('auth')->should_use($guard);
        return $user;
    }
    /**
     * Set the personal access token model name.
     *
     * @param  class-string<TToken>  $model
     */
    public static function use_personal_access_token_model($model): void
    {
        static::$personal_access_token_model = $model;
    }
    /**
     * Specify a callback that should be used to fetch the access token from the request.
     */
    public static function get_access_token_from_request_using(?callable $callback): void
    {
        static::$access_token_retrieval_callback = $callback;
    }
    /**
     * Specify a callback that should be used to authenticate access tokens.
     */
    public static function authenticate_access_tokens_using(callable $callback): void
    {
        static::$access_token_authentication_callback = $callback;
    }
    /**
     * Get the token model class name.
     *
     * @return class-string<TToken>
     */
    public static function personal_access_token_model()
    {
        return static::$personal_access_token_model;
    }
}