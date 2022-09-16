<?php

namespace App\Exceptions;

use Throwable;
use App\Traits\API\RestTrait;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class Handler extends ExceptionHandler
{
    use RestTrait;

    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
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
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        if ($request->wantsJson() && $this->isApiCall($request)) {   //add Accept: application/json in request
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * handle any API exception in a custom way
     * and prepare any custom message if required
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleApiException($request, $exception)
    {
        return $this->customApiResponse($request, $exception);
    }

    /**
     * prepare a custom API response expected in the front-end
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function customApiResponse($request, $exception)
    {
        switch ($exception) {
            case $exception instanceof AuthorizationException:
                $message = $exception->getMessage();
                $statusCode = Response::HTTP_FORBIDDEN;
                break;

            case $exception instanceof NotFoundHttpException:
                $message = $exception->getMessage();
                $statusCode = Response::HTTP_NOT_FOUND;
                break;

            case $exception instanceof BadRequestHttpException:
                $message = $exception->getMessage();
                $statusCode = Response::HTTP_BAD_REQUEST;
                break;

            case $exception instanceof ServiceUnavailableHttpException:
                $message = $exception->getMessage();
                $statusCode = Response::HTTP_SERVICE_UNAVAILABLE;
                break;

            case $exception instanceof ValidationException:
                $message = $exception->getMessage();
                $statusCode = Response::HTTP_CONFLICT;
                break;

            case $exception instanceof HttpResponseException:
                $message = $exception->getMessage();
                $statusCode = $exception->getStatusCode();
                break;

            default:
                $message = 'Something went wrong, Please try again later.';
                if (method_exists($exception, 'getStatusCode')) {
                    $statusCode = $exception->getStatusCode();
                } else {
                    $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                }
                break;
        }

        // if debug flag is true show the actual detailed error message
        if (config('app.debug')) {
            $message = $exception->getMessage();
        }

        // return the error json reponse
        return $this->errorResponse($message, $statusCode);
    }
}
