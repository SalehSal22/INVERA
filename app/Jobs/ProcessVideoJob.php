<?php

namespace App\Jobs;

use App\Models\Video;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessVideoJob implements ShouldQueue
{
    use Queueable;
    protected $video;
    public $tries = 3;

    public function __construct(Video $video)
    {
        $this->video = $video;
    }

    public function handle(): void
    {

        $absoluteVideoPath = storage_path('app/public/' . $this->video->file_path);
        $absoluteAudioPath = storage_path('app/public/' . $this->video->audio_path);

        try {
            
            $response = Http::timeout(0)
                ->withOptions(['expect' => false])
                ->withHeaders([
                    'X-API-Key' => "ASDFGHJKL333", 
                    'accept' => 'application/json', 
                ])
               
                ->attach(
                    'video_file',
                    fopen($absoluteVideoPath, 'r'),
                    'video.mp4'
                )
                
                ->attach(
                    'audio_file',
                    fopen($absoluteAudioPath, 'r'),
                    'audio.wav'
                )
                
                ->post('http://192.168.16.103:8000/process', [
                    'video_id' => $this->video->id,
                ]);

            if ($response->failed()) {
                throw new Exception('ML Server returned an error: ' . $response->body());
            }
        } catch (Exception $e) {

            Log::error("Failed to send Video ID {$this->video->id} to ML server: " . $e->getMessage());

            throw $e;
        }
    }
}
