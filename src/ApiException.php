<?php

namespace Honeycomb;

use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Class ApiException.
 *
 * Custom Exception that provides a status code, an error feedback and additional specific errors.
 * It can be converted to JSON, to be used as an API response.
 *
 * @package Honeycomb
 */
class ApiException extends Exception implements Arrayable, Jsonable, JsonSerializable
{

    /**
     * HTTP Status Code.
     *
     * @var int
     */
    private $status;

    /**
     * Error data.
     *
     * @var Feedback
     */
    private $error;

    /**
     * Additional errors data.
     *
     * @var array|null
     */
    private $errors = null;

    /**
     * ApiException constructor.
     *
     * @param int $status
     * @param Feedback $error
     * @param array|object|null $errors
     * @param Exception|null $previous
     */
    public function __construct($status, Feedback $error, $errors = null, Exception $previous = null)
    {
        $this->setStatus($status);
        $this->setError($error);
        $this->setErrors($errors);

        parent::__construct($error->getMessage(), 0, $previous);
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     *
     * @return $this
     */
    private function setStatus($status)
    {
        $status = (int) $status;

        if ($status < 400 || $status >= 600) {
            throw new InvalidArgumentException(sprintf('invalid error status %s', $status));
        }

        $this->status = $status;

        return $this;
    }

    /**
     * @return Feedback
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param Feedback $error
     *
     * @return $this
     */
    private function setError($error)
    {
        if (!($error instanceof Feedback)) {
            throw new InvalidArgumentException('error must be an instance of Feedback');
        }

        $this->error = $error;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param array|object|null $errors
     *
     * @return $this
     */
    private function setErrors($errors)
    {
        if (!empty($errors)) {
            $errors = (array) $errors;

            // clean and check $errors values
            foreach ($errors as $key => $values) {
                if (empty($values)) {
                    unset($errors[$key]);
                    continue;
                }

                foreach ($values as $value) {
                    if (!($value instanceof Feedback)) {
                        throw new InvalidArgumentException('errors values must be instances of Feedback');
                    }
                }
            }
        }

        $this->errors = $errors ?: null;

        return $this;
    }

    /**
     * Convert the exception instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'status' => $this->getStatus(),
            'error' => $this->getError(),
            'errors' => $this->getErrors(),
        ];
    }

    /**
     * Convert the exception instance to JSON.
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the exception into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Convert the exception to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

}
