<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        $response = parent::render($request, $e);
        // switch ($response->status()) {
        //     case 403:
        //         return response()->json([
        //             'code' => $response->status(),
        //             'message' => 'Forbidden'
        //         ], $response->status());

        //     case 404:
        //         return response()->json([
        //             'code' => $response->status(),
        //             'message' => 'Not found'
        //         ], $response->status());

        //     case 405:
        //         return response()->json([
        //             'code' => $response->status(),
        //             'message' => 'Method Not Allowed.'
        //         ], $response->status());

        //     case 419:
        //         return response()->json([
        //             'code' => 405,
        //             'message' => 'Method Not Allowed.'
        //         ], Response::HTTP_METHOD_NOT_ALLOWED);
        //         break;

        //     default:
        //         break;
        // }
        return response()->json([
            'code' => $response->status(),
            'message' => $e->getMessage(),
            'trace' => $e->getTrace(),
        ], $response->status());
    }
}
