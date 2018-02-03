<?php

namespace Honeycomb\Support;

use Honeycomb\ApiException;
use Honeycomb\Contracts\ApiExceptionWrapper;
use Honeycomb\Feedback;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exception\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BaseApiExceptionWrapper implements ApiExceptionWrapper
{

    /**
     * An array of supported exceptions.
     * Use the fully qualified class name as the key and the function name as value. Eg:
     *
     *   \Fully\Qualified\Class\Name\CustomException::class => 'wrapCustomException',
     *
     * @var array
     */
    protected $exceptions = [];

    /**
     * Wrap given Exception in an ApiException.
     *
     * @param \Throwable|\Exception $exception
     *
     * @return ApiException
     */
    public function wrap($exception)
    {
        if ($exception instanceof ApiException) {
            return $exception;
        }

        // default handled exceptions
        $exceptions = [
            HttpException::class => 'wrapHttpException',
            HttpResponseException::class => 'wrapHttpResponseException',
            ModelNotFoundException::class => 'wrapModelNotFoundException',
        ];

        $exceptions = array_merge($exceptions, $this->exceptions);

        $functionName = $this->findBestMatch($exception, $exceptions);

        return $this->$functionName($exception);
    }

    /**
     * Wrap given HttpException in an ApiException.
     *
     * @param HttpException $exception
     *
     * @return ApiException
     */
    protected function wrapHttpException($exception)
    {
        $status = $exception->getStatusCode();
        $errors = null;

        $errorMessage = strtolower(isset(Response::$statusTexts[$status]) ? Response::$statusTexts[$status] : 'error');
        $errorDescription = $this->getDescriptionForStatus($status);

        $error = Feedback::error($errorMessage, $errorDescription);

        return new ApiException($status, $error, $errors, $exception);
    }

    /**
     * Wrap given HttpResponseException in an ApiException.
     *
     * @param HttpResponseException $exception
     *
     * @return ApiException
     */
    protected function wrapHttpResponseException($exception)
    {
        $status = $exception->getResponse()->getStatusCode();
        $errors = null;

        $errorMessage = strtolower(isset(Response::$statusTexts[$status]) ? Response::$statusTexts[$status] : 'error');
        $errorDescription = $this->getDescriptionForStatus($status);

        $error = Feedback::error($errorMessage, $errorDescription);

        return new ApiException($status, $error, $errors, $exception);
    }

    /**
     * Wrap given ModelNotFoundException in an ApiException.
     *
     * @param ModelNotFoundException $exception
     *
     * @return ApiException
     */
    protected function wrapModelNotFoundException($exception)
    {
        $status = 404;
        $errors = null;

        $errorMessage = sprintf('%s not found', snake_case(class_basename($exception->getModel())));
        $errorDescription = trans('honeycomb::errors.not_found');

        $error = Feedback::error($errorMessage, $errorDescription);

        return new ApiException($status, $error, $errors, $exception);
    }

    /**
     * Wrap given Exception in an ApiException.
     *
     * @param \Exception $exception
     *
     * @return ApiException
     */
    protected function wrapException($exception)
    {
        $status = 500;
        $errors = null;

        $errorMessage = 'internal server error';
        $errorDescription = trans('honeycomb::errors.generic');

        $error = Feedback::error($errorMessage, $errorDescription);

        return new ApiException($status, $error, $errors, $exception);
    }

    /**
     * Get error description based on status code.
     *
     * @param int $status
     *
     * @return string
     */
    protected function getDescriptionForStatus($status)
    {
        switch ($status) {
            case 401:
                return trans('honeycomb::errors.authentication');

            case 404:
                return trans('honeycomb::errors.not_found');

            default:
                return trans('honeycomb::errors.generic');
        }
    }

    /**
     * Get the best exception wrapper. Returns the function name.
     *
     * @param \Throwable|\Exception $exception
     * @param array $exceptions
     *
     * @return string
     */
    private function findBestMatch($exception, $exceptions)
    {
        // if the exact exception class is specified, return it
        if (array_key_exists(get_class($exception), $exceptions)) {
            return $exceptions[get_class($exception)];
        }

        // find the best match based on inheritance level
        $bestMatch = null;
        $bestLevel = PHP_INT_MAX;

        foreach ($exceptions as $class => $function) {
            if (is_a($exception, $class, true)) {
                $exceptionClass = get_class($exception);
                $level = 0;

                // find the inheritance level
                while ($exceptionClass !== $class) {
                    $exceptionClass = get_parent_class($exceptionClass);
                    $level++;
                }

                // save new best match
                if ($level < $bestLevel) {
                    $bestMatch = $function;
                }
            }
        }

        // if no match is found, fallback to wrapException
        return $bestMatch ?: 'wrapException';
    }

}
