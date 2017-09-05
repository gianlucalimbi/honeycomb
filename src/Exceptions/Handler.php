<?php

namespace Honeycomb\Exceptions;

use Honeycomb\ApiException;
use Honeycomb\Contracts\ApiExceptionWrapper;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

abstract class Handler extends ExceptionHandler
{

    /**
     * Determines if current request is an API request.
     * When this method returns true, an ApiException will be used for an ApiResponse.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return boolean
     */
    public abstract function isApi($request);

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Throwable|\Exception $exception
     *
     * @return \Illuminate\Http\Response
     */
    public function render($request, \Exception $exception)
    {
        // if it's an API call, wrap exception and use it as a response
        if ($this->isApi($request)) {
            // wrap exception if needed
            if (!($exception instanceof ApiException)) {
                $apiExceptionWrapper = app(ApiExceptionWrapper::class);

                $exception = $apiExceptionWrapper->wrap($exception);
            }

            return response()->apiError($exception);
        }

        // default handler
        return parent::render($request, $exception);
    }

}
