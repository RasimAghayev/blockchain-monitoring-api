<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth\Requests;

use App\Http\Requests\ApiFormRequest;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="LoginRequest",
 *     title="Login Request",
 *     type="object",
 *     required={"email", "password"},
 *
 *     @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
 *     @OA\Property(property="password", type="string", format="password", example="admin123")
 * )
 */
class LoginRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
