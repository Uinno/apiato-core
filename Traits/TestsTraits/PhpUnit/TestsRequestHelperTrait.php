<?php

declare(strict_types=1);

namespace Apiato\Core\Traits\TestsTraits\PhpUnit;

use Apiato\Core\Exceptions\MissingTestEndpointException;
use Apiato\Core\Exceptions\UndefinedMethodException;
use Apiato\Core\Exceptions\WrongEndpointFormatException;
use Apiato\Core\Foundation\Facades\Apiato;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use JsonException;
use stdClass;
use Vinkla\Hashids\Facades\Hashids;

trait TestsRequestHelperTrait
{
    /**
     * property to be set on the user test class.
     */
    protected string $endpoint = '';

    /**
     * property to be set on the user test class.
     */
    protected bool $auth = true;

    protected TestResponse $response;

    protected string $responseContent;

    protected ?array $responseContentArray = null;

    protected ?stdClass $responseContentObject = null;

    /**
     * Allows users to override the default class property `endpoint` directly before calling the `makeCall` function.
     */
    protected ?string $overrideEndpoint = null;

    /**
     * Allows users to override the default class property `auth` directly before calling the `makeCall` function.
     */
    protected ?bool $overrideAuth = null;

    /**
     * @throws WrongEndpointFormatException
     * @throws MissingTestEndpointException
     * @throws UndefinedMethodException
     */
    public function makeCall(array $data = [], array $headers = []): TestResponse
    {
        // Get or create a testing user. It will get your existing user if you already called this function from your
        // test. Or create one if you never called this function from your tests "Only if the endpoint is protected".
        $this->getTestingUser();

        // Read the $parseEndpoint property from the test and set the verb and the uri as properties on this trait
        $parseEndpoint = $this->parseEndpoint();
        $verb          = $parseEndpoint['verb'];
        $url           = $parseEndpoint['url'];

        // Validating user http verb input + converting `get` data to query parameter.
        switch ($verb) {
            case 'get':
                $url = $this->dataArrayToQueryParam($data, $url);
                break;
            case 'post':
            case 'put':
            case 'patch':
            case 'delete':
                break;
            default:
                throw new UndefinedMethodException('Unsupported HTTP Verb (' . $verb . ')!');
        }

        $httpResponse = $this->json($verb, $url, $data, $this->injectAccessToken($headers));

        return $this->setResponseObjectAndContent($httpResponse);
    }

    /**
     * @throws WrongEndpointFormatException
     * @throws MissingTestEndpointException
     * @throws UndefinedMethodException
     */
    public function makeUploadCall(array $files = [], array $params = [], array $headers = []): TestResponse
    {
        // Get or create a testing user. It will get your existing user if you already called this function from your
        // test. Or create one if you never called this function from your tests "Only if the endpoint is protected".
        $this->getTestingUser();

        // Read the $parseEndpoint property from the test and set the verb and the uri as properties on this trait
        $parseEndpoint = $this->parseEndpoint();
        $verb          = $parseEndpoint['verb'];
        $url           = $parseEndpoint['url'];

        // Validating user http verb input + converting `get` data to query parameter
        if ($verb !== 'post') {
            throw new UndefinedMethodException('Unsupported HTTP Verb (' . $verb . ')!');
        }

        $headers = array_merge([
            'Accept' => 'application/json',
        ], $headers);

        $server  = $this->transformHeadersToServerVars($this->injectAccessToken($headers));
        $cookies = $this->prepareCookiesForRequest();

        $httpResponse = $this->call($verb, $url, $params, $cookies, $files, $server);

        return $this->setResponseObjectAndContent($httpResponse);
    }

    public function setResponseObjectAndContent($httpResponse): TestResponse
    {
        $this->setResponseContent($httpResponse);

        return $this->response = $httpResponse;
    }

