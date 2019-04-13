<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\HttpResponseException;
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
            $message = new MethodNotAllowedHttpException([], Response::$statusTexts[$httpStatusCode], $exception);
        } elseif ($exception instanceof NotFoundHttpException) {
            $httpStatusCode = Response::HTTP_NOT_FOUND;
            $message = new NotFoundHttpException(Response::$statusTexts[$httpStatusCode], $exception);
        } elseif ($exception instanceof AuthorizationException) {
            $httpStatusCode = Response::HTTP_FORBIDDEN;
            $message = new AuthorizationException(Response::$statusTexts[$httpStatusCode], $httpStatusCode);
        } elseif ($exception instanceof \Dotenv\Exception\ValidationException && $exception->getResponse()) {
            $httpStatusCode = Response::HTTP_BAD_REQUEST;
            $message = new \Dotenv\Exception\ValidationException(Response::$statusTexts[$httpStatusCode], $httpStatusCode, $exception);
        } else {
            $httpStatusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
            $message = new HttpException($httpStatusCode, Response::$statusTexts[$httpStatusCode]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => $message->getMessage(),
            'data' => $request->toArray()
        ], $httpStatusCode);
    }
}
