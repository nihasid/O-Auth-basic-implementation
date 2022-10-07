<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use App\Helpers\ResponseHandler;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }

    // Add new method 
    protected function unauthenticated($request, array $guards)
    {
        abort( ResponseHandler::authorizationError('Token mismatched or expired. Please check token!'));
        /* abort(response()->json(
            [
                'api_status' => '403',
                'message' => 'UnAuthenticated',
            ], 403));
            */
    }
}
