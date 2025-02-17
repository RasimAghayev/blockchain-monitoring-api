<?php

namespace App\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CompressResponse
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if ($response instanceof BinaryFileResponse) {
            return $response;
        }

        if (strpos($request->header('Accept-Encoding'), 'gzip') !== false
            && !in_array('Content-Encoding', $response->headers->keys())) {

            $output = $response->getContent();

            if (strlen($output) > 1024) {
                $compressed = gzencode($output, 9);

                if ($compressed !== false && strlen($compressed) < strlen($output)) {
                    $response->setContent($compressed);
                    $response->headers->add([
                        'Content-Encoding' => 'gzip',
                        'X-Compression-Rate' => round((1 - strlen($compressed) / strlen($output)) * 100, 2) . '%'
                    ]);
                }
            }
        }

        return $response;
    }
}