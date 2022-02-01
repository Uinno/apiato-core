<?php

declare(strict_types=1);

namespace Apiato\Core\Traits;

use Apiato\Core\Abstracts\Transformers\Transformer;
use Apiato\Core\Exceptions\InvalidTransformerException;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;
use ReflectionClass;
use ReflectionException;
use Spatie\Fractal\Facades\Fractal;

trait ResponseTrait
{
    protected array $metaData = [];

    /**
     * @param Transformer|mixed $transformerName The transformer (e.g., Transformer::class or new Transformer()) to be applied
     * @param array             $includes        additional resources to be included
     * @param array             $meta            additional meta information to be applied
     * @param string            $resourceKey     the resource key to be set for the TOP LEVEL resource
     *
     * @throws InvalidTransformerException
     */
    public function transform(
        $data,
        $transformerName = null,
        array $includes = [],
        array $meta = [],
        $resourceKey = null
    ): array {
        /**
         * First, we need to create the transformer and check,
         * if we have provided a respective Transformer class,
         * or we just passed the classname.
         */
        $transformer = $transformerName instanceof Transformer ? $transformerName : new $transformerName();

        // now, finally check, if the class is really a TRANSFORMER
        if (!($transformer instanceof Transformer)) {
            throw new InvalidTransformerException();
        }

        // add specific meta information to the response message
        $this->metaData = [
            'include' => $transformer->getAvailableIncludes(),
            'custom'  => $meta,
        ];

        // No resource key was set
        if (!$resourceKey) {
            // Get the resource key from the model

            if ($data instanceof AbstractPaginator) {
                $obj = $data->getCollection()->first();
            } elseif ($data instanceof Collection) {
                $obj = $data->first();
            } else {
                $obj = $data;
            }

            // If we have an object, try to get its resourceKey
            if ($obj) {
                $resourceKey = $obj->getResourceKey();
            }
        }

        $fractal = Fractal::create($data, $transformer)->withResourceName($resourceKey)->addMeta($this->metaData);

        // Read includes passed via query params in url
        $requestIncludes = $this->parseRequestedIncludes();

        // Merge the requested includes with the one added by the transform() method itself
        $requestIncludes = array_unique(array_merge($includes, $requestIncludes));

        // And let fractal include everything
        $fractal->parseIncludes($requestIncludes);

        // Apply request filters if available in the request
        if (config('apiato.requests.allow-both-filter', false) && ($requestFilters = Request::get('filter'))) {
            $result = $this->filterResponse($fractal->toArray(), explode(';', $requestFilters));
        } else {
            $result = $fractal->toArray();
        }

        return $result;
    }

    public function withMeta($data): self
    {
        $this->metaData = $data;

        return $this;
    }

    /**
     * @param int $status
     * @param int $options
     */
    public function json($message, $status = 200, array $headers = [], $options = 0): JsonResponse
    {
        return new JsonResponse($message, $status, $headers, $options);
    }

    /**
     * @param int $status
     * @param int $options
     */
    public function created($message = null, $status = 201, array $headers = [], $options = 0): JsonResponse
    {
        return new JsonResponse($message, $status, $headers, $options);
    }

    /**
     * @throws ReflectionException
     */
    public function deleted($responseArray = null): JsonResponse
    {
        if (!$responseArray) {
            return $this->accepted();
        }

        $id        = $responseArray->getHashedKey();
        $className = (new ReflectionClass($responseArray))->getShortName();

        return $this->accepted([
            'message' => sprintf('%s (%s) Deleted Successfully.', $className, $id),
        ]);
    }

    /**
     * @param int $status
     * @param int $options
     */
    public function accepted($message = null, $status = 202, array $headers = [], $options = 0): JsonResponse
    {
        return new JsonResponse($message, $status, $headers, $options);
    }

    /**
     * @param int $status
     */
    public function noContent($status = 204): JsonResponse
    {
        return new JsonResponse(null, $status);
    }

    /**
     * @return string[]
     *
     * @psalm-return non-empty-list<string>
     */
    protected function parseRequestedIncludes(): array
    {
        return explode(',', Request::get('include', ''));
    }

    private function filterResponse(array $responseArray, array $filters): array
    {
        foreach ($responseArray as $k => $v) {
            if (\in_array($k, $filters, true)) {
                // We have found our element - so continue with the next one
                continue;
            }

            if (\is_array($v)) {
                // It is an array - so go one step deeper
                $v = $this->filterResponse($v, $filters);

                if (empty($v)) {
                    // It is an empty array - delete the key as well
                    unset($responseArray[$k]);
                } else {
                    $responseArray[$k] = $v;
                }

                // Check if the array is not in our filter-list
            } elseif (!\in_array($k, $filters, true)) {
                unset($responseArray[$k]);
            }
        }

        return $responseArray;
    }
}
