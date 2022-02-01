<?php

declare(strict_types=1);

namespace Apiato\Core\Middlewares\Http;

use Apiato\Core\Abstracts\Middlewares\Middleware;
use Apiato\Core\Exceptions\MissingJSONHeaderException;
use Closure;
use Illuminate\Http\Request;

class ValidateJsonContent extends Middleware
{
    /**
     * @throws MissingJSONHeaderException
     */
    public function handle(Request $request, Closure $next)
    {
        $acceptHeader = $request->header('accept');
        $contentType  = 'application/json';

        // Check if the accept header is set to application/json
        // If forcing users to have the accept header is enabled, then throw an exception
        if (!str_contains($acceptHeader, $contentType) && config('apiato.requests.force-accept-header')) {
            throw new MissingJSONHeaderException();
        }

        // The request has to be processed, so get the response after the request is done
        $response = $next($request);

        // Set Content Languages header in the response | always return Content-Type application/json in the header
        $response->headers->set('Content-Type', $contentType);

        // If request doesn't contain in header accept = application/json. Return a warning in the response
        if (!str_contains($acceptHeader, $contentType)) {
            $warnCode    = '199'; // https://www.iana.org/assignments/http-warn-codes/http-warn-codes.xhtml
            $warnMessage = 'Missing request header [ accept = ' . $contentType . ' ] when calling a JSON API.';
            $response->headers->set('Warning', $warnCode . ' ' . $warnMessage);
        }

        // Return the response
        return $response;
    }
}
