<?php

namespace App\Http\Controllers;

use App\Services\HomeService;
use Illuminate\Http\Request;

class HomeConrtoller extends Controller
{
    public function __construct(protected HomeService $homeService) {}
    public function index(Request $request)
    {
        try {
            $result = $this->homeService->getHomeData(auth('api')->id());
            $result['latest_video'] = $result['latest_video'] ? [
                'id' => $result['latest_video']->id,
                'status' => $result['latest_video']->status,
                'thumbnail_url' => asset('storage/' . $result['latest_video']->thumbnail_path),
                'created_at' => $result['latest_video']->created_at,
                'updated_at' => $result['latest_video']->updated_at,
            ] : null;
            $result['user'] = $result['user'] ? [
                'id' => $result['user']->id,
                'name' => $result['user']->user_name,
                'email' => $result['user']->email,
                'avatar' => $result['user']->avatar ? asset('storage/' . $result['user']->avatar) : null,
            ] : null;
            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve home data', 'error' => $e->getMessage()], 500);
        }
    }
    public function getUserInfo()
    {
        try {

            $user = auth('api')->user();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve user info', 'error' => $e->getMessage()], 500);
        }
        return response()->json([
            'id' => $user->id,
            'name' => $user->user_name,
            'email' => $user->email,
            'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
        ], 200);
    }
    public function uploadPhoto(Request $request)
    {
        try {
            $request->validate([
                'photo' => 'required|image|max:2048',
            ]);
            $response = $this->homeService->uploadPhoto($request->file('photo'));
            return response()->json(['message' => 'Photo uploaded successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to upload photo', 'error' => $e->getMessage()], 500);
        }
    }
}
