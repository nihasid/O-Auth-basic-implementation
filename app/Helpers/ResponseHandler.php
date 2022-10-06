<?php

/* *created by Niha Siddiqui 2022-10-05
    * all API responses handled in this file
*/
namespace App\Helpers;

use Illuminate\Support\Arr;

class ResponseHandler
{

    static function success( $body = [], $message = "", $cookie = false )
    {
        $message = empty($message) ? __('messages.general.success') : $message;
        return self::send(Constant::HTTP_RESPONSE_STATUSES['success'], $message, $body, null, $cookie);
    }

    static function validationError( $validationErrors, $message = "" )
    {
        $message = empty($message) ? __('messages.general.validation') : $message;
        if ( is_array($validationErrors) ) {
            $errorMessages = array_values($validationErrors);
        } else {
            $errorMessages = $validationErrors->getMessages();
            $errorMessages = Arr::flatten($errorMessages);
        }
        return self::send(Constant::HTTP_RESPONSE_STATUSES['validationError'], $message, $errorMessages, null);
    }

    static function authenticationError( $message = "" )
    {
        $message = empty($message) ? __('messages.general.unauthenticated') : $message;
        return self::send(Constant::HTTP_RESPONSE_STATUSES['authenticationError'], $message, [], null);
    }

    static function authorizationError( $message = "" )
    {
        $message = empty($message) ? __('messages.general.unauthorized') : $message;
        return self::send(Constant::HTTP_RESPONSE_STATUSES['authorizationError'], $message, [], null);
    }

    static function failure( $message = "" )
    {
        $message = empty($message) ? __('messages.general.failed') : $message;
        return self::send(Constant::HTTP_RESPONSE_STATUSES['failed'], $message, [], null);
    }

    static function serverError( $exception = null, $message = "" )
    {
        $message = empty($message) ? __('messages.general.crashed') : $message;
        $exceptionMsg = $exception ? $exception->getMessage() : '';
        return self::send(Constant::HTTP_RESPONSE_STATUSES['serverError'], $message, [], $exceptionMsg);
    }

    private static function send( $status, $message, $body, $exception, $cookie = false )
    {
        $response = response()->json([
            'status' => $status,
            'message' => $message,
            'body' => $body,
            'exception' => config('app.debug') ? $exception : null
        ], $status, [], JSON_UNESCAPED_UNICODE);

        if ($cookie) {
            $response->cookie($cookie);
        }

        return $response;
    }
}
