<?php

namespace App\Services;

use App\Jobs\ProcessVideoJob;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use FFMpeg\Format\Audio\Wav;
use App\Jobs\SendAnalysisCompleteNotification;
use App\Models\Video;
use Illuminate\Http\Request;

class UploadVideoService
{
    public function handle($videoFile)
    {
        $user = auth('api')->user();
        if (!$user) {
            throw new \Exception('User not authenticated');
        }

        $path = $videoFile->store('videos', 'public');
        $filename = pathinfo($path, PATHINFO_FILENAME);

        $audioPath = 'audio/' . $filename . '.wav';
        // 1. Change to .png here!
        $thumbnailPath = 'thumbnails/' . $filename . '.png';

        try {
            // 2. Extract Audio
            FFMpeg::fromDisk('public')
                ->open($path)
                ->export()
                ->toDisk('public')
                ->inFormat(new Wav())
                ->save($audioPath);

            // 3. Extract Thumbnail (PNG avoids the JPEG color space crash)
            FFMpeg::fromDisk('public')
                ->open($path)
                ->getFrameFromSeconds(0)
                ->export()
                ->toDisk('public')
                ->save($thumbnailPath);
        } catch (\Exception $e) {
            throw new \Exception('Failed to process video: ' . $e->getMessage());
        }
        

        // 4. Save to DB
        $video = Video::create([
            'user_id' => $user->id,
            'file_path' => $path,
            'audio_path' => $audioPath,
            'thumbnail_path' => $thumbnailPath,
            'status' => 'processing',
        ]);
        ProcessVideoJob::dispatch($video);
        return $video;
    }




    public function updateStatus(array $data, Video $video)
    {
        $status = $data['status'];
        if ($video->status === 'completed') {
            return false; // Already completed, no further action needed
        }
        // 1. Idempotency Guard
        if (in_array($status, ['analyzed', 'ready']) && in_array($video->status, ['analyzed', 'ready'])) {
            return false; // Tell the controller it was already processed
        }

        // 2. Update the Database
        $updateData = ['status' => $status];

        if (isset($data['phq8_scores'])) {
            $updateData['model_scores'] = $data['phq8_scores'];
        }

        $video->update($updateData);

        // 3. Fire the Notification[cite: 1]
        if (in_array($status, ['analyzed', 'ready'])) {
            SendAnalysisCompleteNotification::dispatch($video);
        }

        return true; // Tell the controller the update was successful
    }
}
