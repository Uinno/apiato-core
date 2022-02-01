<?php

declare(strict_types=1);

namespace Apiato\Core\Abstracts\Transporters;

use Apiato\Core\Traits\SanitizerTrait;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Spatie\DataTransferObject\Arr;
use Spatie\DataTransferObject\Attributes\MapTo;
use Spatie\DataTransferObject\DataTransferObject as Dto;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;
use Spatie\DataTransferObject\Exceptions\ValidationException;
use Spatie\DataTransferObject\Reflection\DataTransferObjectClass;

abstract class Transporter extends Dto
{
    use SanitizerTrait;

    /**
     * Override the Dto constructor to extend it for supporting Requests objects as $input.
     * Transporter constructor.
     *
     * @noinspection PhpMissingParentConstructorInspection
     *
     * @throws UnknownProperties|ValidationException|ReflectionException
     */
    public function __construct(...$params)
    {
        /**
         * This way of parse arguments we take from \Spatie\DataTransferObject\DataTransferObject constructor.
         */
        $payload = $params[1] ?? null;

        if (\is_array($params[0] ?? null)) {
            $params = $params[0];
        }

        // Added for additional data
        if (\is_array($payload)) {
            $params = array_merge($params, $payload);
        }

        /** @var string[] $invalidTypes */
        $invalidTypes = [];
        $class        = new DataTransferObjectClass($this);

        foreach ($class->getProperties() as $property) {
            $fieldName          = $property->name;
            $reflectionProperty = new ReflectionProperty($this, $property->name);

            /**
             * This step check is our $property has default value in final transporter or can be null
             * if no, and in input params we don`t have value for this $param throw an Error.
             */
            if (
                !isset($params[$fieldName])
                && !$reflectionProperty->hasDefaultValue()
                && !$reflectionProperty->getType()?->allowsNull()
            ) {
                $invalidTypes[$fieldName][] = new Exception("Doesn't have default value");
                continue;
            }

            /**
             * This step check Transporter Strict type.
             * If Transporter Strict we must set all values that can not be null.
             * For miss empty initialized params with null values we just skip their initialization.
             */
            if (
                !\array_key_exists($fieldName, $params)
                && $reflectionProperty->getType()?->allowsNull()
                && !$class->isStrict()
            ) {
                continue;
            }

            $property->setValue(Arr::get($params, $property->name) ?? $this->{$property->name} ?? null);

            $params = Arr::forget($params, $property->name);
        }

        if ($class->isStrict() && \count($params)) {
            throw UnknownProperties::new(static::class, array_keys($params));
        }

        if (!empty($invalidTypes)) {
            throw new ValidationException($this, $invalidTypes);
        }

        $class->validate();
    }

    public function all(): array
    {
        $data = [];

        $class = new ReflectionClass(static::class);

        $properties = array_filter(
            $class->getProperties(ReflectionProperty::IS_PUBLIC),
            fn ($property): bool => $property->isInitialized($this)
        );

        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $mapToAttribute = $property->getAttributes(MapTo::class);
            $name           = $mapToAttribute !== [] ? $mapToAttribute[0]->newInstance()->name : $property->getName();

            $data[$name] = $property->getValue($this);
        }

        return $data;
    }

    /**
     * This method mimics the $request->input() method but works on the "decoded" values.
     */
    public function getInputByKey(?string $key = null, mixed $default = null): mixed
    {
        return Arr::get($this->toArray(), $key, $default);
    }
}
