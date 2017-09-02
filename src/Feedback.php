<?php

namespace Honeycomb;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Class Feedback.
 *
 * A Feedback contains an internal message and a user-friendly description, along with a type.
 *
 * @package Honeycomb
 */
class Feedback implements Arrayable, Jsonable, JsonSerializable
{

    /**
     * Define Feedback types. Possible values are:
     * - success
     * - info
     * - warning
     * - error
     */
    const TYPES = [ self::TYPE_SUCCESS, self::TYPE_INFO, self::TYPE_WARNING, self::TYPE_ERROR ];

    const TYPE_SUCCESS = 'success';
    const TYPE_INFO = 'info';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR = 'error';

    /**
     * The type of the feedback. Must be one of:
     * success, info, warning, error
     *
     * @var string
     */
    private $type;

    /**
     * Internal message to describe the feedback.
     *
     * @var string
     */
    private $message;

    /**
     * User-friendly description of the feedback, ready to be shown to the user.
     *
     * @var string
     */
    private $description;

    /**
     * Feedback constructor.
     *
     * @param string $type
     * @param string $message
     * @param string $description
     */
    public function __construct($type, $message, $description)
    {
        $this->setType($type);
        $this->setMessage($message);
        $this->setDescription($description);
    }

    /**
     * Shorthand to create a success Feedback.
     *
     * @param string $message
     * @param string $description
     *
     * @return self
     */
    public static function success($message, $description)
    {
        return new self(self::TYPE_SUCCESS, $message, $description);
    }

    /**
     * Shorthand to create an info Feedback.
     *
     * @param string $message
     * @param string $description
     *
     * @return self
     */
    public static function info($message, $description)
    {
        return new self(self::TYPE_INFO, $message, $description);
    }

    /**
     * Shorthand to create a warning Feedback.
     *
     * @param string $message
     * @param string $description
     *
     * @return self
     */
    public static function warning($message, $description)
    {
        return new self(self::TYPE_WARNING, $message, $description);
    }

    /**
     * Shorthand to create an error Feedback.
     *
     * @param string $message
     * @param string $description
     *
     * @return self
     */
    public static function error($message, $description)
    {
        return new self(self::TYPE_ERROR, $message, $description);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    private function setType($type)
    {
        if (!in_array($type, self::TYPES)) {
            throw new InvalidArgumentException(sprintf('type must be one of %s', implode(', ', self::TYPES)));
        }

        $this->type = (string) $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return $this
     */
    private function setMessage($message)
    {
        if (empty($message)) {
            throw new InvalidArgumentException('message cannot be empty');
        }

        $this->message = (string) $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    private function setDescription($description)
    {
        if (empty($description)) {
            throw new InvalidArgumentException('description cannot be empty');
        }

        $this->description = (string) $description;

        return $this;
    }

    /**
     * Convert the object to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'type' => $this->getType(),
            'message' => $this->getMessage(),
            'description' => $this->getDescription(),
        ];
    }

    /**
     * Convert the object to JSON.
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
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Convert the object to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

}
