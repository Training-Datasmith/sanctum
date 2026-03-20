<?php

declare (strict_types=1);
namespace Laravel\Sanctum\Http\Controllers;

use Illuminate\Http\Json_Response;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
class Csrf_Cookie_Controller
{
    /**
     * Return an empty response simply to trigger the storage of the CSRF cookie in the browser.
     */
    public function show(Request $request): \Illuminate\Http\Json_Response|\Illuminate\Http\Response
    {
        if ($request->expects_json()) {
            return new Json_Response(status: 204);
        }
        return new Response(status: 204);
    }
}