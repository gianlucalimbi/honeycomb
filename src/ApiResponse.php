<?php

namespace Honeycomb;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\ResponseTrait;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use JsonSerializable;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse extends Response implements Arrayable, Jsonable, JsonSerializable
{

    use ResponseTrait;

    /**
     * Values that cannot be used for the $name argument.
     *
     * @var array
     */
    const RESERVED_NAMES = [ 'status', 'feedback', 'metadata', 'error', 'errors' ];

    /**
     * Name used for data in the JSON output.
     *
     * @var string
     */
    private $name = 'data';

    /**
     * Response data.
     *
     * @var mixed
     */
    private $data = null;

    /**
     * Feedback or message list.
     *
     * @var array|null
     */
    private $feedback = null;

    /**
     * Response metadata.
     *
     * @var array
     */
    private $metadata = [];

    /**
     * Indicates whether the pagination is enabled.
     *
     * @var boolean
     */
    private $paginated = false;

    /**
     * API exception data.
     *
     * @var ApiException|null
     */
    private $apiException = null;

    /**
     * Options used for JSON serialization/deserialization.
     *
     * @var int
     */
    private $jsonOptions = 0;

    /**
     * ApiResponse constructor.
     *
     * @param int $status
     * @param array $headers
     */
    public function __construct($status = 200, $headers = [])
    {
        parent::__construct('', $status, $headers);
    }

    /**
     * Creates a new success ApiResponse.
     *
     * @param integer $status
     * @param string $name
     * @param mixed $data
     * @param array|null $feedback
     * @param array $metadata
     * @param array $headers
     *
     * @return self
     */
    public static function success($status, $name, $data, $feedback = null, $metadata = [], $headers = [])
    {
        $response = new self($status, $headers);

        $response->setName($name);
        $response->setData($data);
        $response->setFeedback($feedback);
        $response->setMetadata($metadata);

        return $response->update();
    }

    /**
     * Creates a new failure ApiResponse.
     *
     * @param ApiException $apiException
     * @param array $headers
     *
     * @return $this
     */
    public static function failure($apiException, $headers = [])
    {
        $response = new self(200, $headers); // 200 to prevent 'invalid status', will be re-set in setApiException

        $response->setApiException($apiException);

        return $response->update();
    }

    /**
     * @param int $status
     * @param string $text
     *
     * @return $this
     */
    public function setStatusCode($status, $text = null)
    {
        parent::setStatusCode($status, $text);

        if ($this->isSuccessful() !== $this->isSuccess()) {
            throw new InvalidArgumentException(sprintf('invalid status %1$s fos %2$s response',
                $status, $this->isSuccess() ? 'success' : 'failure'));
        }

        return $this->update();
    }

    /* Success */

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        if (empty($name)) {
            throw new InvalidArgumentException('name cannot be empty');
        }

        if (in_array($name, self::RESERVED_NAMES)) {
            throw new InvalidArgumentException(sprintf('%s is a reserved name', $name));
        }

        $this->name = (string) $name;

        return $this->update();
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        if ($this->isList()) {
            return $this->getList();
        }

