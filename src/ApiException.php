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
 * Custom Exception that provides a status code, an error feedback and additional error description.
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
     * Additional error data.
     *
     * @var array|null
     */
    private $errorDescription = null;

    /**
     * ApiException constructor.
     *
     * @param int $status
     * @param Feedback $error
     * @param array|object|null $errorDescription
     * @param Exception|null $previous
     */
    public function __construct($status, Feedback $error, $errorDescription = null, Exception $previous = null)
    {
        $this->setStatus($status);
        $this->setError($error);
        $this->setErrorDescription($errorDescription);

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
    public function getErrorDescription()
    {
        return $this->errorDescription;
    }

    /**
     * @param array|object|null $errorDescription
     *
     * @return $this
     */
    private function setErrorDescription($errorDescription)
    {
        if (!empty($errorDescription)) {
            $errorDescription = (array) $errorDescription;

            // clean and check $errorDescription values
            foreach ($errorDescription as $key => $values) {
                if (empty($values)) {
                    unset($errorDescription[$key]);
                    continue;
                }

                foreach ($values as $value) {
                    if (!($value instanceof Feedback)) {
                        throw new InvalidArgumentException('error_description values must be instances of Feedback');
                    }
                }
            }
        }

        $this->errorDescription = $errorDescription ?: null;

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
            'error_description' => $this->getErrorDescription(),
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
