<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->resource['user'];

        return [
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'user_name' => $user->user_name ?? null,
                    'email' => $user->email,
                    'avatar' => $user->avatar ?? null,
                ],
                'access_token' => $this->resource['access_token'],
                'refresh_token' => $this->resource['refresh_token'],
            ],
        ];
    }
}
