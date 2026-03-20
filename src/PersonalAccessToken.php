<?php

declare (strict_types=1);
namespace Laravel\Sanctum;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\Contracts\Has_Abilities;
class Personal_Access_Token extends Model implements Has_Abilities
{
    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = ['abilities' => 'json', 'last_used_at' => 'datetime', 'expires_at' => 'datetime'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'token', 'abilities', 'expires_at'];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ['token'];
    /**
     * Get the tokenable model that the access token belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function tokenable()
    {
        return $this->morph_to('tokenable');
    }
    /**
     * Find the token instance matching the given token.
     *
     * @param  string  $token
     * @return static|null
     */
    public static function find_token($token)
    {
        if (!str_contains($token, '|')) {
            return static::where('token', hash('sha256', $token))->first();
        }
        [$id, $token] = explode('|', $token, 2);
        if ($instance = static::find($id)) {
            return hash_equals($instance->token, hash('sha256', $token)) ? $instance : null;
        }
    }
    /**
     * Determine if the token has a given ability.
     *
     * @param  string  $ability
     * @return bool
     */
    public function can($ability)
    {
        return in_array('*', $this->abilities) || array_key_exists($ability, array_flip($this->abilities));
    }
    /**
     * Determine if the token is missing a given ability.
     *
     * @param  string  $ability
     * @return bool
     */
    public function cant($ability)
    {
        return !$this->can($ability);
    }
}