<?php

declare (strict_types=1);
namespace Laravel\Sanctum;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
class New_Access_Token implements Arrayable, Jsonable
{
    /**
     * Create a new access token result.
     *
     * @param  \Laravel\Sanctum\PersonalAccessToken  $accessToken  The access token instance.
     * @param  string  $plainTextToken  The plain text version of the token.
     */
    public function __construct(public Personal_Access_Token $access_token, public string $plain_text_token)
    {
    }
    /**
     * Get the instance as an array.
     *
     * @return array<string, string>
     */
    public function to_array(): array
    {
        return ['accessToken' => $this->access_token, 'plainTextToken' => $this->plain_text_token];
    }
    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function to_json($options = 0)
    {
        return json_encode($this->to_array(), $options);
    }
}