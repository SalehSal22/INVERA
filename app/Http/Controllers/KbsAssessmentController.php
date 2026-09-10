<?php

namespace App\Http\Controllers;

use App\Models\KbsAssessment;
use App\Models\Video;
use App\Services\KbsAssessmentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class KbsAssessmentController extends Controller
{
    protected KbsAssessmentService $kbsService;

    
    public function __construct(KbsAssessmentService $kbsService)
    {
        $this->kbsService = $kbsService;
    }

    public function start(Request $request)
    {
        $request->validate([
            'video_id' => 'required|exists:videos,id'
        ]);

        try {
            
            $result = $this->kbsService->startAssessment($request->video_id, auth('api')->id());
            
            return response()->json($result, 200);

        } catch (Exception $e) {
            
            $statusCode = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }

    public function nextQ(Request $request)
    {
        $validated = $request->validate([
            'assessment_id' => 'required|exists:kbs_assessments,id',
            'question_key'  => 'required|string',
            'answer_value'  => 'required' 
        ]);

        try {
            
            $result = $this->kbsService->processAnswer(
                $validated['assessment_id'], 
                auth('api')->id(), 
                $validated['question_key'], 
                $validated['answer_value']
            );
            
            return response()->json($result, 200);

        } catch (Exception $e) {
            $statusCode = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }
public function getReports(Video $video)
    {   
        try{

        
        
        if ($video->user_id !== auth('api')->id()) {
            return response()->json(['message' => 'Unauthorized access to this video'], 403);
        }

        
        $assessment = $video->kbsAssessments; 

        
        if (!$assessment) {
            return response()->json([
                'success' => false,
                'message' => 'No assessment found for this video.'
            ], 404);
        }
        if($assessment->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Assessment is not yet completed. Please complete the assessment to view reports.'
            ], 400);
        }

        
        return response()->json([
            'success' => true,
            'status' => $assessment->status, 
            'reports' => $assessment->reports ?? [] 
        ], 200);
        }catch(Exception $e){
            $statusCode = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }
}
