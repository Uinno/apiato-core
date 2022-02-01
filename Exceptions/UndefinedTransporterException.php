<?php

declare(strict_types=1);

namespace Apiato\Core\Exceptions;

use Apiato\Core\Abstracts\Exceptions\Exception;
use Symfony\Component\HttpFoundation\Response;

class UndefinedTransporterException extends Exception
{
    /**
     * @var int
     */
    protected $code = Response::HTTP_INTERNAL_SERVER_ERROR;

    /**
     * @var string
     */
    protected $message = 'Default Transporter for Request not defined. Please override $transporter in Ship\Parents\Request\Request.';
}
