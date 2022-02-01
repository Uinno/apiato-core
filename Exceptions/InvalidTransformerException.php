<?php

declare(strict_types=1);

namespace Apiato\Core\Exceptions;

use Apiato\Core\Abstracts\Exceptions\Exception;
use Apiato\Core\Abstracts\Transformers\Transformer;
use Symfony\Component\HttpFoundation\Response;

class InvalidTransformerException extends Exception
{
    /**
     * @var int
     */
    protected $code = Response::HTTP_INTERNAL_SERVER_ERROR;

    /**
     * @var string
     */
    protected $message = 'Transformers must extended the ' . Transformer::class . ' class.';
}
