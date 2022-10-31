<?php

declare(strict_types=1);

namespace Apiato\Core\Exceptions;

use Apiato\Core\Abstracts\Exceptions\Exception;
use Symfony\Component\HttpFoundation\Response;

class MissingJSONHeaderException extends Exception
{
    /**
     * @var int
     */
    protected $code = Response::HTTP_BAD_REQUEST;

    /**
     * @var string
     */
    protected $message = 'Your request must contain [Accept = application/json].';
}
