<?php

declare(strict_types=1);

namespace Apiato\Core\Abstracts\Transformers;

use Apiato\Core\Exceptions\CoreInternalErrorException;
use Apiato\Core\Exceptions\UnsupportedFractalIncludeException;
use ErrorException;
use Exception;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Primitive;
use League\Fractal\Resource\ResourceInterface;
use League\Fractal\Scope;
use League\Fractal\TransformerAbstract as FractalTransformer;

abstract class Transformer extends FractalTransformer
{
    /**
     * @psalm-param mixed                       $data
     * @psalm-param callable|FractalTransformer $transformer
     * @psalm-param null|string                 $resourceKey
     */
    public function nullableItem($data, $transformer, $resourceKey = null): Primitive|Item
    {
        if (is_null($data)) {
            return $this->primitive(null);
        }

        return $this->item($data, $transformer, $resourceKey = null);
    }

    /**
     * @psalm-param mixed                       $data
     * @psalm-param callable|FractalTransformer $transformer
     * @psalm-param null|string                 $resourceKey
     */
    public function item($data, $transformer, $resourceKey = null): Item
    {
        // Set a default resource key if none is set
        if (!$resourceKey && $data) {
            $resourceKey = $data->getResourceKey();
        }

        return parent::item($data, $transformer, $resourceKey);
    }

    /**
     * @psalm-param mixed                       $data
     * @psalm-param callable|FractalTransformer $transformer
     * @psalm-param null|string                 $resourceKey
     */
    public function collection($data, $transformer, $resourceKey = null): Collection
    {
        // Set a default resource key if none is set
        if (!$resourceKey && $data->isNotEmpty()) {
            $resourceKey = (string)$data->modelKeys()[0];
        }

        return parent::collection($data, $transformer, $resourceKey);
    }

    /**
     * @param string $includeName
     *
     * @throws CoreInternalErrorException
     * @throws UnsupportedFractalIncludeException
     */
    protected function callIncludeMethod(Scope $scope, $includeName, $data): ResourceInterface | bool
    {
        try {
            return parent::callIncludeMethod($scope, $includeName, $data);
        } catch (Exception $exception) {
            if (
                $exception instanceof ErrorException &&
                config('apiato.requests.force-valid-includes', true)
            ) {
                throw new UnsupportedFractalIncludeException($exception->getMessage());
            }

            throw new CoreInternalErrorException($exception->getMessage());
        }
    }
}
