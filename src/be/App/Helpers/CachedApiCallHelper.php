<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CachedApiCallHelper
{
    public static function cachedApiCall(string $cacheKey, int $minutes, callable $apiCall): JsonResponse
    {
        return Cache::remember($cacheKey, now()->addMinutes($minutes), function () use ($apiCall) {
            $response = $apiCall();

            if ($response->status() !== 200) {
                return $response->content();
            }

            return $response->getData(true);
        });
    }
}