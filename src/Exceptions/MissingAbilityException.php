<?php

declare (strict_types=1);
namespace Laravel\Sanctum\Exceptions;

use Illuminate\Auth\Access\Authorization_Exception;
use Illuminate\Support\Arr;
class Missing_Ability_Exception extends Authorization_Exception
{
    /**
     * Create a new missing scope exception.
     *
     * @param  array|string  $abilities  The abilities that the user did not have.
     * @param  string  $message
     */
    public function __construct(protected $abilities = [], $message = 'Invalid ability provided.')
    {
        parent::__construct($message);
        $this->abilities = Arr::wrap($abilities);
    }
    /**
     * Get the abilities that the user did not have.
     *
     * @return array
     */
    public function abilities()
    {
        return $this->abilities;
    }
}