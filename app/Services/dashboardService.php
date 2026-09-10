<?php

namespace App\Services;

use App\Models\KbsAssessment;

class dashboardService
{
    public function generatePlatformReport()
    {
        // 1. Fetch only completed assessments
        $assessments = KbsAssessment::where('status', 'completed')->get();
        $totalAssessments = $assessments->count();

        // 2. Initialize our empty statistics skeleton for the 8 branches
        $branchStats = [];
        for ($i = 1; $i <= 8; $i++) {
            $branchStats["branch_$i"] = [
                'branch_id' => $i,
                'total_processed' => 0,
                'skipped_count' => 0,
                'completed_count' => 0,
                'average_score_pct' => 0, // <--- New field for the final mean
                '_temp_score_sum' => 0,   // Hidden temp tracker
                '_temp_score_count' => 0, // Hidden temp tracker
                'severities' => [
                    'Mild' => 0,
                    'Moderate' => 0,
                    'Severe' => 0,
                    'Critical' => 0
                ]
            ];
        }

        // 3. Loop through the database and tally up the numbers
        foreach ($assessments as $assessment) {
            $reports = $assessment->reports ?? [];

            foreach ($reports as $branchKey => $reportData) {
                // Ignore any malformed keys just in case
                if (!isset($branchStats[$branchKey])) continue;

                $branchStats[$branchKey]['total_processed']++;

                $status = $reportData['status'] ?? 'incomplete';

                if ($status === 'skipped') {
                    $branchStats[$branchKey]['skipped_count']++;
                } 
                elseif ($status === 'completed') {
                    $branchStats[$branchKey]['completed_count']++;

                    // 4. Extract Severity & Score (Handles both Single and Multi-Domain)
                    $isMultiDomain = $reportData['is_multi_domain'] ?? false;

                    if ($isMultiDomain && isset($reportData['domains'])) {
                        // For complex branches, tally the severity and score of EACH domain
                        foreach ($reportData['domains'] as $domain) {
                            // Tally Severity
                            $severity = $domain['severity'] ?? null;
                            if ($severity && isset($branchStats[$branchKey]['severities'][$severity])) {
                                $branchStats[$branchKey]['severities'][$severity]++;
                            }
                            
                            // Tally Score for the Mean
                            if (isset($domain['score_pct'])) {
                                $branchStats[$branchKey]['_temp_score_sum'] += $domain['score_pct'];
                                $branchStats[$branchKey]['_temp_score_count']++;
                            }
                        }
                    } else {
                        // For simple branches, tally the root severity and score
                        $severity = $reportData['severity'] ?? null;
                        if ($severity && isset($branchStats[$branchKey]['severities'][$severity])) {
                            $branchStats[$branchKey]['severities'][$severity]++;
                        }

                        // Tally Score for the Mean
                        if (isset($reportData['score_pct'])) {
                            $branchStats[$branchKey]['_temp_score_sum'] += $reportData['score_pct'];
                            $branchStats[$branchKey]['_temp_score_count']++;
                        }
                    }
                }
            }
        }

        // 5. Calculate the final averages and clean up the temporary trackers
        foreach ($branchStats as $key => $stats) {
            if ($stats['_temp_score_count'] > 0) {
                // Calculate Mean and round to 1 decimal place (e.g., 68.5)
                $mean = $stats['_temp_score_sum'] / $stats['_temp_score_count'];
                $branchStats[$key]['average_score_pct'] = round($mean, 1);
            }
            
            // Remove the temporary trackers so they don't show up in the JSON response
            unset($branchStats[$key]['_temp_score_sum']);
            unset($branchStats[$key]['_temp_score_count']);
        }

        // 6. Structure the final payload for the frontend Admin Dashboard
        return [
            'overview' => [
                'total_completed_assessments' => $totalAssessments,
                'generated_at' => now()->toIso8601String(),
            ],
            'branch_analytics' => $branchStats
        ];
    }
}