<?php

namespace App\Http\Controllers;

use App\Services\dashboardService;
use Exception;
use Illuminate\Http\Request;

class dashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(dashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function getPlatformAnalytics(Request $request)
    {
        try {
            
            $data = $this->dashboardService->generatePlatformReport();
            
            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to generate analytics: ' . $e->getMessage()
            ], 500);
        }
    }
}
