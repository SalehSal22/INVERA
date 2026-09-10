<?php

namespace App\Services;

class HomeService
{
    public function getHomeData($userId)
    {
        // Fetch the latest video for the user
        $latestVideo = \App\Models\Video::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();

        $user = auth('api')->user();

        return [
            'latest_video' => $latestVideo,
            'user' => $user,
        ];
    }
    public function uploadPhoto($photo)
    {
        $path = $photo->store('avatars', 'public');
        $user = auth('api')->user();
        $user->avatar = $path;
        $user->save();
        return $path;
    }
}