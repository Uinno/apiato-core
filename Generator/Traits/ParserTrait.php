<?php

declare(strict_types=1);

namespace Apiato\Core\Generator\Traits;

trait ParserTrait
{
    /**
     * Replaces the variables in the path structure with defined values.
     */
    public function parsePathStructure(array|string $path, array $data): string|array
    {
        $path = str_replace(array_map([$this, 'maskPathVariables'], array_keys($data)), array_values($data), $path);

        return str_replace('*', $this->parsedFileName, $path);
    }

    /**
     * Replaces the variables in the file structure with defined values.
     */
    public function parseFileStructure(array|string $filename, array $data): string|array
    {
        return str_replace(array_map([$this, 'maskFileVariables'], array_keys($data)), array_values($data), $filename);
    }

    /**
     * Replaces the variables in the stub file with defined values.
     */
    public function parseStubContent(string $stub, array $data): string|array
    {
        return str_replace(array_map([$this, 'maskStubVariables'], array_keys($data)), array_values($data), $stub);
    }

    private function maskPathVariables($key): string
    {
        return '{' . $key . '}';
    }

    private function maskFileVariables($key): string
    {
        return '{' . $key . '}';
    }

    private function maskStubVariables($key): string
    {
        return '{{' . $key . '}}';
    }
}
