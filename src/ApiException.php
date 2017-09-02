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
     * @var Feedback|string
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
     * @param Feedback|string $error
     * @param array|object|null $errors
     * @param Exception|\Throwable|null $previous
     */
    public function __construct($status, $error, $errors = null, $previous = null)
    {
        $this->setStatus($status);
        $this->setError($error);
        $this->setErrors($errors);

        parent::__construct($error instanceof Feedback ? $error->getMessage() : (string) $error, 0, $previous);
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
        $useFeedback = $this->useFeedback();

        if (empty($error)) {
            throw new InvalidArgumentException('error cannot be empty');
        }

        if ($useFeedback && !($error instanceof Feedback)) {
            throw new InvalidArgumentException('error must be an instance of Feedback');
        } elseif (!$useFeedback) {
            // use Feedback's message
            if ($error instanceof Feedback) {
                $error = $error->getMessage();
            }

            $error = (string) $error;
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
        $useFeedback = $this->useFeedback();

        // clean and check $errors
        if (!empty($errors)) {
            $errors = (array) $errors;

            if (!is_associative_array($errors)) {
                throw new InvalidArgumentException('errors must be an associative array');
            }

            foreach ($errors as $key => &$values) {
                if (!is_sequential_array($values)) {
                    throw new InvalidArgumentException('errors values must be sequential arrays');
                }

                if (empty($values)) {
                    unset($errors[$key]);
                    continue;
                }

                foreach ($values as &$value) {
                    if ($useFeedback && !($value instanceof Feedback)) {
                        throw new InvalidArgumentException('errors contents must be instances of Feedback');
                    } elseif (!$useFeedback) {
                        // use Feedback's message
                        if ($value instanceof Feedback) {
                            $value = $value->getMessage();
                        }

                        $value = (string) $value;
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

    /* Config */

    /**
     * Returns whether Feedback should be used, based on config file.
     *
     * @return boolean
     */
    private function useFeedback()
    {
        return (boolean) config('honeycomb.use_feedback');
    }

}
