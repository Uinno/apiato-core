<?php

declare(strict_types=1);

namespace Apiato\Core\Traits;

use Apiato\Core\Abstracts\Requests\Request;
use Apiato\Core\Abstracts\Transporters\Transporter;
use Apiato\Core\Exceptions\CoreInternalErrorException;
use Apiato\Core\Exceptions\IncorrectIdException;
use Illuminate\Support\Arr;

trait SanitizerTrait
{
    /**
     * Sanitizes the data of a request. This is a superior version of php built-in array_filter() as it preserves
     * FALSE and NULL values as well.
     *
     * @param array $fields a list of fields to be checked in the Dot-Notation (e.g., ['data.name', 'data.description'])
     *
     * @return array an array containing the values if the field was present in the request and the intersection array
     *
     * @throws IncorrectIdException|CoreInternalErrorException
     */
    public function sanitizeInput(array $fields): array
    {
        $data = $this->getData();

        $inputAsArray           = [];
        $fieldsWithDefaultValue = [];

        // Create a multidimensional array based on $fields
        // Which was submitted as DOT notation (e.g., data.name)
        foreach ($fields as $key => $value) {
            if (\is_string($key)) {
                // Save fields with default values
                $fieldsWithDefaultValue[$key] = $value;
                Arr::set($inputAsArray, $key, $value);
            } else {
                Arr::set($inputAsArray, $value, true);
            }
        }

        // Check, if the keys exist in both arrays
        $data = $this->recursiveArrayIntersectKey($data, $inputAsArray);

        // Set default values if key doesn't exist
        foreach ($fieldsWithDefaultValue as $key => $value) {
            $data = Arr::add($data, $key, $value);
        }

        return $data;
    }

    /**
     * Recursively intersects 2 arrays based on their keys.
     *
     * @param array $a first array (that keeps the values)
     * @param array $b second array to be compared with
     *
     * @return array an array containing all keys that are present in $a and $b. Only values from $a are returned
     */
    private function recursiveArrayIntersectKey(array $a, array $b): array
    {
        $a = array_intersect_key($a, $b);

        foreach ($a as $key => &$value) {
            if (\is_array($value) && \is_array($b[$key])) {
                $value = $this->recursiveArrayIntersectKey($value, $b[$key]);
            }
        }

        return $a;
    }

    /**
     * @throws CoreInternalErrorException|IncorrectIdException
     */
    private function getData(): array
    {
        // Get all request data.
        if ($this instanceof Transporter) {
            $data = $this->toArray();
        } elseif ($this instanceof Request) {
            $data = $this->all();
        } else {
            throw new CoreInternalErrorException('Unsupported class type for sanitization.');
        }

        return $data;
    }
}
