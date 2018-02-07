<?php

namespace Honeycomb\Support;

use Honeycomb\ApiException;
use Honeycomb\Feedback;

class ApiExceptionWrapperL52 extends BaseApiExceptionWrapper
{

    /**
     * An array of supported exceptions.
     * Use the fully qualified class name as the key and the function name as value. Eg:
     *
     *   \Fully\Qualified\Class\Name\CustomException::class => 'wrapCustomException',
     *
     * @var array
     */
    protected $exceptions = [
        \Illuminate\Auth\AuthenticationException::class => 'wrapAuthenticationException',
        \Illuminate\Validation\ValidationException::class => 'wrapValidationException',
    ];

    /**
     * Wrap given AuthenticationException in an ApiException.
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
     * Wrap given ValidationException in an ApiException.
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

}
