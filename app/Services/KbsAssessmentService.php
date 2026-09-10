<?php

namespace App\Services;

use App\Models\KbsAssessment;
use App\Models\Video;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KbsAssessmentService
{
    public function startAssessment(int $videoId, int $userId)
    {
        $video = Video::findOrFail($videoId);

        // 1. The Bouncer
        if ($video->status === 'processing') {
            throw new Exception('The AI is still analyzing this video. Please wait until it is ready.', 400);
        }

        // 2. Check Assessment
        $assessment = KbsAssessment::where('user_id', $userId)
            ->where('video_id', $videoId)
            ->first();

        if ($assessment && $assessment->status === 'completed') {
            throw new Exception('You have already completed the assessment for this video. Please upload a new video.', 403);
        }

        // 3. Resume or Create
        if (!$assessment || $assessment->status !== 'in_progress') {
            $assessment = KbsAssessment::create([
                'user_id' => $userId,
                'video_id' => $videoId,
                'status' => 'in_progress',
                'current_branch' => 1,
                'answers' => [],
            ]);
        }

        // 4. Update Video Status
        $video->status = 'in_progress';
        $video->save();

        return $this->processNextStep($assessment);
    }

    public function processAnswer(int $assessmentId, int $userId, string $questionKey, $answerValue)
    {
        $assessment = KbsAssessment::where('user_id', $userId)->findOrFail($assessmentId);

        // Add the new answer to the memory bank
        $answers = $assessment->answers ?? [];
        $answers[$questionKey] = (int) $answerValue;
        $assessment->answers = $answers;
        $assessment->save();

        return $this->processNextStep($assessment);
    }

    /**
     * The Magic Loop: Talks to Python and advances the branches.
     */
    private function processNextStep(KbsAssessment $assessment)
    {
        while ($assessment->current_branch <= 8) {

            $branchKeys = [
                1 => 'no_interest',
                2 => 'depressed',
                3 => 'sleep',
                4 => 'tired',
                5 => 'appetite',
                6 => 'failure',
                7 => 'concentrating',
                8 => 'moving',
            ];

            $video = Video::find($assessment->video_id);
            $keyString = $branchKeys[$assessment->current_branch];
            $modelScore = (float) ($video->model_scores[$keyString] ?? 0.0);
            
            // Force empty PHP arrays to become empty JSON objects {} for Python
            $answersPayload = empty($assessment->answers) ? new \stdClass() : $assessment->answers;

            try {
                // Ask Python what to do (Added a 10-second timeout to prevent infinite hanging)
                $response = Http::timeout(10)->post("http://127.0.0.1:8001/api/branch{$assessment->current_branch}", [
                    'model_score' => $modelScore,
                    'answers'     => $answersPayload,
                ]);

                // Check if Python threw a 500 error or crashed
                if ($response->failed()) {
                    Log::error("Python Engine Error on Branch {$assessment->current_branch}: " . $response->body());
                    throw new Exception('The AI Engine encountered an error processing your answers.', 502);
                }

                $data = $response->json();

            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                // Catches the error if the Python server is offline completely
                Log::error("Python Engine Offline: " . $e->getMessage());
                throw new Exception('The AI Engine is currently offline. Please try again later.', 503);
            }

            // Route the response
            if ($data['status'] === 'needs_answer') {
                return [
                    'status' => 'question',
                    'assessment_id' => $assessment->id,
                    'question' => $data['question']
                ];
            }

            if ($data['status'] === 'complete') {
                // Branch finished! Save the report
                $reports = $assessment->reports ?? [];
                $reports['branch_' . $assessment->current_branch] = $data['report'];
                $assessment->reports = $reports;

                // Wipe answers, increment branch, and loop again instantly
                $assessment->answers = [];
                $assessment->current_branch++;
                $assessment->save();
            }
        }

        // Loop finished - Assessment Complete!
        $assessment->status = 'completed';
        $assessment->save();

        $video = Video::find($assessment->video_id);
        if ($video) {
            $video->status = 'completed';
            $video->save();
        }

        return [
            'status' => 'completed',
            'final_reports' => $assessment->reports
        ];
    }
}