    /**
     * @throws JsonException
     */
    public function getResponseContentArray()
    {
        if ($this->responseContentArray) {
            return $this->responseContentArray;
        }

        if ($this->getResponseContent() === '') {
            return null;
        }

        return json_decode($this->getResponseContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function getResponseContent(): string
    {
        return $this->responseContent;
    }

    public function setResponseContent($httpResponse)
    {
        return $this->responseContent = $httpResponse->getContent();
    }

    /**
     * @throws JsonException
     */
    public function getResponseContentObject()
    {
        if ($this->responseContentObject) {
            return $this->responseContentObject;
        }

        if ($this->getResponseContent() === '') {
            return null;
        }

        return json_decode($this->getResponseContent(), false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Inject the ID in the Endpoint URI before making the call by
     * overriding the `$this->endpoint` property
     * Example: you give it ('users/{id}/stores', 100) it returns 'users/100/stores'.
     */
    public function injectId(string|int|array $id, bool $skipEncoding = false, string|array $replace = '{id}'): static
    {
        // In case Hash ID is enabled it will encode the ID first
        $ids = [];
        foreach ((array)$id as $value) {
            $ids[] = $this->hashEndpointId($value, $skipEncoding);
        }

        $injectedEndpoint = str_replace((array)$replace, $ids, $this->getEndpoint());

        return $this->endpoint($injectedEndpoint);
    }

    /**
     * Override the default class endpoint property before making the call
     * to be used as follows: $this->endpoint('verb@uri')->makeCall($data).
     */
    public function endpoint(?string $endpoint): static
    {
        $this->overrideEndpoint = $endpoint;

        return $this;
    }

    public function getEndpoint(): string
    {
        return \is_null($this->overrideEndpoint) ? $this->endpoint : $this->overrideEndpoint;
    }

    /**
     * Override the default class auth property before making the call
     * to be used as follows: $this->auth('false')->makeCall($data);
     */
    public function auth(bool $auth): static
    {
        $this->overrideAuth = $auth;

        return $this;
    }

    public function getAuth(): bool
    {
        return $this->overrideAuth ?? $this->auth;
    }

    /**
     * Change to public we need because sometimes some web routes need to test by makeCall and all web routes has different url.
     */
    public function buildUrlForUri($uri): string
    {
        return Config::get('apiato.api.url') . Apiato::getApiPrefix() . ltrim($uri, '/');
    }

    /**
     * Transform headers array to array of $_SERVER vars with HTTP_* format.
     */
    protected function transformHeadersToServerVars(array $headers): array
    {
        return collect($headers)->mapWithKeys(function ($value, $name): array {
            $name = str_replace('-', '_', strtoupper($name));

            return [$this->formatServerHeaderKey($name) => $value];
        })->all();
    }

    /**
     * Attach Authorization Bearer Token to the request headers
     * if it does not exist already and the authentication is required
     * for the endpoint `$this->auth = true`.
     */
    private function injectAccessToken(array $headers = []): array
    {
        // If endpoint is protected (requires token to access its functionality)
        if ($this->getAuth() && !$this->headersContainAuthorization($headers)) {
            // create token
            $accessToken = $this->getTestingUser()->createToken('token')->accessToken;
            // give it to user
            $this->getTestingUser()->withAccessToken($accessToken);
            // append the token to the header
            $headers['Authorization'] = 'Bearer ' . $accessToken;
        }

        return $headers;
    }

    private function headersContainAuthorization($headers): bool
    {
        return Arr::has($headers, 'Authorization');
    }

    private function dataArrayToQueryParam($data, $url): string
    {
        return $data ? $url . '?' . http_build_query($data) : $url;
    }

    private function hashEndpointId($id, bool $skipEncoding = false): string
    {
        return (Config::get('apiato.hash-id') && !$skipEncoding) ? Hashids::encode($id) : $id;
    }

    /**
     * @deprecated it's not used anywhere
     */
    private function getJsonVerb($text): string
    {
        return Str::replaceFirst('json:', '', $text);
    }

    /**
     * Read `$this->endpoint` property from the test class (`verb@uri`) and convert it to usable data.
     *
     * @throws WrongEndpointFormatException
     * @throws MissingTestEndpointException
     */
    private function parseEndpoint(): array
    {
        $this->validateEndpointExist();

        $separator = '@';

        $this->validateEndpointFormat($separator);

        // Convert the string to array
        $asArray = explode($separator, $this->getEndpoint(), 2);

        // Get the verb and uri values from the array
        [$verb, $uri] = $asArray;

        /** @var string $verb */
        /** @var string $uri */
        return [
            'verb' => $verb,
            'uri'  => $uri,
            'url'  => $this->buildUrlForUri($uri),
        ];
    }

    /**
     * @throws MissingTestEndpointException
     */
    private function validateEndpointExist(): void
    {
        if (!$this->getEndpoint()) {
            throw new MissingTestEndpointException();
        }
    }

    /**
     * @throws WrongEndpointFormatException
     */
    private function validateEndpointFormat(string $separator): void
    {
        // Check if string contains the separator
        if (!strpos($this->getEndpoint(), $separator)) {
            throw new WrongEndpointFormatException();
        }
    }
}
