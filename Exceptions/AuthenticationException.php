<?php

declare(strict_types=1);

namespace Apiato\Core\Exceptions;

use Apiato\Core\Abstracts\Exceptions\Exception;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationException extends Exception
{
    /**
     * @var int
     */
    protected $code = Response::HTTP_UNAUTHORIZED;

    /**
     * @var string
     */
    protected $message = 'An Exception occurred while trying to authenticate the User.';
}
