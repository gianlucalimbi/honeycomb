<?php

namespace Honeycomb\Support;

use Honeycomb\ApiException;
use Honeycomb\Contracts\ApiExceptionWrapper;
use Honeycomb\Feedback;
use Symfony\Component\HttpFoundation\Response;

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
            //
        ];

        $exceptions = array_merge($exceptions, $this->exceptions);

        $functionName = $this->findBestMatch($exception, $exceptions);

        return $this->$functionName($exception);
    }

    /**
     * Wrap an HttpException in an ApiException.
     *
     * @param \Symfony\Component\HttpKernel\Exception\HttpException $exception
     *
     * @return ApiException
     */
    protected function wrapHttpException($exception)
    {
        $status = $exception->getStatusCode();
        $errors = null;

        $errorMessage = strtolower(isset(Response::$statusTexts[$status]) ? Response::$statusTexts[$status] : 'error');
        $errorDescription = trans('honeycomb::errors.generic');

        $error = Feedback::error($errorMessage, $errorDescription);

        return new ApiException($status, $error, $errors, $exception);
    }

    /**
     * Wrap an NotFoundHttpException in an ApiException.
     *
     * @param \Symfony\Component\HttpKernel\Exception\NotFoundHttpException $exception
     *
     * @return ApiException
     */
    protected function wrapNotFoundHttpException($exception)
    {
        $status = 404;
        $errors = null;

        $errorMessage = 'not found';
        $errorDescription = trans('honeycomb::errors.not_found');

        $error = Feedback::error($errorMessage, $errorDescription);

        return new ApiException($status, $error, $errors, $exception);
    }

    /**
     * Wrap an HttpResponseException in an ApiException.
     *
     * @param \Illuminate\Http\Exception\HttpResponseException $exception
     *
     * @return ApiException
     */
    protected function wrapHttpResponseException($exception)
    {
        $status = $exception->getResponse()->getStatusCode();
        $errors = null;

        $errorMessage = strtolower(isset(Response::$statusTexts[$status]) ? Response::$statusTexts[$status] : 'error');
        $errorDescription = trans('honeycomb::errors.generic');

        $error = Feedback::error($errorMessage, $errorDescription);

        return new ApiException($status, $error, $errors, $exception);
    }

    /**
     * Wrap a ModelNotFoundException in an ApiException.
     *
     * @param \Illuminate\Database\Eloquent\ModelNotFoundException $exception
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
     * Wrap an AuthenticationException in an ApiException.
     *
     * @param \Illuminate\Auth\AuthenticationException $exception
     *
     * @return ApiException
     */
    protected function wrapAuthenticationException($exception)
    {
        $status = 401;
        $errors = null;

        $errorMessage = 'unauthorized';
        $errorDescription = trans('honeycomb::errors.authentication');

        $error = Feedback::error($errorMessage, $errorDescription);

        return new ApiException($status, $error, $errors, $exception);
    }

    /**
     * Wrap a ValidationException in an ApiException.
     *
     * @param \Illuminate\Validation\ValidationException $exception
     *
     * @return ApiException
     */
    protected function wrapValidationException($exception)
    {
        $status = 422;
        $errors = [];

        $errorMessage = 'validation failed';
        $errorDescription = trans('honeycomb::errors.validation');

        $messages = $exception->validator->getMessageBag()->toArray();
        foreach ($exception->validator->failed() as $field => $rules) {
            $errors[$field] = [];

            $i = 0;
            foreach ($rules as $rule => $params) {
                $ruleDescription = strtolower($rule);
                if (!empty($params)) {
                    $ruleDescription .= sprintf(':%s', implode(',', $params));
                }

                $descriptionMessage = sprintf('%1$s field %2$s rule failed', $field, $ruleDescription);
                $descriptionText = $messages[$field][$i];

                $errors[$field][] = Feedback::error($descriptionMessage, $descriptionText);
                $i++;
            }
        }

        $error = Feedback::error($errorMessage, $errorDescription);

        return new ApiException($status, $error, $errors, $exception);
    }

    /**
     * Wrap an Exception in an ApiException.
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
