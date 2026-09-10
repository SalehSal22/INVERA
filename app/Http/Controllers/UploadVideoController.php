<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Services\UploadVideoService;
use Illuminate\Http\Request;

class UploadVideoController extends Controller
{
    public function __construct(protected UploadVideoService $uploadVideoService) {}

    public function upload(Request $request)
    {
        set_time_limit(0); 
        $request->validate([
            'video' => 'required|file|mimetypes:video/mp4,video/avi,video/mov|max:102400', 
        ]);
        try {
            $result = $this->uploadVideoService->handle($request->file('video'));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Video upload failed', 'error' => $e->getMessage()], 500);
        }

        
        return response()->json(['message' => 'Video uploaded successfully', 'video_id' => $result->id], 201);
    }
    public function getVideos(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $videos = Video::where('user_id', $user->id)->latest()->get();
        $map= $videos->map(function ($video) {
            return [
                'id' => $video->id,
                'status' => $video->status,
                'thumbnail_url' => asset('storage/' . $video->thumbnail_path),
                'created_at' => $video->created_at,
                'updated_at' => $video->updated_at,
            ];
        });

        return response()->json(['videos' => $map], 200);
    }






    public function updateStatus(Request $request, Video $video)
    {
        
        if ($request->header('X-Model-API-Key') !== config('services.ml.api_key')) {
            abort(403, 'Unauthorized service request.');
        }

        
        $request->validate([
            'status' => 'required|string|in:analyzed,ready,failed',
            'phq8_scores' => 'required_if:status,analyzed|required_if:status,ready|array',
        ]);

        try {
           
            $wasUpdated = $this->uploadVideoService->updateStatus($request->all(), $video);

            if (!$wasUpdated) {
                return response()->json(['message' => 'Video already processed.'], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update video status', 'error' => $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Video status updated successfully'], 200);
    }
}
