<?php

declare(strict_types=1);

namespace Apiato\Core\Middlewares\Http;

use Apiato\Core\Abstracts\Middlewares\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class ProcessETagHeadersMiddleware extends Middleware
{
    public function handle(Request $request, Closure $next)
    {
        /*
         * This middleware will add the "ETag" HTTP Header to a Response. The ETag, in turn, is a
         * hash of the content that will be returned. The client may request an endpoint and provide an ETag in the
         * "If-None-Match" HTTP Header. If the calculated ETag and submitted ETag matches, the response is manipulated accordingly:
         * - the HTTP Status Code is set to 304 (not modified)
         * - the body content (i.e., the content that was supposed to be delivered) is removed --> the client receives an empty body
         */

        // The feature is disabled - so skip everything.
        if (!config('apiato.requests.use-etag', false)) {
            return $next($request);
        }

        // Check, if an "if-none-match" header is supplied.
        if ($request->hasHeader('if-none-match')) {
            // Check, if the request method is GET or HEAD.
            $method = $request->method();

            if (!($method === 'GET' || $method === 'HEAD')) {
                throw new PreconditionFailedHttpException('HTTP Header IF-None-Match is only allowed for GET and HEAD Requests.');
            }
        }

        // Everything is fine, just call the next middleware. We will process the ETag later on…
        $response = $next($request);

        // Now we have processed the request and have a response that is sent back to the client.
        // Calculate the etag of the content!
        $content = $response->getContent();
        $etag    = md5($content);
        $response->headers->set('Etag', $etag);

        // Now, lets check, if the request contains an "if-none-match" http header field
        // now check, if the if-none-match etag is the same as the calculated etag!
        if ($request->hasHeader('if-none-match') && $request->header('if-none-match') === $etag) {
            $response->setStatusCode(304);
        }

        return $response;
    }
}
