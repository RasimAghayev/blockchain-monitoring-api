<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CachedApiCallHelper
{
    public static function cachedApiCall(string $cacheKey, int $minutes, callable $apiCall): JsonResponse
    {
        // Cache the status and body rather than the JsonResponse itself, then
        // rebuild the response. Returning the raw body from the remember
        // closure broke the JsonResponse return type and turned every call into
        // a 500.
        $cached = Cache::remember($cacheKey, now()->addMinutes($minutes), function () use ($apiCall) {
            $response = $apiCall();

            return [
                'status' => $response->status(),
                'body' => $response->getData(true),
            ];
        });

        return response()->json($cached['body'], $cached['status']);
    }
}
