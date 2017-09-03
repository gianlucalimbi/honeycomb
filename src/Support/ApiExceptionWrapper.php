<?php

namespace Honeycomb\Support;

use Honeycomb\ApiException;
use Honeycomb\Contracts\ApiExceptionWrapper as BaseApiExceptionWrapper;
use Honeycomb\Feedback;
use Symfony\Component\HttpFoundation\Response;

class ApiExceptionWrapper implements BaseApiExceptionWrapper
{

    /**
     * Wrap an Exception in an ApiException.
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

        $functionName = 'wrap' . class_basename($exception);
        if (method_exists($this, $functionName)) {
            return $this->$functionName($exception);
        }

        return $this->wrapException($exception);
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

}
