<?php

namespace App\Services\Blockchain;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BlockchainService
{
    private string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.blockchain.base_url');
    }

    public function getTokenInfo(string $address): JsonResponse
    {
        return $this->makeApiRequest("token/$address",
            'Token info not found',
            function ($tokenData) {
                return [
                    'name' => $tokenData['name'] ?? 'Unknown',
                    'symbol' => $tokenData['symbol'] ?? 'N/A',
                    'total_supply' => $tokenData['total_supply'] ?? 0,
                ];
            });
    }

    private function makeApiRequest(string $endpoint, string $errorMessage, callable $successCallback): JsonResponse
    {
        try {
            $response = Http::get("{$this->apiUrl}/{$endpoint}");

            if ($response->failed()) {
                return response()->json(['error' => $errorMessage], 404);
            }

            $data = $response->json();
            return response()->json($successCallback($data));
        } catch (\Exception $e) {
            Log::error('Blockchain API error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error occurred'], 500);
        }
    }

    public function getTopHolders(string $address): JsonResponse
    {
        return $this->makeApiRequest("token/$address/holders?limit=100",
            'Token holders not found',
            function ($holdersData) use ($address) {
                return [
                    'token_address' => $address,
                    'top_holders' => $holdersData['holders'] ?? [],
                ];
            });
    }

    public function getLatestTransactions(string $address): JsonResponse
    {
        return $this->makeApiRequest("address/$address/transactions?limit=50",
            'Transaction data not found',
            function ($transactions) use ($address) {
                return [
                    'address' => $address,
                    'transactions' => $transactions['transactions'] ?? [],
                ];
            });
    }
}