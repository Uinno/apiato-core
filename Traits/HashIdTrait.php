<?php

declare(strict_types=1);

namespace Apiato\Core\Traits;

use Apiato\Core\Exceptions\IncorrectIdException;
use Illuminate\Support\Facades\Config;
use Vinkla\Hashids\Facades\Hashids;

trait HashIdTrait
{
    /**
     * Endpoint to be skipped from decoding their ID's (example for external ID's).
     */
    private array $skippedEndpoints = [
        // 'orders/{id}/external',
    ];

    /**
     * Hashes the value of a field (e.g., ID).
     * Will be used by the Eloquent Models (since it's used as trait there).
     *
     * @param string|null $field The field of the model to be hashed
     *
     * @psalm-return mixed
     */
    public function getHashedKey(?string $field = null)
    {
        // If no key is set, use the default key name (i.e., id)
        if ($field === null) {
            $field = $this->getKeyName();
        }

        // We need to get the VALUE for this KEY (model field)
        $value = $this->getAttribute($field);

        // Hash the ID only if hash-id enabled in the config
        if (Config::get('apiato.hash-id')) {
            return $this->encoder($value);
        }

        return $value;
    }

    /**
     * @param int $id
     */
    public function encoder($id): string
    {
        return Hashids::encode($id);
    }

    public function decodeArray(array $ids): array
    {
        $result = [];
        foreach ($ids as $id) {
            $result[] = $this->decode($id);
        }

        return $result;
    }

    public function decode(?string $id): ?int
    {
        // Check if passed as null, (could be an optional decodable variable)
        if (\is_null($id) || strtolower($id) === 'null') {
            return null;
        }

        $value = $this->decoder($id);

        if (empty($value)) {
            return null;
        }

        // Do the decoding if the ID looks like a hashed one
        return (int)$value[0];
    }

    /**
     * @param string $id
     */
    private function decoder($id): array
    {
        return Hashids::decode($id);
    }

    /**
     * @param int $id
     */
    public function encode($id): string
    {
        return $this->encoder($id);
    }

    /**
     * Without decoding the encoded ID's you won't be able to use
     * validation features like `exists:table,id`.
     *
     * @throws IncorrectIdException
     */
    protected function decodeHashedIdsBeforeValidation(array $requestData): array
    {
        // The hash ID feature must be enabled to use this decoder feature.
        if (property_exists($this, 'decode') && !empty($this->decode) && Config::get('apiato.hash-id')) {
            // Iterate over each key (ID that needs to be decoded) and call keys locator to decode them
            foreach ($this->decode as $key) {
                $requestData = $this->locateAndDecodeIds($requestData, $key);
            }
        }

        return $requestData;
    }

    /**
     * Search the IDs to be decoded in the request data.
     *
     * @throws IncorrectIdException
     */
    private function locateAndDecodeIds(array $requestData, string $key): array
    {
        // Split the key based on the "."
        $fields = explode('.', $key);
        // Loop through all elements of the key.
        return (array)($this->processField($requestData, $fields, $key));
    }

    /**
     * Recursive function to process (decode) the request data with a given key.
     *
     * @return array|string|int|null
     *
     * @throws IncorrectIdException
     */
    private function processField(array|string|int|null $data, ?array $keysTodo = null, ?string $currentFieldName = null)
    {
        // Check if there are no more fields to be processed.
        if (empty($keysTodo)) {
            if ($this->skipHashIdDecode($data)) {
                return $data;
            }

            if (!\is_string($data)) {
                throw new IncorrectIdException(sprintf('ID (%s) is incorrect.', $currentFieldName));
            }

            // There are no more keys left - so basically we need to decode this entry.
            $decodedField = $this->decode($data);

            if ($decodedField === null) {
                throw new IncorrectIdException(sprintf('ID (%s) is incorrect.', $currentFieldName));
            }

            return $decodedField;
        }

        // Take the first element from the field
        $field = array_shift($keysTodo);

        // Is the current field an array?! we need to process it like crazy
        if ($field === '*') {
            // Make sure field value is an array
            $data = \is_array($data) ? $data : [$data];

            // Process each field of the array (and go down one level!)
            $fields = $data;
            foreach ($fields as $key => $value) {
                $data[$key] = $this->processField($value, $keysTodo, sprintf('%s[%s]', $currentFieldName, $key));
            }

            return $data;
        }

        // Check if the key we are looking for does, in fact, really exist
        if (!\is_array($data) || !\array_key_exists($field, $data)) {
            return $data;
        }

        // Go down one level
        $value        = $data[$field];
        $data[$field] = $this->processField($value, $keysTodo, $field);

        return $data;
    }

    public function skipHashIdDecode(array|string|int|null $field): bool
    {
        return $field === null;
    }
}
