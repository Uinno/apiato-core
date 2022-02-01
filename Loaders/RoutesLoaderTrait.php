<?php

declare(strict_types=1);

namespace Apiato\Core\Loaders;

use Apiato\Core\Foundation\Facades\Apiato;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Finder\SplFileInfo;

trait RoutesLoaderTrait
{
    protected string $uiApi = 'API';

    protected string $uiWeb = 'WEB';

    /**
     * Register all the containers routes files in the framework.
     */
    public function runRoutesAutoLoader(): void
    {
        $containersPaths = Apiato::getAllContainerPaths();

        foreach ($containersPaths as $containerPath) {
            $this->loadContainerRoutesByUI($containerPath, $this->uiApi);
            $this->loadContainerRoutesByUI($containerPath, $this->uiWeb);
        }
    }

    public function getApiRouteGroup(string | SplFileInfo $endpointFileOrPrefixString, ?string $controllerNamespace = null): array
    {
        return [
            'namespace'  => $controllerNamespace,
            'middleware' => $this->getMiddlewares(),
            'domain'     => $this->getApiDomain(),
            // If $endpointFileOrPrefixString is a file then get the version name from the file name, else if string use that string as prefix
            'prefix'     => \is_string($endpointFileOrPrefixString) ? $endpointFileOrPrefixString : $this->getApiVersionPrefix($endpointFileOrPrefixString),
        ];
    }

    public function getWebRouteGroup(SplFileInfo $file, ?string $controllerNamespace = null): array
    {
        return [
            'namespace'  => $controllerNamespace,
            'middleware' => ['web'],
        ];
    }

    /**
     * Register the Containers UI routes files.
     */
    private function loadContainerRoutesByUI(string $containerPath, string $ui): void
    {
        // Build the container api routes path
        $uiRoutesPath = sprintf('%s/UI/%s/Routes', $containerPath, $ui);
        // Build the namespace from the path
        $controllerNamespace = sprintf('%s/UI/%s/Controllers', $containerPath, $ui);

        if (File::isDirectory($uiRoutesPath)) {
            $files = File::allFiles($uiRoutesPath);
            $files = Arr::sort($files, static fn (SplFileInfo $file): string => $file->getFilename());

            foreach ($files as $file) {
                $routeGroupArray = match ($ui) {
                    $this->uiApi => $this->getApiRouteGroup($file, $controllerNamespace),
                    $this->uiWeb => $this->getWebRouteGroup($file, $controllerNamespace),
                };

                $this->createRouteGroup($file, $routeGroupArray);
            }
        }
    }

    /**
     * @return string[]
     */
    private function getMiddlewares(): array
    {
        return array_filter([
            'api',
            $this->getRateLimitMiddleware(), // Returns NULL if feature disabled. Null will be removed form the array.
        ]);
    }

    private function getRateLimitMiddleware(): ?string
    {
        $rateLimitMiddleware = null;

        if (Config::get('apiato.api.throttle.enabled')) {
            $rateLimitMiddleware = 'throttle:' . Config::get('apiato.api.throttle.attempts') . ',' . Config::get('apiato.api.throttle.expires');
        }

        return $rateLimitMiddleware;
    }

    private function getApiDomain(): string
    {
        return parse_url($this->getApiUrl(), PHP_URL_HOST);
    }

    private function getApiUrl(): string
    {
        return Config::get('apiato.api.url');
    }

    private function getApiVersionPrefix(SplFileInfo $file): string
    {
        return Apiato::getApiPrefix() . (Config::get('apiato.api.enable_version_prefix') ? $this->getRouteFileVersionFromFileName($file) : '');
    }

    private function getRouteFileVersionFromFileName(SplFileInfo $file): string
    {
        $fileNameWithoutExtension = $this->getRouteFileNameWithoutExtension($file);

        $fileNameWithoutExtensionExploded = explode('.', $fileNameWithoutExtension);

        end($fileNameWithoutExtensionExploded);

        $apiVersion = prev($fileNameWithoutExtensionExploded); // get the array before the last one

        // Skip versioning the API's root route
        if ($apiVersion === 'ApisRoot') {
            $apiVersion = '';
        }

        return (string)$apiVersion;
    }

    private function getRouteFileNameWithoutExtension(SplFileInfo $file): string
    {
        $fileInfo = pathinfo($file->getFileName());

        return $fileInfo['filename'];
    }

    private function createRouteGroup(SplFileInfo $file, array $routeGroupArray): void
    {
        Route::group($routeGroupArray, function (Router $router) use ($file): void {
            /** @psalm-suppress UnresolvableInclude dynamic include, psalm cant resolve it */
            require $file->getPathname();
        });
    }
}
