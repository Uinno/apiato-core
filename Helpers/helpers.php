<?php

declare(strict_types=1);

if (!function_exists('uncamelize')) {
    /**
     * @return string|string[]|null
     */
    function uncamelize(string $word, string $splitter = ' ', bool $uppercase = true): array|string|null
    {
        $word = preg_replace(
            '/(?!^)[[:upper:]][[:lower:]]/',
            '$0',
            preg_replace('/(?!^)[[:upper:]]+/', $splitter . '$0', $word)
        );

        return $uppercase ? ucwords($word) : $word;
    }
}