        return $this->data;
    }

    /**
     * @return array|Collection
     */
    public function getList()
    {
        if (!$this->isList()) {
            throw new InvalidArgumentException('data must be a list');
        }

        $page = $this->getPage();
        $perPage = $this->getPerPage();

        if ($this->data instanceof EloquentBuilder || $this->data instanceof QueryBuilder) {
            if ($this->isPaginated()) {
                $list = $this->data->forPage($page, $perPage)->get();
            } else {
                $list = $this->data->get();
            }
        } else {
            if ($this->isPaginated()) {
                $list = Collection::make($this->data)->forPage($page, $perPage);
            } else {
                $list = $this->data;
            }
        }

        return $list;
    }

    /**
     * @param mixed $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        $this->paginated = $this->isPaginated();

        return $this->update();
    }

    /**
     * @return array|null
     */
    public function getFeedback()
    {
        return $this->feedback;
    }

    /**
     * @param array|null $feedback
     *
     * @return $this
     */
    public function setFeedback($feedback)
    {
        $useFeedback = $this->useFeedback();

        // clean and check $feedback
        if (!empty($feedback)) {
            $feedback = (array) $feedback;

            if (!is_associative_array($feedback)) {
                throw new InvalidArgumentException('feedback must be an associative array');
            }

            foreach ($feedback as $key => &$values) {
                if (!is_sequential_array($values)) {
                    throw new InvalidArgumentException('feedback values must be sequential arrays');
                }

                if (empty($values)) {
                    unset($feedback[$key]);
                    continue;
                }

                foreach ($values as &$value) {
                    if ($useFeedback && !($value instanceof Feedback)) {
                        throw new InvalidArgumentException('feedback contents must be instances of Feedback');
                    } elseif (!$useFeedback) {
                        if ($value instanceof Feedback) {
                            $value = $value->getMessage();
                        }

                        $value = (string) $value;
                    }
                }
            }
        }

        $this->feedback = $feedback ?: null;

        return $this->update();
    }

    /* Success - Metadata */

    /**
     * @return array|object
     */
    public function getMetadata()
    {
        $metadata = array_merge($this->metadata, [
            'name' => $this->getName(),
        ]);

        if ($this->isPaginated()) {
            $metadata += [
                'count' => $this->getCount(),
                'page_count' => $this->getPageCount(),
                'page' => $this->getPage(),
                'per_page' => $this->getPerPage(),
            ];
        }

        return $metadata;
    }

    /**
     * @param array|object $metadata
     *
     * @return $this
     */
    public function setMetadata($metadata)
    {
        $this->metadata = (array) $metadata;

        return $this->update();
    }

    /* Success - Pagination */

    /**
     * @return boolean
     */
    public function isPaginated()
    {
        return $this->isList() && $this->paginated;
    }

    /**
     * @param boolean $paginated
     *
     * @return $this
     */
    public function setPaginated($paginated = true)
    {
        if ($paginated && !$this->isList()) {
            throw new InvalidArgumentException('pagination requires a list');
        }

        $this->paginated = (boolean) $paginated;

        return $this->update();
    }

    /**
     * Checks if data is a list.
     * E.g. an array or a Collection.
     *
     * @return boolean
     */
    public function isList()
    {
        return
            is_sequential_array($this->data) ||
            $this->data instanceof Collection ||
            $this->data instanceof EloquentBuilder ||
            $this->data instanceof QueryBuilder;
    }

    /**
     * Get the total items count in the list.
     *
     * @return int
     */
    public function getCount()
    {
        if (!$this->isPaginated()) {
            return null;
        }

        return $this->data instanceof EloquentBuilder || $this->data instanceof QueryBuilder
            ? $this->data->count()
            : sizeof($this->data);
    }

    /**
     * Get the total number of pages.
     *
     * @return int
     */
    public function getPageCount()
    {
        if (!$this->isPaginated()) {
            return null;
        }

        $count = $this->getCount();

        if ($count == 0) {
            return 1;
        }

        return (int) ceil($count / $this->getPerPage());
    }

    /**
     * Get the current requested page.
     *
     * @return int
     */
    public function getPage()
    {
        if (!$this->isPaginated()) {
            return null;
        }

        $page = (int) request()->get($this->transformFieldName('page'), 1);

        $pageCount = $this->getPageCount();

        if ($page <= 0 || $page > $pageCount) {
            abort_api(416, sprintf('invalid page argument, min:1 max:%d', $pageCount));
        }

        return $page;
    }

    /**
     * Get the number of items that should be shown per page.
     *
     * @return int
     */
    public function getPerPage()
    {
        if (!$this->isPaginated()) {
            return null;
        }

        $perPageMin = $this->getPerPageMin();
        $perPageMax = $this->getPerPageMax();
        $perPageDefault = $this->getPerPageDefault();

        $perPage = (int) request()->get($this->transformFieldName('per_page'), $perPageDefault);

        if ($perPage <= 0) {
            abort_api(416, sprintf('invalid per_page argument, min:%1$d max:%2$d',
                $perPageMin, $perPageMax));
        }

        if ($perPage > $perPageMax) {
            $perPage = $perPageMax;
        }

        if ($perPage < $perPageMin) {
            $perPage = $perPageMin;
        }

        return $perPage;
    }

    /* Failure */

    /**
     * @return ApiException|null
     */
    public function getApiException()
    {
        return $this->apiException;
    }

    /**
     * @param ApiException|null $apiException
     *
     * @return $this
     */
    public function setApiException($apiException)
    {
        if (is_null($apiException)) {
            $this->apiException = null;

            if (!$this->isSuccessful()) {
                $this->setStatusCode(200); // default value
            }

            return $this->update();
        }

        if (!($apiException instanceof ApiException)) {
            throw new InvalidArgumentException('apiException must be an instance of ApiException');
        }

        $this->apiException = $apiException;

        $this->setStatusCode($apiException->getStatus());

        return $this->update();
    }

    /* Global */

    /**
     * @return int
     */
    public function getJsonOptions()
    {
        return $this->jsonOptions;
    }

    /**
     * @param int $jsonOptions
     *
     * @return $this
     */
    public function setJsonOptions($jsonOptions)
    {
        $this->jsonOptions = (int) $jsonOptions;

        return $this->update();
    }

    /**
     * Updates the response content.
     *
     * @return $this
     */
    private function update()
    {
        $this->headers->set('Content-Type', 'application/json');

        return $this->setContent($this->toJson($this->getJsonOptions()));
    }

    /**
     * Is response successful?
     *
     * @return boolean
     */
    public function isSuccess()
    {
        return is_null($this->apiException);
    }

    /**
     * Convert the response instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        if ($this->isSuccess()) {
            $array = [
                'status' => $this->getStatusCode(),
                $this->getName() => $this->getData(),
                'feedback' => $this->getFeedback(),
                'metadata' => $this->getMetadata(),
            ];
        } else {
            $array = $this->apiException->toArray();
        }

        return $this->transformArrayIfNeeded($array);
    }

    /**
     * Convert the response instance to JSON.
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
     * Convert the response into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /* Config related */

    /**
     * Transform response array, based on config file.
     * It just supports 'camel_case' at the moment.
     *
     * @param array $array
     *
     * @return array
     */
    private function transformArrayIfNeeded($array)
    {
        $camelCase = config('honeycomb.camel_case');

        if ($camelCase) {
            return transform_array_keys($array, 'camel_case');
        }

        return $array;
    }

    /**
     * Get the transformed field name for given field, based on config file.
     * It just supports 'camel_case' at the moment.
     *
     * @param string $field
     *
     * @return string
     */
    private function transformFieldName($field)
    {
        $camelCase = config('honeycomb.camel_case');

        if ($camelCase) {
            $field = camel_case($field);
        }

        return $field;
    }

    /**
     * Returns whether Feedback should be used, based on config file.
     *
     * @return boolean
     */
    private function useFeedback()
    {
        return (boolean) config('honeycomb.use_feedback');
    }

    /**
     * Get the minimum allowed "per page" value, based on config file.
     *
     * @return int
     */
    private function getPerPageMin()
    {
        return max(1, (int) config('honeycomb.per_page_min'));
    }

    /**
     * Get the maximum allowed "per page" value, based on config file.
     *
     * @return int
     */
    private function getPerPageMax()
    {
        return max(1, (int) config('honeycomb.per_page_max'));
    }

    /**
     * Get the default "per page" value, based on config file.
     *
     * @return int
     */
    private function getPerPageDefault()
    {
        return max(1, (int) config('honeycomb.per_page_default'));
    }

}
