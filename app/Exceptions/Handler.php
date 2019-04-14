<?php

namespace App\Exceptions;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception $exception
     * @return void
     * @throws Exception
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof MethodNotAllowedHttpException) {
            $httpStatusCode = Response::HTTP_METHOD_NOT_ALLOWED;
            $message = Response::$statusTexts[$httpStatusCode];
        } elseif ($exception instanceof NotFoundHttpException && $exception instanceof ModelNotFoundException) {
            $httpStatusCode = Response::HTTP_NOT_FOUND;
            $message = Response::$statusTexts[$httpStatusCode];
        } elseif ($exception instanceof AuthorizationException) {
            $httpStatusCode = Response::HTTP_UNAUTHORIZED;
            $message = Response::$statusTexts[$httpStatusCode];
        } elseif ($exception instanceof \Dotenv\Exception\ValidationException && $exception->getResponse()) {
            $httpStatusCode = Response::HTTP_BAD_REQUEST;
            $message = Response::$statusTexts[$httpStatusCode];
        } else {
            $httpStatusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            $message = Response::$statusTexts[$httpStatusCode];
        }

        if (Schema::connection(env('DB_CONNECTION'))->hasTable('error_logs')) {
            DB::table('error_logs')->insert([
                'path' => $request->path(),
                'method' => $request->method(),
                'request' => json_encode($request->toArray()),
                'message' => empty($exception->getMessage()) ? $message : $exception->getMessage(),
                'line' => $exception->getLine(),
                'file' => $exception->getFile(),
                'trace' => $exception->getTraceAsString(),
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString()
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => $message,
            'data' => []
        ], $httpStatusCode);
    }
}
