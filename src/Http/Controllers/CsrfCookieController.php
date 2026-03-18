<?php

namespace Laravel\Sanctum\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CsrfCookieController
{
    /**
     * Return an empty response simply to trigger the storage of the CSRF cookie in the browser.
     */
    public function show(Request $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
    {
        if ($request->expectsJson()) {
            return new JsonResponse(status: 204);
        }

        return new Response(status: 204);
    }
}
