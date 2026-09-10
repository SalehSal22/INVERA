<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;

class SendAnalysisCompleteNotification implements ShouldQueue
{

    use  Queueable;

    
    public function __construct(public Video $video) {}

    public function handle(): void 
    {
        $user = $this->video->user;

        $tokens = $user->fcmTokens()->pluck('token')->toArray();

        if (empty($tokens)) {
            return; 
        }

    
        $messaging = Firebase::messaging(); 

        $notification = Notification::create(
            'Analysis Complete',
            'Your video has been analyzed. You can now start your assessment.'
        );

        foreach ($tokens as $token) {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification)
                ->withData([
                    'type' => 'assessment_ready',
                    'assessmentId' => (string) $this->video->id,
                    'route' => '/assessment/' . $this->video->id . '/introduction',
                ]);

            try {
                $messaging->send($message);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('FCM Send Error: ' . $e->getMessage());
            }
        }
    }
}